<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Robo;

use Consolidation\Log\ConsoleLogLevel;
use Mwltr\MageDeploy2\Config\Config;
use Mwltr\MageDeploy2\Config\ConfigAwareTrait;
use Mwltr\MageDeploy2\Robo\Task\GenerateConfigFileTask;
use Mwltr\MageDeploy2\Robo\Task\ValidateEnvironmentTask;
use Mwltr\Robo\Deployer\loadDeployerTasks;
use Mwltr\Robo\Magento2\loadMagentoTasks;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\Timer as RoboTimer;

/**
 * RoboTasks
 */
class RoboTasks extends \Robo\Tasks implements LoggerAwareInterface
{
    const MAGENTO_VENDOR_DIR = 'vendor';
    const MAGENTO_PATH_ENV_PHP = 'app/etc/env.php';

    use ConfigAwareTrait;
    use loadDeployerTasks;
    use loadMagentoTasks;

    use RoboTimer;
    use LoggerAwareTrait;

    protected $dotEnvOverload = false;

    /**
     * RoboTasks constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize Config
     */
    protected function init()
    {
        $this->initEnvironment();
        $this->initDeployConfig();

        $this->stopOnFail(true);
    }

    /**
     * Initialize Environment variables by .env file
     *
     * Overload can be activated by setting $this->dotEnvOverload = true; in your RoboFile
     */
    protected function initEnvironment()
    {
        $path = getcwd();
        if (!is_file("$path/.env")) {
            return;
        }
        $dotenv = new \Dotenv\Dotenv($path);
        if ($this->dotEnvOverload) {
            $dotenv->overload();
        } else {
            $dotenv->load();
        }
    }

    /**
     * @return GenerateConfigFileTask
     */
    protected function taskGenerateConfigFile()
    {
        return $this->createTask(GenerateConfigFileTask::class);
    }

    /**
     * @return ValidateEnvironmentTask
     */
    protected function taskDeployValidate()
    {
        return $this->createTask(ValidateEnvironmentTask::class);
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
        $gitBin = $this->config(Config::KEY_ENV . '/' . Config::KEY_GIT_BIN);

        /** @var RoboFile|CollectionBuilder $collection */
        $collection = $this->collectionBuilder();

        $isFirstRun = !is_dir($gitDir);
        if ($isFirstRun) {
            // Clone git Repo
            $cloneRepo = $this->taskGitStack($gitBin)->cloneRepo($repo, $gitDir);
            $cloneRepo->run();
        }

        // Fetch origin
        $collection->taskGitStack($gitBin)->dir($gitDir)->exec(['fetch', '-vp', 'origin']);

        // Gather Tag information
        $tagRefs = [];
        // use exec since the output from the task cannot be accessed anymore since AUG-2017
        exec("$gitBin -C $gitDir show-ref --tags", $tagRefs);

        $tags = [];
        foreach ($tagRefs as $tagRefData) {
            if (empty($tagRefData)) {
                continue;
            }
            $tagData = explode('/', $tagRefData);
            $tag = array_pop($tagData);
            $tags[$tag] = $tag;
        }

        $isTag = array_key_exists($branch, $tags);

        // Checkout branch or tag
        $collection->taskGitStack($gitBin)->dir($gitDir)->exec(['checkout', '-f', $branch]);

        // Reset to origin Branch / Tag
        $resetTo = $isTag ? $branch : "origin/$branch";

        $collection->taskGitStack($gitBin)->dir($gitDir)->exec(['reset', '--hard', $resetTo]);

        $collection->taskGitStack($gitBin)->dir($gitDir)->exec(['status']);

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

        return $this->taskCleanDir($dirs);
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

        /** @var RoboFile|CollectionBuilder $collection */
        $collection = $this->collectionBuilder();
        if ($dropVendor === true) {
            $vendorDir = self::MAGENTO_VENDOR_DIR;
            $dir = "$magentoDir/$vendorDir";
            $collection->taskDeleteDir($dir);
        }

        $task = $collection->taskComposerInstall($pathToComposer);
        $task->dir($magentoDir);
        $task->noDev();
        $task->optimizeAutoloader();

        return $collection;
    }

    /**
     * Build Task to create database
     *
     * @param bool $dropDatabase
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function taskMysqlCreateDatabase($dropDatabase = false)
    {
        $dbName = $this->config(CONFIG::KEY_BUILD . '/' . Config::KEY_DB . '/db-name');

        $sqlDropDb = "DROP DATABASE `{$dbName}`";
        $sqlCreateDb = "CREATE DATABASE IF NOT EXISTS `{$dbName}`";

        /** @var RoboFile|CollectionBuilder $collection */
        $collection = $this->collectionBuilder();

        // Skip database drop and create incase mysql_bin is not set
        $mysqlBin = $this->config(Config::KEY_ENV . '/' . Config::KEY_MYSQL_BIN);
        if (empty($mysqlBin)) {
            $collection->progressMessage('mySQL database managing skipped');

            return $collection;
        }

        // Drop Database
        if ($dropDatabase === true) {
            $taskDropDatabase = $this->taskMysqlCommand();
            $taskDropDatabase->option('-e', $sqlDropDb);

            $collection->progressMessage('Drop Database (as requested)');
            $collection->addTask($taskDropDatabase);
        }

        // Create DB
        $createDatabase = $this->taskMysqlCommand();
        $createDatabase->option('-e', $sqlCreateDb);

        $collection->progressMessage('Create Database');
        $collection->addTask($createDatabase);

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

        /** @var RoboFile|CollectionBuilder $collection */
        $collection = $this->collectionBuilder();

        $hasEnvPhp = is_file($envPhpFile);

        // Reinstall Project
        if ($reinstallProject === true && $hasEnvPhp) {
            $taskDeleteEnvPhp = $collection->taskFilesystemStack();
            $taskDeleteEnvPhp->remove($envPhpFile);

            $collection->progressMessage("delete $envPhpFile");
            $hasEnvPhp = false;
        }

        if (!$hasEnvPhp) {
            $options = $this->config(CONFIG::KEY_BUILD . '/' . Config::KEY_DB);

            $installTask = $collection->taskMagentoSetupInstallTask();
            $installTask->options($options);
            $installTask->dir($magentoDir);
        }

        $upgradeTask = $collection->taskMagentoSetupUpgradeTask();
        $upgradeTask->dir($magentoDir);

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
     * Build task to update dependencies using composer
     *
     * @param bool $dropVendor
     *
     * @return \Robo\Collection\CollectionBuilder
     */
    protected function taskMagentoDumpAutoload()
    {
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);
        $pathToComposer = $this->config(Config::KEY_ENV . '/' . Config::KEY_COMPOSER_BIN);

        /** @var RoboFile|CollectionBuilder $collection */
        $collection = $this->collectionBuilder();

        $task = $collection->taskComposerDumpAutoload($pathToComposer);
        $task->dir($magentoDir);
        $task->noDev();
        $task->optimize();

        // composer dump-autoload --no-dev --optimize

        return $collection;
    }

    /**
     * Build Task to run magento setup static content deploy
     *
     * @return \Robo\Collection\CollectionBuilder
     * @throws \RuntimeException
     */
    protected function taskMagentoSetupStaticContentDeploy()
    {
        /** @var array $themes */
        $themes = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_THEMES);
        $magentoDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_APP_DIR);

        /** @var RoboFile|CollectionBuilder $collection */
        $collection = $this->collectionBuilder();
        foreach ($themes as $theme) {
            if (!array_key_exists('code', $theme)) {
                throw new \RuntimeException('invalid theme config: code is missing');
            }

            $task = $collection->taskMagentoSetupStaticContentDeployTask();
            $task->addTheme($theme['code']);
            $task->addLanguages($theme['languages']);
            $task->jobs(16);
            $task->dir($magentoDir);
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
        $rootDir = getcwd();
        $tarBin = $this->config(Config::KEY_ENV . '/' . Config::KEY_TAR_BIN);
        $gitDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_DIR);
        $artifactsDir = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_ARTIFACTS_DIR);

        /** @var array $artifacts */
        $artifacts = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_ARTIFACTS);

        /** @var RoboFile|CollectionBuilder $collection */
        $collection = $this->collectionBuilder();
        $collection->progressMessage('cleanup old packages');

        // Ensure artifacts-dir is present
        $collection->taskFilesystemStack()->mkdir($artifactsDir);

        // Cleanup old tars
        foreach ($artifacts as $artifactName => $artifactConfig) {
            $file = "$artifactsDir/$artifactName";

            $task = $collection->taskFilesystemStack();
            $task->remove($file);
        }

        // Create Tars
        $collection->progressMessage('creating packages');
        foreach ($artifacts as $artifactName => $artifactConfig) {
            $dir = $artifactConfig['dir'];

            $artifactPath = "$rootDir/$artifactsDir/$artifactName";

            $tarOptions = '';
            if (array_key_exists('options', $artifactConfig)) {
                $options = $artifactConfig['options'];
                $tarOptions = implode(' ', $options);
            }

            $tarCmd = "$tarBin $tarOptions -czf $artifactPath $dir";
            $task = $collection->taskExec($tarCmd);
            $task->dir($gitDir);
        }

        return $collection;
    }

    /**
     * Build Task for deployer deploy
     *
     * @param string $stage
     * @param string $branchOrTag
     *
     * @return \Mwltr\Robo\Deployer\Task\DeployTask
     */
    protected function taskDeployerDeploy($stage, $branchOrTag)
    {
        $deployerBin = $this->config(Config::KEY_ENV . '/' . Config::KEY_DEPLOYER_BIN);

        $isTagDeploy = $this->isTag($branchOrTag);

        $task = $this->taskDeployerDeployTask($deployerBin);

        if ($isTagDeploy) {
            $task->tag($branchOrTag);
        } else {
            $task->branch($branchOrTag);
        }

        $task->stage($stage);

        // Map Verbosity
        $output = $this->output();
        if ($output->isDebug()) {
            $task->debug();
        } elseif ($output->isVeryVerbose()) {
            $task->veryVerbose();
        } elseif ($output->isVerbose()) {
            $task->verbose();
        } elseif ($output->isQuiet()) {
            $task->quiet();
        }

        return $task;
    }

    /**
     * Create a mysql Command
     *
     * @return \Robo\Task\Base\Exec
     */
    protected function taskMysqlCommand()
    {
        $mysqlBin = $this->config(Config::KEY_ENV . '/' . Config::KEY_MYSQL_BIN);
        $dbHost = $this->config(CONFIG::KEY_BUILD . '/' . Config::KEY_DB . '/db-host');
        $dbUser = $this->config(CONFIG::KEY_BUILD . '/' . Config::KEY_DB . '/db-user');
        $dbPass = $this->config(CONFIG::KEY_BUILD . '/' . Config::KEY_DB . '/db-password');

        $hostParts = explode(':', $dbHost);
        $dbPort = null;
        if (count($hostParts) === 2) {
            $dbPort = array_pop($hostParts);
            $dbHost = array_pop($hostParts);
        }

        $createDatabase = $this->taskExec($mysqlBin);
        $createDatabase->option('-h', $dbHost);

        if ($dbPort !== null) {
            $createDatabase->option('-P', $dbPort);
        }

        $createDatabase->option('-u', $dbUser);
        if ($dbPass) {
            $createDatabase->option("-p$dbPass");
        }

        return $createDatabase;
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

    /**
     * Create a Task and transfer input and output instances
     *
     * This is needed if you have task that needs the current input / output
     *
     * @return \Robo\Contract\TaskInterface
     */
    protected function createTask()
    {
        $task = call_user_func_array(['parent', 'task'], func_get_args());
        $task->setInput($this->input());
        $task->setOutput($this->output());

        return $task;
    }

    /**
     * @param string $branchOrTag
     * @return bool
     */
    protected function isTag($branchOrTag)
    {
        $tagList = $this->getTagList();

        return in_array($branchOrTag, $tagList);
    }

    /**
     * @return array
     */
    protected function getTagList()
    {
        $gitBin = $this->config(Config::KEY_ENV . '/' . Config::KEY_GIT_BIN);

        $gitTask = $this->taskExec($gitBin);
        $gitTask->printOutput(false);
        $gitTask->arg('--no-pager');
        $gitTask->arg('tag');
        $gitTask->option('-l');
        $gitTaskResult = $gitTask->run();
        $rawTagList = $gitTaskResult->getMessage();

        return explode("\n", $rawTagList);
    }
}
