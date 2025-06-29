<?php

namespace Modules\Base\Console;

use Illuminate\Console\Command;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class ReleaseModulesCommand extends Command
{
    protected $signature = 'base:release-modules';

    protected $description = 'Gerencia o versionamento e release de módulos com Git Flow.';

    protected array $modulesPath = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Iniciando o processo de release de módulos...');

        // Debug: Imprimir o PATH do ambiente do processo
        /*$process = new Process(['env']); // Ou 'echo $PATH' no Linux
        $process->run();
        if ($process->isSuccessful()) {
            $this->info("PATH do Processo:\n" . $process->getOutput());
        } else {
            $this->error("Não foi possível obter o PATH do processo.");
        }
        dd("debug");*/

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

    /**
     * Obtém os módulos sem commits pendentes.
     *
     * @return array
     */
    protected function getModulesWithoutPendingCommits(): array
    {
        $modules = [];
        foreach ($this->modulesPath as $path) {
            if (File::exists($path) && File::isDirectory($path)) {
                foreach (File::directories($path) as $moduleDir) {
                    $moduleName = basename($moduleDir);
                    if (
                        $this->isGitRepository($moduleDir) &&
                        !$this->hasPendingCommits($moduleDir) &&
                        $this->hasUnpushedCommits($moduleDir)
                    ) {
                        $modules[$moduleName] = $moduleDir;
                    }
                }
            }
        }
        return $modules;
    }

    /**
     * Verifica se um diretório é um repositório Git.
     *
     * @param string $path
     * @return bool
     */
    protected function isGitRepository(string $path): bool
    {
        return File::exists($path . '/.git');
    }

    /**
     * Verifica se um repositório tem commits pendentes.
     *
     * @param string $path
     * @return bool
     */
    protected function hasPendingCommits(string $path): bool
    {
        $process = new Process(['git', 'status', '--porcelain'], $path);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return !empty(trim($process->getOutput()));
    }

    /**
     * Verifica se a branch atual tem commits que ainda não foram enviados (pushed) para o remoto.
     *
     * @param string $path
     * @return bool
     */
    protected function hasUnpushedCommits(string $path): bool
    {
        // PRIMEIRA VERIFICAÇÃO (RÁPIDA, OFFLINE):
        // Verifica se há commits locais à frente da última referência remota conhecida.
        // Se 0, não há nada para enviar, e podemos retornar false imediatamente.
        $processInitial = new Process(['git', 'rev-list', '@{u}..HEAD', '--count'], $path);
        $processInitial->run();

        if (!$processInitial->isSuccessful()) {
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
            if (!$processUpdate->isSuccessful()) {
                $this->warn("Falha ao sincronizar remoto para '{$path}' durante verificação de push: " . $processUpdate->getErrorOutput());
                // Se a sincronização falhar, somos conservadores e retornamos true para forçar verificação manual.
                return true;
            }
        } catch (ProcessFailedException $e) {
            $this->warn("Erro ao sincronizar remoto para '{$path}' durante verificação de push: " . $e->getMessage());
            return true;
        }

        // TERCEIRA VERIFICAÇÃO (RÁPIDA, OFFLINE, APÓS SINCRONIZAÇÃO):
        // Agora que o remoto está atualizado, fazemos a verificação final e precisa.
        $processFinal = new Process(['git', 'rev-list', '@{u}..HEAD', '--count'], $path);
        $processFinal->run();

        if (!$processFinal->isSuccessful()) {
            $this->warn("Não foi possível realizar a verificação final de commits não enviados para '{$path}'. Assumindo que existem.");
            return true;
        }

        $aheadCountFinal = (int) trim($processFinal->getOutput());

        return $aheadCountFinal > 0;
    }

    /**
     * Permite ao usuário selecionar múltiplos módulos.
     *
     * @param array $modules
     * @return array
     */
    protected function selectModules(array $modules): array
    {
        $options = [];
        foreach ($modules as $name => $path) {
            $options[] = $name;
        }

        // Usando multiSelect ao invés de choice com multiple choice
        return multiselect(
            label: 'Selecione os módulos para release:',
            options: $options
        );
    }

    /**
     * Processa a release para um módulo específico.
     *
     * @param string $moduleName
     * @return void
     */
    protected function processModuleRelease(string $moduleName): void
    {
        $modulePath = $this->modulesPath[0] . '/' . $moduleName; // Assuming single modules path for now

        $this->newLine();
        $this->info("Processando módulo: {$moduleName} em {$modulePath}");

        $currentBranch = $this->getCurrentBranch($modulePath);

        if ($currentBranch !== 'develop') {
            $this->warn("A branch atual de '{$moduleName}' é '{$currentBranch}'. Para iniciar uma release, é recomendado estar na branch 'develop'.");
            if (!$this->confirm("Deseja mudar para 'develop'? (Se 'não', o processo será abortado para este módulo)", true)) {
                $this->warn('cancelado');
                return;
            }
            $this->runProcess(['git', 'checkout', 'develop'], $modulePath);
        }

        $currentVersion = $this->getCurrentTag($modulePath);
        $this->info("Versão atual do módulo {$moduleName}: " . ($currentVersion ?: 'N/A'));

        $releaseType = $this->askForReleaseType($currentVersion);

        $newVersion = $this->calculateNewVersion($currentVersion, $releaseType);

        if (!$this->confirm("Deseja criar a tag '{$newVersion}' para o módulo '{$moduleName}'?", true)) {
            $this->warn("Release para '{$moduleName}' cancelada.");
            return;
        }

        $this->info("Iniciando release {$newVersion} para {$moduleName}...");
        $this->runProcess(['git', 'flow', 'release', 'start', $newVersion], $modulePath);

        if (!$this->confirm(
            "A branch de release 'release/{$newVersion}' foi iniciada para '{$moduleName}'. Deseja finalizar o release agora, ou você tem mais trabalhos (testes, documentação, etc.) a fazer antes de prosseguir com o merge e o push para o remoto?",
            true // Default para 'sim' para continuar o fluxo padrão
        )) {
            $this->warn("Finalização do release para '{$moduleName}' adiada. A branch 'release/{$newVersion}' permanece ativa. Você pode finalizá-la manualmente com 'git flow release finish {$newVersion}'.");
            return; // Interrompe o script para este módulo
        }

        $mergeMessage = $this->askForMergeMessage($newVersion);

        $this->info("Finalizando release {$newVersion} para {$moduleName}...");
        $this->runProcess(['git', 'checkout', 'main'], $modulePath);
        $this->runProcess(['git', 'pull'], $modulePath);
        $this->runProcess(['git', 'checkout', 'release/' . $newVersion], $modulePath);
        $this->runProcess(['git', 'flow', 'release', 'finish', $newVersion, '-m', $mergeMessage], $modulePath);

        $this->info("Release '{$newVersion}' finalizada com sucesso para o módulo '{$moduleName}'.");

        $this->info("Enviando alterações e tags para o repositório remoto...");
        $this->runProcess(['git', 'push', '--follow-tags', 'origin', 'develop', 'main'], $modulePath); // Envia develop e main e tags
        $this->info("Alterações e tags de release enviadas para o remoto para '{$moduleName}'.");

        $this->updateComposerDependency($moduleName, $modulePath, $newVersion);

        $this->cleanVendor();
    }

    /**
     * Executa um processo no terminal.
     *
     * @param array $command
     * @param string $cwd
     * @return void
     */
    protected function runProcess(array $command, string $cwd): void
    {
        $process = new Process($command, $cwd);
        $process->setTimeout(3600); // Aumenta o timeout para operações de git mais longas
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error('ERRO: ' . $buffer);
            } else {
                $this->line($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Obtém a branch atual de um repositório Git.
     *
     * @param string $path
     * @return string
     */
    protected function getCurrentBranch(string $path): string
    {
        $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $path);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        return trim($process->getOutput());
    }

    /**
     * Obtém a última tag de versão semântica.
     *
     * @param string $path
     * @return string|null
     */
    protected function getCurrentTag(string $path): ?string
    {
        $process = new Process(['git', 'describe', '--tags', '--abbrev=0', '--match', 'v*.*.*'], $path);
        $process->run();

        if (!$process->isSuccessful()) {
            // No tags found, return initial version
            return 'v0.0.0';
        }

        return trim($process->getOutput());
    }

    /**
     * Pergunta ao usuário o tipo de alteração da release.
     *
     * @param string|null $currentVersion
     * @return string
     */
    protected function askForReleaseType(?string $currentVersion): string
    {
        $currentVersion = ltrim($currentVersion, 'v');
        list($major, $minor, $patch) = array_pad(explode('.', $currentVersion), 3, 0);

        $options = [
            "major" => "Major (Nova versão não compatível): v" . ($major + 1) . ".0.0",
            "minor" => "Feature (Adição de funcionalidade): v" . $major . "." . ($minor + 1) . ".0",
            "patch" => "Patch (Correção de bugs/melhorias): v" . $major . "." . $minor . "." . ($patch + 1),
        ];

        return select(
            label: 'Qual o tipo de alteração para esta release?',
            options: array_values($options),
        );
    }

    /**
     * Calcula a nova versão semântica.
     *
     * @param string|null $currentVersion
     * @param string $releaseType
     * @return string
     */
    protected function calculateNewVersion(?string $currentVersion, string $releaseType): string
    {
        $currentVersion = ltrim($currentVersion, 'v');
        list($major, $minor, $patch) = array_pad(explode('.', $currentVersion), 3, 0);

        switch ($releaseType) {
            case (str_contains($releaseType, 'Major')):
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case (str_contains($releaseType, 'Feature')):
                $minor++;
                $patch = 0;
                break;
            case (str_contains($releaseType, 'Patch')):
                $patch++;
                break;
        }

        return "v{$major}.{$minor}.{$patch}";
    }

    /**
     * Pergunta a mensagem de merge para a release.
     *
     * @param string $newVersion
     * @return string
     */
    protected function askForMergeMessage(string $newVersion): string
    {
        $defaultMessage = "Release {$newVersion}";
        return $this->ask("Qual a mensagem para o merge da release? (Padrão: '{$defaultMessage}')", $defaultMessage);
    }

    /**
     * Atualiza a dependência do Composer no projeto principal.
     *
     * @param string $moduleName
     * @param string $newVersion
     * @return void
     */
    protected function updateComposerDependency(string $moduleName, string $modulePath, string $newVersion): void
    {
        // 1. Obter/confirmar o nome do pacote Composer
        $vendorPackageName = $this->getComposerPackageName($moduleName, $modulePath);

        // 2. Verificar se o módulo está ativo
        if (!$this->isModuleActive($moduleName)) {
            $this->info("Módulo '{$moduleName}' não está ativo. Pulando atualização da dependência Composer.");
            return;
        }

        // 3. Confirmar e executar a atualização do Composer
        if ($this->confirm("O módulo '{$moduleName}' parece estar ativo no projeto principal. Deseja atualizar sua dependência no composer.json para '{$vendorPackageName}:{$newVersion}'?", true)) {
            $this->info("Atualizando dependência do Composer para '{$vendorPackageName}:{$newVersion}'...");
            // Usamos 'sail' composer require, assumindo que você está usando Laravel Sail.
            // Se não estiver, apenas 'composer require'.
            $this->runProcess(['composer', 'require', "{$vendorPackageName}:{$newVersion}"], base_path());
            $this->info("Dependência do Composer atualizada com sucesso.");
        } else {
            $this->warn("Atualização da dependência Composer para '{$moduleName}' cancelada.");
        }
    }

    /**
     * Obtém o nome do pacote Composer de um módulo, preferencialmente do composer.json.
     * Caso contrário, infere o nome e pede confirmação ao usuário.
     *
     * @param string $moduleName O nome do diretório do módulo.
     * @param string $modulePath O caminho completo para o diretório do módulo.
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
                $this->warn("Não foi possível ler ou parsear o composer.json de '{$moduleName}'. Erro: " . $e->getMessage());
            }
        }

        if (empty($vendorPackageName)) {
            $inferredName = 'vendor/' . strtolower($moduleName) . '-module'; // Sua convenção
            $this->info("Não foi possível encontrar o nome do pacote no composer.json do módulo '{$moduleName}'.");
            $vendorPackageName = $this->ask(
                "Por favor, confirme o nome do pacote Composer para '{$moduleName}' (inferido: {$inferredName}):",
                $inferredName
            );
        } else {
            $vendorPackageName = $this->ask(
                "Confirmar o nome do pacote Composer para '{$moduleName}' (encontrado: {$vendorPackageName}):",
                $vendorPackageName
            );
        }

        return $vendorPackageName;
    }

    /**
     * Verifica se um módulo está ativo usando o pacote nwidart/laravel-modules.
     *
     * @param string $moduleName O nome do módulo (ex: 'BlogModule').
     * @return bool
     */
    protected function isModuleActive(string $moduleName): bool
    {
        // Certifique-se de que o pacote nwidart/laravel-modules está instalado
        // e que o facade Module está registrado ou o contract Repository pode ser resolvido.
        if (!class_exists(Module::class) && !interface_exists(RepositoryInterface::class)) {
            $this->warn('O pacote nwidart/laravel-modules não parece estar instalado ou configurado. Não é possível verificar o status do módulo. Assumindo inativo para segurança.');
            return false;
        }

        // No Laravel 10+, é comum usar o Facade.
        // Para versões anteriores ou injeção de dependência, você injetaria `Nwidart\Modules\Contracts\Repository`.
        return \Module::isEnabled($moduleName);
    }


    /**
     * Remove projetos com sufixo "-module" .
     *
     * @return void
     */
    protected function cleanVendor(): void
    {
        // Certifica-se de que estamos em ambiente de desenvolvimento
        if (app()->environment('production', 'staging', 'testing')) {
            $this->info("Não removendo módulos da pasta vendor em ambiente de " . app()->environment() . ".");
            return;
        }

        $this->info("Iniciando limpeza dos módulos locais da pasta vendor...");

        // Supondo que você tem uma propriedade ou método para obter o caminho da pasta Modules
        // Exemplo: $this->modulesPath (assumindo que já está definido e contém o caminho base)
        $modulesRootPath = $this->modulesPath[0] ?? base_path('Modules'); // Garanta que este caminho está correto

        if (!File::isDirectory($modulesRootPath)) {
            $this->warn("Diretório de módulos '{$modulesRootPath}' não encontrado. Pulando limpeza da pasta vendor.");
            return;
        }

        $moduleDirectories = File::directories($modulesRootPath);

        if (empty($moduleDirectories)) {
            $this->info("Nenhum módulo encontrado em '{$modulesRootPath}'. Nenhuma limpeza necessária.");
            return;
        }

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
                    $this->warn("Não foi possível ler ou parsear o composer.json de '{$moduleName}'. Erro: " . $e->getMessage());
                    // Se não conseguir ler o composer.json, pula este módulo
                    continue;
                }
            }

            if (empty($vendorPackageName)) {
                $this->warn("Não foi possível determinar o nome do pacote Composer para o módulo '{$moduleName}'. Pulando remoção da pasta vendor.");
                continue;
            }

            list($vendorPrefix, $packageName) = explode('/', $vendorPackageName, 2);
            $fullPathInVendor = base_path("vendor/{$vendorPrefix}/{$packageName}");

            if (File::isDirectory($fullPathInVendor)) {
                $this->info("Removendo '{$vendorPackageName}' de '{$fullPathInVendor}' para evitar duplicidade.");
                File::deleteDirectory($fullPathInVendor);
            } else {
                $this->info("Diretório de vendor para '{$vendorPackageName}' ('{$fullPathInVendor}') não encontrado ou já removido.");
            }
        }
        $this->info("Limpeza da pasta vendor concluída.");
    }
}
