<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Robo;

use Consolidation\Log\ConsoleLogLevel;
use Mwltr\MageDeploy2\Config\Config;
use Mwltr\MageDeploy2\Config\ConfigWriter;
use Mwltr\MageDeploy2\Robo\Task\ValidateEnvironmentTask;
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

    protected function generateMageDeploy2Config()
    {
        // Gather Config information
        $this->say('environment configuration');
        $gitBin = $this->askDefault('git_bin', '/usr/local/bin/git');
        $phpBin = $this->askDefault('php_bin', '/usr/local/bin/php');
        $tarBin = $this->askDefault('tar_bin', '/usr/local/bin/gtar');
        $composerBin = $this->askDefault('composer_bin', '/usr/local/bin/composer.phar');
        $deployerBin = $this->askDefault('deployer_bin', '/usr/local/bin/deployer.phar');

        $this->say('deploy configuration');
        $gitUrl = $this->ask('git-url');
        $appSubDir = '';
        $gitHasSubDir = $this->askDefault('Is your app composer.json in the root of the vcs', 'y');
        if ($gitHasSubDir == 'n') {
            $appSubDir = $this->askDefault('sub-dir', 'src');
        }

        $this->say('Enter themes to compile for this deployment');
        $askForTheme = true;
        $themes = [];
        while ($askForTheme === true) {
            $themeCode = $this->askDefault('theme', 'Magento/backend');
            $themeLang = $this->askDefault('languages', 'en_US,de_DE');
            $continue = $this->askDefault('add another theme? (y/n)', 'n');
            $themes[] = [
                'code' => $themeCode,
                'languages' => explode(',', $themeLang),
            ];
            if ($continue == 'n') {
                $askForTheme = false;
            }
        }

        $this->say('Enter database configuration for build environment');
        $dbHost = $this->askDefault('db-host', '127.0.0.1');
        $dbName = $this->askDefault('db-name', '');
        $dbUser = $this->askDefault('db-user', 'root');
        $dbPw = $this->askDefault('db-password', '');

        // Create Config data object
        $config = new Config();
        $config->set(Config::KEY_ENV . '/' . Config::KEY_GIT_BIN, $gitBin);
        $config->set(Config::KEY_ENV . '/' . Config::KEY_PHP_BIN, $phpBin);
        $config->set(Config::KEY_ENV . '/' . Config::KEY_TAR_BIN, $tarBin);
        $config->set(Config::KEY_ENV . '/' . Config::KEY_COMPOSER_BIN, $composerBin);
        $config->set(Config::KEY_ENV . '/' . Config::KEY_DEPLOYER_BIN, $deployerBin);

        $config->set(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_URL, $gitUrl);
        if (!empty($appSubDir)) {
            $appdir = $config->get(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR) . '/' . $appSubDir;
            $config->set(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR, $appdir);
        }
        $config->set(Config::KEY_DEPLOY . '/' . Config::KEY_THEMES, $themes);

        $pathBuildDb = Config::KEY_BUILD . '/' . Config::KEY_DB . '/';
        $config->set($pathBuildDb . 'db-host', $dbHost);
        $config->set($pathBuildDb . 'db-name', $dbName);
        $config->set($pathBuildDb . 'db-user', $dbUser);
        $config->set($pathBuildDb . 'db-password', $dbPw);

        // Write Config to file
        $configWriter = new ConfigWriter();
        $configWriter->write($config);
    }

    /**
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function taskDeployCheck()
    {
        return $this->task(ValidateEnvironmentTask::class);
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
        $repo = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_URL);
        $gitDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_DIR);

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
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);
        $varDirs = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_CLEAN_DIRS);

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
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);
        $pathToComposer = $this->config(Config::KEY_ENV . '/' . Config::KEY_COMPOSER_BIN);

        $collection = $this->collectionBuilder();
        if ($dropVendor === true) {
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
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);
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
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);

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
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);

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
        /** @var array $themes */
        $themes = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_THEMES);
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);

        $collection = $this->collectionBuilder();
        foreach ($themes as $theme) {
            if (!array_key_exists('code', $theme)) {
                throw new \RuntimeException('invalid theme config: code is missing');
            }

            $task = $this->taskMagentoSetupStaticContentDeployTask();
            $task->addTheme($theme['code']);
            $task->addLanguages($theme['languages']);
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
        $tarBin = $this->config(Config::KEY_ENV . '/' . Config::KEY_TAR_BIN);
        $gitDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_DIR);
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);

        /** @var array $assets */
        $assets = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_ASSETS);

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

            $tarCmd = "$tarBin $tarOptions -czf $assetName $dir";
            $task = $this->taskExec($tarCmd);
            $task->dir($gitDir);

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
        $deployerBin = $this->config(Config::KEY_ENV . '/' . Config::KEY_DEPLOYER_BIN);

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
            'timer-label' => 'in',
        ];
        $this->logger->log(ConsoleLogLevel::SUCCESS, "<info>$method</info> finished", $context);
    }

}