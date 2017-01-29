<?php

namespace Mwltr\MageDeploy2\Robo;

use Consolidation\Log\ConsoleLogLevel;
use Psr\Log\LoggerAwareInterface;

/**
 * RoboTasks
 */
class RoboTasks extends \Robo\Tasks implements LoggerAwareInterface
{
    const MAGENTO_VENDOR_DIR = "vendor";
    const MAGENTO_PATH_ENV_PHP = "app/etc/env.php";

    use \Mwltr\MageDeploy2\Config\ConfigAwareTrait;
    use \Mwltr\Robo\Deployer\loadDeployerTasks;
    use \Mwltr\Robo\Magento2\loadMagentoTasks;

    use \Robo\Common\Timer;
    use \Psr\Log\LoggerAwareTrait;

    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize Config
     */
    protected function init()
    {
        $this->initDeployConfig();

        $this->stopOnFail(true);
    }

    /**
     * Build Task to update source code to desired branch/tag
     *
     * @param $branch
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function taskUpdateSourceCode($branch)
    {
        $repo = $this->config('deploy/git_url');
        $gitDir = $this->config('deploy/git_dir');

        $collection = $this->collectionBuilder();

        if (!is_dir($gitDir)) {
            $task = $this->taskGitStack();
            $task->cloneRepo($repo, $gitDir);

            $collection->addTask($task);

        } else {
            $task = $this->taskGitStack();
            $task->dir($gitDir);
            $task->exec(['fetch', '-vp', 'origin']);
            $collection->addTask($task);

            $task = $this->taskGitStack();
            $task->dir($gitDir);
            $task->exec(['checkout', '-f', $branch]);

            $collection->addTask($task);
            $task = $this->taskGitStack();
            $task->dir($gitDir);
            $task->exec(['reset', '--hard', $branch]);
            // @todo check if it is a branch or tag
            // exec("git reset --hard origin/$branch");

            $collection->addTask($task);
        }

        return $collection;
    }

    /**
     * Build Task to clean all var dirs
     *
     * @return \Robo\Task\Filesystem\CleanDir
     */
    protected function taskMagentoCleanVarDirs()
    {
        $magentoDir = $this->config('deploy/magento_dir');
        $varDirs = $this->config('deploy/clean_dirs');

        $dirs = [];
        foreach ($varDirs as $dir) {
            $dirPath = $dir;
            if (!empty($magentoDir)) {
                $dirPath = "{$magentoDir}/$dir";
            }
            if (!is_dir($dirPath)) {
                $this->logger->notice("File or directory <info>$dirPath</info> does not exist!");
                continue;
            }
            $dirs[] = $dirPath;
        }

        $task = $this->taskCleanDir($dirs);

        return $task;
    }

    /**
     * Build task to update dependencies using composer
     *
     * @param bool $dropVendor
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function taskMagentoUpdateDependencies($dropVendor = false)
    {
        $magentoDir = $this->config('deploy/magento_dir');
        $pathToComposer = $this->config('env/composer_bin');

        $collection = $this->collectionBuilder();
        if ($dropVendor === true) {
            $magentoDir = $this->config('deploy/magento_dir');
            $vendorDir = self::MAGENTO_VENDOR_DIR;
            $dir = "$magentoDir/$vendorDir";
            $task = $this->taskDeleteDir($dir);

            $collection->addTask($task);
        }

        $task = $this->taskComposerInstall($pathToComposer);
        $task->dir($magentoDir);
        $task->noDev();
        $task->optimizeAutoloader();

        $collection->addTask($task);

        return $collection;
    }

    /**
     * Build Task to setup / upgrade Magento database
     *
     * @param bool $reinstallProject
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function taskMagentoSetup($reinstallProject = false)
    {
        $magentoDir = $this->config('deploy/magento_dir');
        $pathEnvPhp = self::MAGENTO_PATH_ENV_PHP;
        $envPhpFile = "$magentoDir/$pathEnvPhp";

        $collection = $this->collectionBuilder();

        $hasEnvPhp = is_file($envPhpFile);

        // Reinstall Project
        if ($reinstallProject === true && $hasEnvPhp) {
            $taskDeleteEnvPhp = $this->taskFilesystemStack();
            $taskDeleteEnvPhp->remove($envPhpFile);

            $collection->progressMessage("delete $envPhpFile");
            $collection->addTask($taskDeleteEnvPhp);
            $hasEnvPhp = false;
        }

        if (!$hasEnvPhp) {
            $options = $this->config('magento/db');

            $task = $this->taskMagentoSetupInstallTask();
            $task->options($options);
            $task->dir($magentoDir);
            $collection->addTask($task);
        }

        $task = $this->taskMagentoSetupUpgradeTask();
        $task->dir($magentoDir);
        $collection->addTask($task);

        return $collection;
    }

    /**
     * Build Task to set magento to production mode
     *
     * @return \Mwltr\Robo\Magento2\Task\DeploySetModeTask
     */
    protected function taskMagentoSetProductionMode()
    {
        $magentoDir = $this->config('deploy/magento_dir');

        $task = $this->taskMagentoDeploySetModeTask();
        $task->modeProduction();
        $task->skipCompilation();
        $task->dir($magentoDir);

        return $task;
    }

    /**
     * Build Task to run magento setup di compile
     *
     * @return \Mwltr\Robo\Magento2\Task\SetupDiCompileTask
     */
    protected function taskMagentoSetupDiCompile()
    {
        $magentoDir = $this->config('deploy/magento_dir');

        $task = $this->taskMagentoSetupDiCompileTask();
        $task->dir($magentoDir);

        return $task;
    }

    /**
     * Build Task to run magento setup static content deploy
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function taskMagentoSetupStaticContentDeploy()
    {
        $magentoDir = $this->config('deploy/magento_dir');
        $themes = $this->config('deploy/themes');

        $collection = $this->collectionBuilder();
        foreach ($themes as $theme => $languages) {
            $task = $this->taskMagentoSetupStaticContentDeployTask();
            $task->addTheme($theme);
            $task->addLanguages($languages);
            $task->dir($magentoDir);

            $collection->addTask($task);
        }

        return $collection;
    }

    /**
     * Build Task for artifact creation
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function taskArtifactCreatePackages()
    {
        $tarBin = $this->config('env/tar_bin');
        $magentoDir = $this->config('deploy/magento_dir');
        $assets = $this->config('deploy/assets');

        $collection = $this->collectionBuilder();
        $collection->progressMessage('cleanup old packages');

        // Cleanup old tars
        foreach ($assets as $assetName => $assetConfig) {
            $file = "$magentoDir/$assetName";

            $task = $this->taskFilesystemStack();
            $task->remove($file);

            $collection->addTask($task);
        }

        // Create Tars
        $collection->progressMessage('creating packages');
        foreach ($assets as $assetName => $assetConfig) {
            $dir = $assetConfig['dir'];

            $tarOptions = '';
            if (array_key_exists('options', $assetConfig)) {
                $options = $assetConfig['options'];

                $tarOptions = implode(' ', $options);
            }

            $tarCmd = "$tarBin $tarOptions czf $assetName $dir";
            $task = $this->taskExec($tarCmd);
            $task->dir($magentoDir);

            $collection->addTask($task);
        }

        return $collection;
    }

    /**
     * Build Task for deployer deploy
     *
     * @param string $stage
     * @param string $branch
     *
     * @return \Mwltr\Robo\Deployer\Task\DeployTask
     */
    protected function taskDeployerDeploy($stage, $branch)
    {
        $deployerBin = $this->config('env/deployer_bin');

        $task = $this->taskDeployerDeployTask($deployerBin);
        $task->branch($branch);
        $task->stage($stage);

        return $task;
    }

    /**
     * Print Stage Starting Information
     *
     * @param string $msg
     */
    protected function printStageInfo($msg)
    {
        $this->yell($msg, 80, 'red');
    }

    /**
     * Print Task Starting Information
     *
     * @param string $msg
     */
    protected function printTaskInfo($msg)
    {
        $this->yell($msg, 80, 'blue');
    }

    /**
     * Print Runtime
     *
     * @param string $method
     */
    protected function printRuntime($method)
    {
        $context = [
            'time' => $this->getExecutionTime(),
            'timer-label' => "in",
        ];
        $this->logger->log(ConsoleLogLevel::SUCCESS, "<info>$method</info> finished", $context);
    }

}