<?php

namespace Modules\Base\Console;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class ReleaseModulesCommand extends Command
{
    protected $signature = 'base:release-modules';

    protected $description = 'Gerencia o versionamento e release de módulos com Git Flow.';

    protected string $modulesPath;

    /**
     * @var array Variáveis de ambiente para a execução de comandos Git.
     */
    protected array $gitEnv = [];

    public function handle(): int
    {
        $this->info('Iniciando o processo de release de módulos...');

        $developmentModulesPath = base_path('ModDev');

        // Verifica se a pasta ModDev/ existe
        if (!File::exists($developmentModulesPath) || !File::isDirectory($developmentModulesPath)) {
            $this->warn('A pasta ModDev/ não foi encontrada ou não é um diretório.');
            $this->info('Este comando precisa saber onde estão os módulos que você está desenvolvendo ativamente para release.');

            // Pergunta ao usuário qual é a pasta de desenvolvimento
            $customPath = $this->ask('Por favor, digite o caminho da pasta onde seus módulos de desenvolvimento estão localizados (ex: ModulosDev, custom/modules):');

            // Converte o caminho fornecido em um caminho absoluto
            $customPath = base_path($customPath);

            // Valida o caminho fornecido pelo usuário
            if (!File::exists($customPath) || !File::isDirectory($customPath)) {
                $this->error("O caminho '{$customPath}' não existe ou não é um diretório válido. Encerrando.");
                return Command::FAILURE; // Usar FAILURE para indicar erro
            }

            $developmentModulesPath = $customPath;
            $this->info("Usando '{$developmentModulesPath}' como sua pasta de módulos de desenvolvimento.");
        } else {
            $this->info("Pasta ModDev/ encontrada em '{$developmentModulesPath}'. Usando-a para localizar módulos de desenvolvimento.");
        }

        // Define o caminho principal de módulos para o comando (garantindo que seja o validado/perguntado)
        $this->modulesPath = $developmentModulesPath;

        $availableModules = $this->getModulesWithoutPendingCommits();

        if (empty($availableModules)) {
            $this->warn('Nenhum módulo encontrado sem commits pendentes.');

            return Command::SUCCESS;
        }

        $selectedModules = $this->selectModules($availableModules);

        if (empty($selectedModules)) {
            $this->info('Nenhum módulo selecionado. Encerrando.');

            return Command::SUCCESS;
        }

        foreach ($selectedModules as $module) {
            $this->processModuleRelease($module);
        }

        return Command::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            //['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            //['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }

    protected function getModulesWithoutPendingCommits(): array
    {
        $modules = [];
        $currentDevelopmentPath = $this->modulesPath;

        if (File::exists($currentDevelopmentPath) && File::isDirectory($currentDevelopmentPath)) {
            foreach (File::directories($currentDevelopmentPath) as $moduleDir) {
                $moduleName = basename($moduleDir);
                if (
                    $this->isGitRepository($moduleDir) &&
                    ! $this->hasPendingCommits($moduleDir) &&
                    $this->hasUnpushedCommits($moduleDir)
                ) {
                    $modules[$moduleName] = $moduleDir;
                }
            }
        }
        return $modules;
    }

    protected function isGitRepository(string $path): bool
    {
        return File::exists($path.'/.git');
    }

    protected function hasPendingCommits(string $path): bool
    {
        $process = new Process(['git', 'status', '--porcelain'], $path);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return ! empty(trim($process->getOutput()));
    }

    /**
     * Verifica se a branch atual tem commits que ainda não foram enviados (pushed) para o remoto.
     */
    protected function hasUnpushedCommits(string $path): bool
    {
        // PRIMEIRA VERIFICAÇÃO (RÁPIDA, OFFLINE):
        // Verifica se há commits locais à frente da última referência remota conhecida.
        // Se 0, não há nada para enviar, e podemos retornar false imediatamente.
        $processInitial = new Process(['git', 'rev-list', '@{u}..HEAD', '--count'], $path);
        $processInitial->run();

        if (! $processInitial->isSuccessful()) {
            // Se falhar (ex: branch sem upstream), não podemos ter certeza.
            // Para não bloquear, emitimos um aviso e assumimos que pode haver commits.
            $this->warn("Não foi possível realizar a verificação inicial de commits não enviados para '{$path}'.");
            // Decisão: assumir true para ser conservador e forçar a verificação online se necessário.
            // OU: retornar true aqui e pular a verificação online, aceitando a imprecisão.
            // Para a abordagem híbrida, vamos forçar a checagem online se a inicial falhar.
            $aheadCountInitial = 1; // Força a próxima etapa
        } else {
            $aheadCountInitial = (int) trim($processInitial->getOutput());
        }

        // Se a contagem inicial for 0, não há commits locais não enviados
        // de acordo com as referências locais, e podemos retornar imediatamente.
        if ($aheadCountInitial === 0) {
            // Não há commits locais à frente do remoto (baseado na última fetch)
            return false;
        }

        // SEGUNDA ETAPA (LENTA, ONLINE, CONDICIONAL):
        // Se houver commits locais pendentes (aheadCountInitial > 0),
        // só então sincronizamos com o remoto para garantir precisão.
        $this->line("-> [{$path}] Pendências locais detectadas. Sincronizando com o remoto...");
        try {
            $processUpdate = new Process(['git', 'remote', 'update', 'origin'], $path);
            $processUpdate->run();
            if (! $processUpdate->isSuccessful()) {
                $this->warn("Falha ao sincronizar remoto para '{$path}' durante verificação de push: ".$processUpdate->getErrorOutput());

                // Se a sincronização falhar, somos conservadores e retornamos true para forçar verificação manual.
                return true;
            }
        } catch (ProcessFailedException $e) {
            $this->warn("Erro ao sincronizar remoto para '{$path}' durante verificação de push: ".$e->getMessage());

            return true;
        }

        // TERCEIRA VERIFICAÇÃO (RÁPIDA, OFFLINE, APÓS SINCRONIZAÇÃO):
        // Agora que o remoto está atualizado, fazemos a verificação final e precisa.
        $processFinal = new Process(['git', 'rev-list', '@{u}..HEAD', '--count'], $path);
        $processFinal->run();

        if (! $processFinal->isSuccessful()) {
            $this->warn("Não foi possível realizar a verificação final de commits não enviados para '{$path}'. Assumindo que existem.");

            return true;
        }

        $aheadCountFinal = (int) trim($processFinal->getOutput());

        return $aheadCountFinal > 0;
    }

    /**
     * Permite ao usuário selecionar múltiplos módulos.
     */
    protected function selectModules(array $modules): array
    {
        $options = [];
        foreach ($modules as $name => $path) {
            $options[] = $name;
        }

        return multiselect(
            label: 'Selecione os módulos para release:',
            options: $options
        );
    }

    /**
     * Processa a release para um módulo específico.
     */
    protected function processModuleRelease(string $moduleName): void
    {
        // Assuming single modules path for now
        $moduleGitPath = $this->getModuleGitPath($moduleName);

        $this->newLine();
        $this->info("Processando módulo: {$moduleName}");
        $this->line("Caminho para operações Git: {$moduleGitPath}");

        $currentBranch = $this->getCurrentBranch($moduleGitPath);

        if ($currentBranch !== 'develop') {
            $this->warn("A branch atual de '{$moduleName}' é '{$currentBranch}'. Para iniciar uma release, é recomendado estar na branch 'develop'.");
            if (! $this->confirm("Deseja mudar para 'develop'? (Se 'não', o processo será abortado para este módulo)", true)) {
                $this->warn('Release para ' . $moduleName . ' cancelada.');
                return;
            }
            $this->runProcess(['git', 'checkout', 'develop'], $moduleGitPath); // Usando $moduleGitPath
        }

        $currentVersion = $this->getCurrentTag($moduleGitPath);
        $this->info("Versão atual do módulo {$moduleName}: ".($currentVersion ?: 'N/A'));

        $releaseType = $this->askForReleaseType($currentVersion);

        $newVersion = $this->calculateNewVersion($currentVersion, $releaseType);

        if (! $this->confirm("Deseja criar a tag '{$newVersion}' para o módulo '{$moduleName}'?", true)) {
            $this->warn("Release para '{$moduleName}' cancelada.");
            return;
        }

        $this->info("Iniciando release {$newVersion} para {$moduleName}...");
        $this->runProcess(['git', 'flow', 'release', 'start', $newVersion], $moduleGitPath); // Usando $moduleGitPath

        if (! $this->confirm(
            'Deseja finalizar o release e prosseguir com o merge e o push para o remoto?',
            true
        )) {
            $this->warn("Finalização do release para '{$moduleName}' adiada. A branch 'release/{$newVersion}' permanece ativa. Você pode finalizá-la manualmente com 'git flow release finish {$newVersion}'.");
            return;
        }

        $mergeMessage = $this->askForMergeMessage($newVersion);

        $this->info("Finalizando release {$newVersion} para {$moduleName}...");
        $this->runProcess(['git', 'checkout', 'main'], $moduleGitPath); // Usando $moduleGitPath
        $this->runProcess(['git', 'pull'], $moduleGitPath); // Usando $moduleGitPath
        $this->runProcess(['git', 'checkout', 'release/'.$newVersion], $moduleGitPath); // Usando $moduleGitPath
        $this->runProcess(['git', 'flow', 'release', 'finish', $newVersion, '-m', $mergeMessage], $moduleGitPath); // Usando $moduleGitPath

        $this->info("Release '{$newVersion}' finalizada com sucesso para o módulo '{$moduleName}'.");

        $this->info('Enviando alterações e tags para o repositório remoto...');
        $this->runProcess(['git', 'push', '--follow-tags', 'origin', 'develop', 'main'], $moduleGitPath); // Usando $moduleGitPath
        $this->info("Alterações e tags de release enviadas para '{$moduleName}' remoto.");

        // --- INÍCIO DO CÓDIGO NOVO: Etapas de manipulação do Composer para o Projeto PRINCIPAL ---
        // Estas operações ocorrem no diretório raiz do projeto principal, não no diretório do módulo.
        $vendorPackageName = $this->getComposerPackageName($moduleName, $moduleGitPath); // Ainda precisa do path do módulo para buscar o nome do pacote
        $composerJsonPath = base_path('composer.json');
        $composerLockPath = base_path('composer.lock');
        $composerLocalJsonPath = base_path('composer-local.json');
        $composerLocalJsonBackupPath = base_path('composer-local.json.TEMP_DISABLED');

        $this->info('--- Iniciando atualização de dependências no Composer para a release do projeto principal ---');

        // Loop principal para todas as operações do Composer
        try {
            // Chame o novo método auxiliar
            $this->runComposerOperationsWithRetry(
                $vendorPackageName,
                $newVersion, // Certifique-se que $newVersion está definida antes
                $moduleName,
                $composerJsonPath,
                $composerLockPath,
                $composerLocalJsonPath,
                $composerLocalJsonBackupPath
            );
        } catch (\Exception $e) {
            $this->error("O processo de atualização das dependências do Composer falhou: " . $e->getMessage());
            // Garante que o composer.local.json seja restaurado mesmo se uma exceção for lançada
            if (File::exists($composerLocalJsonBackupPath)) {
                $this->warn('Tentando restaurar composer.local.json após erro fatal...');
                rename($composerLocalJsonBackupPath, $composerLocalJsonPath);
            }
            throw $e; // Re-lança a exceção para indicar a falha no processo de release
        }

        // 9. Reiniciar o Sail para aplicar as novas dependências e caches
        $this->info('9. Reiniciando Sail para aplicar as mudanças...');
//        $this->runShellCommand('./vendor/bin/sail down', 'Parando contêineres Sail');
//        $this->runShellCommand('./vendor/bin/sail up -d', 'Iniciando contêineres Sail em segundo plano');

        $this->info("--- Concluída a atualização de dependências e restauração do ambiente ---");
    }

    /**
     * Tenta executar uma série de operações Composer com lógica de retentativa para erros de autenticação.
     *
     * @param string $vendorPackageName Nome do pacote do vendor (ex: 'dvidev/blog-module').
     * @param string $newVersion A nova versão do módulo.
     * @param string $moduleName O nome do módulo (para mensagens ao usuário).
     * @param string $composerJsonPath Caminho para o composer.json.
     * @param string $composerLockPath Caminho para o composer.lock.
     * @param string $composerLocalJsonPath Caminho para o composer.local.json.
     * @param string $composerLocalJsonBackupPath Caminho para o backup do composer.local.json.
     * @throws \RuntimeException Se as operações do Composer falharem após as retentativas.
     */
    private function runComposerOperationsWithRetry(
        string $vendorPackageName,
        string $newVersion,
        string $moduleName,
        string $composerJsonPath,
        string $composerLockPath,
        string $composerLocalJsonPath,
        string $composerLocalJsonBackupPath
    ): void {
        $maxComposerAttempts = 2; // Permite uma tentativa inicial + 1 retentativa

        for ($attempt = 1; $attempt <= $maxComposerAttempts; $attempt++) {
            try {
                $this->info("Tentativa {$attempt}/{$maxComposerAttempts}: Iniciando operações do Composer...");

                // 1. Atualizar a versão do pacote no composer.json principal
                $this->info('1. Atualizando a versão do pacote principal para a nova release...');
                $composerJsonContent = json_decode(File::get($composerJsonPath), true);
                $composerJsonContent['require'][$vendorPackageName] = "^" . ltrim($newVersion, 'v'); // Garante formato ^X.Y.Z
                File::put($composerJsonPath, json_encode($composerJsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->info('composer.json principal atualizado.');

                // 2. Preparar o ambiente Composer para gerar um composer.lock limpo (para produção)
                $this->info('2. Desativando temporariamente composer.local.json e limpando caches do Composer...');
                if (File::exists($composerLocalJsonPath)) {
                    $this->runShellCommand("mv {$composerLocalJsonPath} {$composerLocalJsonBackupPath}", 'Renomeando composer.local.json');
                } else {
                    $this->info('composer.local.json não encontrado, pulando renomeação.');
                }

                // Remove o composer.lock para garantir uma regeneração limpa na próxima etapa (composer install).
                if (File::exists($composerLockPath)) {
                    $this->runShellCommand("rm -f {$composerLockPath}", 'Removendo composer.lock existente');
                } else {
                    $this->info('composer.lock não encontrado ou já removido.');
                }

                $this->runShellCommand('rm -f vendor/composer/autoload_*.php', 'Removendo arquivos de autoloading do Composer');
                $this->runShellCommand('composer clear-cache', 'Limpando cache interno do Composer');

                // 3. Gerar um novo composer.lock "limpo" para produção (com a nova versão tageada)
                // IMPORTANTE: Esta é a sequência CORRIGIDA que comprovadamente funciona para o seu caso.
                $this->info('3. Gerando novo composer.lock para produção com a nova versão (usando sequência de remoção/adição/instalação)...');
                $this->runShellCommand(
                    "composer remove {$vendorPackageName} --no-update",
                    'Removendo temporariamente o módulo do composer.json/lock para forçar a atualização.'
                );
                $this->runShellCommand(
                    "composer require {$vendorPackageName}:\"^" . ltrim($newVersion, 'v') . "\" --no-update",
                    'Adicionando a nova versão do módulo ao composer.json sem atualizar dependências.'
                );
                $this->runShellCommand(
                    "composer install",
                    'Gerando composer.lock limpo para produção com a nova versão do módulo (usando install).'
                );
                /*$this->runShellCommand(
                    "composer install --no-dev",
                    'Gerando composer.lock limpo para produção com a nova versão do módulo (usando install).'
                );*/
                // Fim da sequência para a etapa 3.

                // 4. Comitar as alterações do composer.json e composer.lock
                $this->info('4. Comitando composer.json e composer.lock para o repositório...');
//                $this->runShellCommand('git add composer.json composer.lock', 'Adicionando arquivos ao Git');
//                $this->runShellCommand("git commit -m \"feat(release): Update main project to {$vendorPackageName} {$newVersion}\"", 'Criando commit para o projeto principal');
//                $this->info('composer.json e composer.lock commitados com sucesso no projeto principal.');

                // 5. Instalar as dependências com base no novo composer.lock em produção
                $this->info('5. Instalando dependências de produção...');
                $this->runShellCommand('composer install --no-dev', 'Executando composer install para dependências de produção');

                // 6. Limpar os caches da aplicação para que a nova versão seja utilizada
                $this->info('6. Limpando caches da aplicação e módulos...');
                $this->runShellCommand('artisan cache:clear', 'Limpando cache geral da aplicação');
                $this->runShellCommand('artisan config:clear', 'Limpando cache de configuração');
                $this->runShellCommand('artisan route:clear', 'Limpando cache de rotas');
                $this->runShellCommand('artisan view:clear', 'Limpando cache de views');
                $this->runShellCommand('rm -f storage/app/modules.json', 'Limpando cache de módulos Nwidart (v11.1)');
                $this->runShellCommand('artisan optimize:clear', 'Limpando otimização do Laravel (opcional)');

                // 7. Restaurar o composer.local.json para o ambiente de desenvolvimento
                $this->info('7. Restaurando composer.local.json para continuar o desenvolvimento...');
                if (File::exists($composerLocalJsonBackupPath)) {
                    $this->runShellCommand("mv {$composerLocalJsonBackupPath} {$composerLocalJsonPath}", 'Restaurando composer.local.json');
                } else {
                    $this->info('Nenhum composer.local.json de backup encontrado para restaurar.');
                }

                // 8. Reinstalar dependências para o ambiente de desenvolvimento (religar ModDev)
                $this->info('8. Reinstalando dependências para o ambiente de desenvolvimento (religando ModDev/)...');
//                $this->runShellCommand('composer install', 'Executando composer install para desenvolvimento');
//                $this->runShellCommand('composer dump-autoload -o', 'Otimizando autoloading do Composer');

                // Se chegou até aqui, todas as operações do Composer foram bem-sucedidas. Saia do método.
                return;

            } catch (ProcessFailedException $e) {
                $errorOutput = $e->getProcess()->getErrorOutput();

                // Caso 1: Erro de autenticação de repositório privado
                if (
                    str_contains($errorOutput, 'Could not authenticate against github.com') ||
                    str_contains($errorOutput, 'Failed to authenticate package') ||
                    str_contains($errorOutput, ' Root composer.json requires '.$vendorPackageName.', it could not be found in any version')

                ) {
                    if ($attempt < $maxComposerAttempts) {
                        $this->warn("Falha de autenticação detectada para um pacote. Tentando resolver...");
                        if ($this->confirm("O módulo '{$moduleName}' é um repositório privado no GitHub que precisa ser adicionado ao composer.json?", true)) {
                            // Certifique-se de que o método addVcsRepositoryToComposerJson está definido na sua classe.
                            $this->addVcsRepositoryToComposerJson($vendorPackageName);
                            $this->info("Repositório VSC para '{$vendorPackageName}' adicionado ao composer.json. Retentando as operações do Composer...");
                            // O loop 'for' continuará automaticamente para a próxima tentativa
                        } else {
                            $this->error("Autenticação para pacote privado necessária e não resolvida. Encerrando o processo de release para este módulo.");
                            throw $e; // Re-lança a exceção se o usuário não quiser adicionar o repositório
                        }
                    } else {
                        $this->error("Máximo de tentativas de autenticação para pacote privado atingido. Encerrando o processo de release para este módulo.");
                        throw $e; // Re-lança a exceção após exaustão das tentativas
                    }
                } else {
                    // Caso 2: Qualquer outro tipo de ProcessFailedException que não seja de autenticação
                    $this->error("Ocorreu um erro durante as operações do Composer: " . $e->getMessage());
                    $this->error("Output: " . $e->getProcess()->getOutput());
                    $this->error("Error Output: " . $e->getProcess()->getErrorOutput());
                    // Tentativa de restaurar composer.local.json em caso de falha
                    if (File::exists($composerLocalJsonBackupPath)) {
                        $this->warn('Tentando restaurar composer.local.json após erro...');
                        rename($composerLocalJsonBackupPath, $composerLocalJsonPath);
                    }
                    throw $e; // Relança a exceção para indicar falha no comando e sair do loop
                }
            }
        }
        // Se o loop terminou sem um 'return' (sucesso) ou um 'throw' explícito
        $this->error("As operações do Composer falharam após várias tentativas ou por um erro não recuperável. Por favor, verifique o erro acima.");
        throw new \RuntimeException("Falha crítica nas operações do Composer para o módulo '{$moduleName}'.");
    }

    /**
     * Adiciona uma entrada de repositório VCS para um pacote privado no composer.json.
     *
     * @param string $packageName O nome completo do pacote (ex: 'vendor/modulename-module').
     */
    private function addVcsRepositoryToComposerJson(string $packageName): void
    {
        $composerJsonPath = base_path('composer.json');
        $composerJsonContent = json_decode(File::get($composerJsonPath), true);

        // Extrai o vendor e o nome do módulo do packageName
        list($vendor, $module) = explode('/', $packageName, 2);
        // Assume o formato do URL do repositório Git no GitHub
        $repoUrl = "git@github.com:{$vendor}/{$module}.git";

        $newRepository = [
            'type' => 'vcs',
            'url' => $repoUrl
        ];

        // Garante que a seção 'repositories' existe
        if (!isset($composerJsonContent['repositories'])) {
            $composerJsonContent['repositories'] = [];
        }

        // Verifica se o repositório já existe para evitar duplicações
        $exists = false;
        foreach ($composerJsonContent['repositories'] as $repo) {
            if (isset($repo['url']) && $repo['url'] === $repoUrl) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $composerJsonContent['repositories'][] = $newRepository;
            File::put($composerJsonPath, json_encode($composerJsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info("Repositório VSC '{$repoUrl}' adicionado ao composer.json.");
        } else {
            $this->warn("Repositório VSC '{$repoUrl}' já existe no composer.json.");
        }
    }

    /**
     * Retorna o caminho do módulo onde as operações Git devem ser executadas.
     * Prioriza ModDev/ se o módulo existir e for um repositório Git válido lá.
     *
     * @param string $moduleName O nome do módulo.
     * @return string O caminho completo para o diretório do módulo onde o Git deve operar.
     * @throws \RuntimeException Se o módulo não for encontrado em nenhum dos caminhos configurados.
     */
    protected function getModuleGitPath(string $moduleName): string
    {
        $modDevPath = base_path('ModDev/'.$moduleName);
        if (File::exists($modDevPath) && File::isDirectory($modDevPath) && $this->isGitRepository($modDevPath)) {
            $this->line("Detectado módulo '{$moduleName}' em ModDev/. Usando este caminho para operações Git.");
            return $modDevPath;
        }

        // Se não estiver em ModDev/ ou não for um repo Git lá, tenta o caminho padrão (Modules/)
        $modulesPath = $this->modulesPath[0].'/'.$moduleName;
        if (File::exists($modulesPath) && File::isDirectory($modulesPath) && $this->isGitRepository($modulesPath)) {
            $this->line("Módulo '{$moduleName}' não encontrado em ModDev/ ou sem repo Git. Usando caminho padrão: Modules/.");
            return $modulesPath;
        }

        throw new \RuntimeException("Módulo '{$moduleName}' não encontrado ou não é um repositório Git válido em ModDev/ nem em nenhum dos caminhos configurados em base.modules.paths.");
    }


    /**
     * Executa um comando de shell direto (sem Sail).
     * Útil para 'mv', 'rm', 'git add', 'git commit'.
     *
     * @param string $command Comando shell a ser executado.
     * @param string $message Mensagem para exibir ao usuário.
     * @throws ProcessFailedException Se o comando falhar.
     */
    protected function runShellCommand(string $command, string $message): void
    {
        $this->line("-> {$message}");
        $process = Process::fromShellCommandline($command, base_path());
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Erro ao executar: {$command}");
            $this->error($process->getErrorOutput());
            throw new ProcessFailedException($process);
        }
        $this->line($process->getOutput());
    }

    /**
     * Configura a identidade do Git para o processo atual.
     */
    protected function setupGitIdentity(): void
    {
        if (! empty($this->gitEnv)) {
            return;
        }

        $userName = env('GIT_USER_NAME');
        $userEmail = env('GIT_USER_EMAIL');

        $variablesToSave = [];

        if (empty($userName)) {
            $userName = $this->ask('Por favor, informe seu nome para o Git (será salvo no .env):', 'Laravel Developer');
            $variablesToSave['GIT_USER_NAME'] = $userName;
        }

        if (empty($userEmail)) {
            $userEmail = $this->ask('Por favor, informe seu e-mail para o Git (será salvo no .env):', 'dev@laravel.com');
            $variablesToSave['GIT_USER_EMAIL'] = $userEmail;
        }

        if (! empty($variablesToSave)) {
            $this->info('Salvando credenciais Git no arquivo .env...');
            $this->updateDotEnv($variablesToSave);
        }

        $this->info("Configurando identidade Git para o processo: {$userName} <{$userEmail}>");

        $this->gitEnv = [
            'GIT_AUTHOR_NAME' => $userName,
            'GIT_AUTHOR_EMAIL' => $userEmail,
            'GIT_COMMITTER_NAME' => $userName,
            'GIT_COMMITTER_EMAIL' => $userEmail,
        ];
    }

    /**
     * Atualiza variáveis no arquivo .env.
     *
     * @param  array  $variables  Associative array of key => value.
     */
    protected function updateDotEnv(array $variables): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            $this->error(".env file not found at {$envPath}. Cannot save Git credentials.");

            return;
        }

        $contents = File::get($envPath);
        foreach ($variables as $key => $value) {
            if (str_contains($contents, "{$key}=")) {
                // Substitui a linha se ela já existe
                $contents = preg_replace("/^{$key}=.*\n/m", "{$key}=\"{$value}\"\n", $contents);
            } else {
                // Adiciona a linha ao final do arquivo se não existe
                $contents .= "\n{$key}=\"{$value}\"";
            }
        }
        File::put($envPath, $contents);

        $this->loadDotEnv();
    }

    /**
     * Recarrega o arquivo .env.
     */
    protected function loadDotEnv(): void
    {
        $dotenv = Dotenv::createImmutable(base_path());
        $dotenv->load();
    }

    /**
     * Executa um processo no terminal.
     */
    protected function runProcess(array $command, string $cwd, array $env = []): void
    {
        $this->setupGitIdentity();

        $processEnv = array_merge($_SERVER, $_ENV, $this->gitEnv, $env);

        $process = new Process($command, $cwd, $processEnv);
        $process->setTimeout(3600); // Aumenta o timeout para operações de git mais longas
        $process->run(function ($type, $buffer) {
            if ($type !== Process::ERR) {
                $this->line($buffer);

                return;
            }
            // Verifica se o comando realmente falhou
            if (str_contains(strtolower($buffer), 'fatal:') || str_contains(strtolower($buffer), 'error:')) {
                $this->error('ERRO: '.$buffer);

                return;
            }

            // É apenas uma mensagem de status do Git, mostre como mensagem normal
            $this->line($buffer);
        });

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Obtém a branch atual de um repositório Git.
     */
    protected function getCurrentBranch(string $path): string
    {
        $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $path);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    /**
     * Obtém a última tag de versão semântica.
     */
    protected function getCurrentTag(string $path): ?string
    {
        $process = new Process(['git', 'describe', '--tags', '--abbrev=0', '--match', 'v*.*.*'], $path);
        $process->run();
        if (! $process->isSuccessful()) {
            // No tags found, return initial version
            return 'v0.0.0';
        }

        $tag = trim($process->getOutput());
        if (! str_starts_with($tag, 'v')) {
            $tag = 'v'.explode('-', $tag)[0];
        }

        return $tag;
    }

    /**
     * Pergunta ao usuário o tipo de alteração da release.
     */
    protected function askForReleaseType(?string $currentVersion): string
    {
        $currentVersion = ltrim($currentVersion, 'v');
        [$major, $minor, $patch] = array_pad(explode('.', $currentVersion), 3, 0);

        $options = [
            'major' => 'Major (Nova versão não compatível): v'.($major + 1).'.0.0',
            'minor' => 'Feature (Adição de funcionalidade): v'.$major.'.'.($minor + 1).'.0',
            'patch' => 'Patch (Correção de bugs/melhorias): v'.$major.'.'.$minor.'.'.($patch + 1),
        ];

        return select(
            label: 'Qual o tipo de alteração para esta release?',
            options: array_values($options),
        );
    }

    /**
     * Calcula a nova versão semântica.
     */
    protected function calculateNewVersion(?string $currentVersion, string $releaseType): string
    {
        $currentVersion = ltrim($currentVersion, 'v');
        [$major, $minor, $patch] = array_pad(explode('.', $currentVersion), 3, 0);

        switch ($releaseType) {
            case str_contains($releaseType, 'Major'):
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case str_contains($releaseType, 'Feature'):
                $minor++;
                $patch = 0;
                break;
            case str_contains($releaseType, 'Patch'):
                $patch++;
                break;
        }

        return "v{$major}.{$minor}.{$patch}";
    }

    /**
     * Pergunta a mensagem de merge para a release.
     */
    protected function askForMergeMessage(string $newVersion): string
    {
        $defaultMessage = "Release {$newVersion}";

        return $this->ask("Qual a mensagem para o merge da release? (Padrão: '{$defaultMessage}')", $defaultMessage);
    }

    /**
     * Atualiza a dependência do Composer no projeto principal.
     */
    protected function updateComposerDependency(string $moduleName, string $modulePath, string $newVersion): void
    {
        $vendorPackageName = $this->getComposerPackageName($moduleName, $modulePath);

        if (! $this->isModuleActive($moduleName)) {
            return;
        }

        if ($this->confirm("Atualizar sua dependência no composer.json para '{$vendorPackageName}:{$newVersion}'?", true)) {
            $this->info("Atualizando dependência do Composer para '{$vendorPackageName}:{$newVersion}'...");
            $this->runProcess(['composer', 'require', "{$vendorPackageName}:^{$newVersion}"], base_path());
            $this->info('Dependência do Composer atualizada com sucesso.');

            return;
        }

        $this->warn("Atualização da dependência Composer para '{$moduleName}' cancelada.");
    }

    /**
     * Obtém o nome do pacote Composer de um módulo, preferencialmente do composer.json.
     * Caso contrário, infere o nome e pede confirmação ao usuário.
     *
     * @param  string  $moduleName  O nome do diretório do módulo.
     * @param  string  $modulePath  O caminho completo para o diretório do módulo.
     * @return string O nome do pacote Composer (ex: "vendor/package-name").
     */
    protected function getComposerPackageName(string $moduleName, string $modulePath): string
    {
        $composerJsonPath = "{$modulePath}/composer.json";
        $vendorPackageName = null;

        if (File::exists($composerJsonPath)) {
            try {
                $composerContent = json_decode(File::get($composerJsonPath), true);
                if (isset($composerContent['name'])) {
                    $vendorPackageName = $composerContent['name'];
                }
            } catch (\Exception $e) {
                $this->warn("Não foi possível ler ou parsear o composer.json de '{$moduleName}'. Erro: ".$e->getMessage());
            }
        }

        if (empty($vendorPackageName)) {
            $inferredName = 'vendor/'.strtolower($moduleName).'-module';
            $this->info("Não foi possível encontrar o nome do pacote no composer.json do módulo '{$moduleName}'.");
            $vendorPackageName = $this->ask(
                "Por favor, confirme o nome do pacote Composer para '{$moduleName}' (inferido: {$inferredName}):",
                $inferredName
            );
        }

        return $vendorPackageName;
    }

    /**
     * Verifica se um módulo está ativo usando o pacote nwidart/laravel-modules.
     *
     * @param  string  $moduleName  O nome do módulo (ex: 'BlogModule').
     */
    protected function isModuleActive(string $moduleName): bool
    {
        if (! class_exists(Module::class)) {
            $this->warn('O pacote nwidart/laravel-modules não parece estar instalado ou configurado. Não é possível verificar o status do módulo. Assumindo inativo para segurança.');

            return false;
        }

        if (! $module = \Module::find($moduleName)) {
            return false;
        }

        return $module->isEnabled();
    }

    /**
     * Remove projetos com sufixo "-module" .
     */
    protected function cleanVendor(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $modulesRootPath = $this->modulesPath[0] ?? base_path('Modules');

        if (! File::isDirectory($modulesRootPath)) {
            $this->warn("Diretório de módulos '{$modulesRootPath}' não encontrado. Pulando limpeza da pasta vendor.");

            return;
        }

        $moduleDirectories = File::directories($modulesRootPath);

        if (empty($moduleDirectories)) {
            return;
        }

        $modulesPathInVendor = [];
        foreach ($moduleDirectories as $moduleDir) {
            $moduleName = basename($moduleDir);
            $composerJsonPath = "{$moduleDir}/composer.json";
            $vendorPackageName = null;

            if (File::exists($composerJsonPath)) {
                try {
                    $composerContent = json_decode(File::get($composerJsonPath), true);
                    if (isset($composerContent['name'])) {
                        $vendorPackageName = $composerContent['name'];
                    }
                } catch (\Exception $e) {
                    $this->warn("Não foi possível ler ou parsear o composer.json de '{$moduleName}'. Erro: ".$e->getMessage());

                    continue;
                }
            }

            if (empty($vendorPackageName)) {
                $this->warn("Não foi possível determinar o nome do pacote Composer para o módulo '{$moduleName}'. Pulando remoção da pasta vendor.");

                continue;
            }

            [$vendorPrefix, $packageName] = explode('/', $vendorPackageName, 2);
            $vendorPath = "vendor/{$vendorPrefix}/{$packageName}";

            if (File::isDirectory(base_path($vendorPath))) {
                $modulesPathInVendor[$packageName] = $vendorPath;
            }
        }

        if (count($modulesPathInVendor) == 0) {
            return;
        }

        $this->info('🤖 Limpando módulos locais da pasta vendor...');

        foreach ($modulesPathInVendor as $packageName => $modulePathInVendor) {
            File::deleteDirectory(base_path($modulePathInVendor));
            $this->info("{$modulePathInVendor}' removido para evitar duplicidade.");
        }

        $this->info('🤖✔️ Limpando módulos da pasta vendor concluída.');

        $this->runProcess(['composer', 'dump-autoload'], base_path());
    }
}
