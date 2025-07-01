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

    protected array $modulesPath = [];

    /**
     * @var array Variáveis de ambiente para a execução de comandos Git.
     */
    protected array $gitEnv = [];

    public function handle(): int
    {
        $this->info('Iniciando o processo de release de módulos...');

        $this->modulesPath = config('base.modules.paths') ?? [base_path('Modules')];

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
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }

    protected function getModulesWithoutPendingCommits(): array
    {
        $modules = [];
        foreach ($this->modulesPath as $path) {
            if (File::exists($path) && File::isDirectory($path)) {
                foreach (File::directories($path) as $moduleDir) {
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
        $modulePath = $this->modulesPath[0].'/'.$moduleName;

        $this->newLine();
        $this->info("Processando módulo: {$moduleName} em {$modulePath}");

        $currentBranch = $this->getCurrentBranch($modulePath);

        if ($currentBranch !== 'develop') {
            $this->warn("A branch atual de '{$moduleName}' é '{$currentBranch}'. Para iniciar uma release, é recomendado estar na branch 'develop'.");
            if (! $this->confirm("Deseja mudar para 'develop'? (Se 'não', o processo será abortado para este módulo)", true)) {
                $this->warn('cancelado');

                return;
            }
            $this->runProcess(['git', 'checkout', 'develop'], $modulePath);
        }

        $currentVersion = $this->getCurrentTag($modulePath);
        $this->info("Versão atual do módulo {$moduleName}: ".($currentVersion ?: 'N/A'));

        $releaseType = $this->askForReleaseType($currentVersion);

        $newVersion = $this->calculateNewVersion($currentVersion, $releaseType);

        if (! $this->confirm("Deseja criar a tag '{$newVersion}' para o módulo '{$moduleName}'?", true)) {
            $this->warn("Release para '{$moduleName}' cancelada.");

            return;
        }

        $this->info("Iniciando release {$newVersion} para {$moduleName}...");
        $this->runProcess(['git', 'flow', 'release', 'start', $newVersion], $modulePath);

        if (! $this->confirm(
            'Deseja finalizar o release e prosseguir com o merge e o push para o remoto?',
            true
        )) {
            $this->warn("Finalização do release para '{$moduleName}' adiada. A branch 'release/{$newVersion}' permanece ativa. Você pode finalizá-la manualmente com 'git flow release finish {$newVersion}'.");

            return;
        }

        $mergeMessage = $this->askForMergeMessage($newVersion);

        $this->info("Finalizando release {$newVersion} para {$moduleName}...");
        $this->runProcess(['git', 'checkout', 'main'], $modulePath);
        $this->runProcess(['git', 'pull'], $modulePath);
        $this->runProcess(['git', 'checkout', 'release/'.$newVersion], $modulePath);
        $this->runProcess(['git', 'flow', 'release', 'finish', $newVersion, '-m', $mergeMessage], $modulePath);

        $this->info("Release '{$newVersion}' finalizada com sucesso para o módulo '{$moduleName}'.");

        $this->info('Enviando alterações e tags para o repositório remoto...');
        $this->runProcess(['git', 'push', '--follow-tags', 'origin', 'develop', 'main'], $modulePath);
        $this->info("Alterações e tags de release enviadas para '{$moduleName}' remoto.");

        $this->backupModulesPath();
        $this->updateComposerDependency($moduleName, $modulePath, $newVersion);
        $this->restoreBackupPath();

        //$this->cleanVendor();
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

    protected function backupModulesPath(): void
    {
        $modulesPath = base_path('Modules'); // Supondo que seus módulos estão em `base_path('Modules')`
        $modulesBackupPath = base_path('Modules_backup');

        if (File::isDirectory($modulesPath)) {
            $this->info("Detectado diretório 'Modules'. Criando backup para evitar sobrescrita...");
            // Renomeia a pasta Modules para Modules_backup
            File::move($modulesPath, $modulesBackupPath);
            $this->info("Diretório 'Modules' movido para 'Modules_backup'.");
            return;
        }
        $this->warn("Diretório 'Modules' não encontrado. Nenhuma ação de backup necessária.");
        // Não há Modules para fazer backup
    }

    protected function restoreBackupPath(): void
    {
        $modulesPath = base_path('Modules'); // Supondo que seus módulos estão em `base_path('Modules')`
        $modulesBackupPath = base_path('Modules_backup');

        $this->info("Verificando módulos recém-instalados em Modules/ e removendo-os...");
        if (File::isDirectory($modulesPath)) {
            File::deleteDirectory($modulesPath); // CUIDADO: Isso apaga o módulo.
            /*$newlyInstalledModules = File::directories($modulesPath); // Obtém todos os subdiretórios
            foreach ($newlyInstalledModules as $moduleDir) {
                $moduleName = basename($moduleDir);
                // Você pode precisar de uma lógica mais inteligente aqui para identificar
                // quais módulos vieram do vendor vs. se algum outro processo criou pastas.
                // Uma heurística: se não existe uma pasta com o mesmo nome em Modules_backup
                // OU se o módulo não tem um .git interno.
                $this->info("Removendo módulo recém-instalado via composer: {$moduleName}");
                File::deleteDirectory($moduleDir); // CUIDADO: Isso apaga o módulo.
            }*/
            $this->info("Módulos recém-instalados removidos da pasta 'Modules' temporária.");
        }

        // Renomeia o backup de volta
        if (File::isDirectory($modulesBackupPath)) {
            $this->info("Restaurando diretório 'Modules' do backup...");
            File::move($modulesBackupPath, $modulesPath);
            $this->info("Diretório 'Modules' restaurado com sucesso.");
            return;
        }
        $this->error("Backup de 'Modules' não encontrado para restauração!");
        // Considere um fallback ou aviso grave aqui
    }
}
