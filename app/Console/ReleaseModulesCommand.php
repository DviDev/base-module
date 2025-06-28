<?php

namespace Modules\Base\Console;

use Illuminate\Console\Command;
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
        dd($newVersion);

        $this->info("Iniciando release {$newVersion} para {$moduleName}...");
        $this->runProcess(['git', 'flow', 'release', 'start', $newVersion], $modulePath);

        $mergeMessage = $this->askForMergeMessage($newVersion);

        $this->info("Finalizando release {$newVersion} para {$moduleName}...");
        $this->runProcess(['git', 'flow', 'release', 'finish', $newVersion, '-m', $mergeMessage], $modulePath);

        $this->info("Release '{$newVersion}' finalizada com sucesso para o módulo '{$moduleName}'.");

        $this->updateComposerDependency($moduleName, $newVersion);

        $this->cleanVendor($modulePath);
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
    protected function updateComposerDependency(string $moduleName, string $newVersion): void
    {
        // O modules_statuses.json é um arquivo comum para rastrear módulos.
        // Você precisa adaptar o caminho e o formato do arquivo para o seu projeto.
        $modulesStatusFile = base_path('modules_statuses.json');

        if (File::exists($modulesStatusFile)) {
            $statuses = json_decode(File::get($modulesStatusFile), true);

            // Adapte esta lógica para como o nome do seu pacote Composer é
            // Ex: Se seu módulo "Blog" se torna "vendor/blog-module"
            $vendorPackageName = 'vendor/' . strtolower($moduleName) . '-module';

            if (isset($statuses[$moduleName]) && $statuses[$moduleName] === true) { // Supondo que 'true' significa ativo
                if ($this->confirm("O módulo '{$moduleName}' parece estar ativo no projeto principal. Deseja atualizar sua dependência no composer.json para '{$newVersion}'?", true)) {
                    $this->info("Atualizando dependência do Composer para '{$vendorPackageName}:{$newVersion}'...");
                    $this->runProcess(['sail', 'composer', 'require', "{$vendorPackageName}:{$newVersion}"], base_path());
                    $this->info("Dependência do Composer atualizada com sucesso.");
                }
            }
        }
    }

    /**
     * Remove projetos com sufixo "-module" de vendor/dvidev.
     *
     * @param string $modulePath
     * @return void
     */
    protected function cleanVendor(string $modulePath): void
    {
        $dvidevPath = $modulePath . '/vendor/dvidev';

        if (File::exists($dvidevPath) && File::isDirectory($dvidevPath)) {
            $this->info("Verificando e removendo módulos obsoletos em {$dvidevPath}...");
            foreach (File::directories($dvidevPath) as $packageDir) {
                $packageName = basename($packageDir);
                if (str_ends_with($packageName, '-module')) {
                    $this->warn("Removendo diretório obsoleto: {$packageDir}");
                    File::deleteDirectory($packageDir);
                }
            }
        }
    }
}
