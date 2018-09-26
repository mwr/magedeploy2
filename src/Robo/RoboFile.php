<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Robo;

use Psr\Log\LoggerAwareInterface;

/**
 * RoboFile
 */
class RoboFile extends RoboTasks implements LoggerAwareInterface
{
    const OPT_REINSTALL_PROJECT = 'reinstall-project';
    const OPT_DROP_VENDOR       = 'drop-vendor';
    const OPT_DROP_DATABASE     = 'drop-database';
    const OPT_DEPLOYER_PARALLEL = 'parallel|p';

    /**
     * command to trigger deployment process completly
     *
     * @param string $stage Environment to deploy to (by default: local/staging/production)
     * @param string $branchOrTag Branch or Tag to deploy
     * @param string $revision
     * @param array $opts
     *
     * @option $reinstall-project Reinstall project by deleting env.php file and running setup:install
     * @option $drop-vendor Remove the vendor directory
     * @option $drop-database Drop the Database
     * @option $parallel Parallel deployment mode for deployer
     */
    public function deploy(
        $stage,
        $branchOrTag,
        $revision = '',
        $opts = [
            self::OPT_REINSTALL_PROJECT => false,
            self::OPT_DROP_VENDOR       => false,
            self::OPT_DROP_DATABASE     => false,
            self::OPT_DEPLOYER_PARALLEL => false,
        ]
    ) {
        $this->startTimer();

        $this->deployMagentoSetup($branchOrTag, $revision, $opts);

        $this->deployArtifactsGenerate();

        $this->deployDeploy($stage, $branchOrTag, $opts);

        $this->stopTimer();
        $this->printRuntime(__FUNCTION__);
    }

    /**
     * Validate the deploy setup configuration and executable
     */
    public function validate()
    {
        $this->taskDeployValidate()->run();
    }

    /**
     * Initialize the configuration file
     */
    public function configInit()
    {
        $this->taskGenerateConfigFile()->run();
    }

    /**
     * STAGE command to setup / update Magento and its dependencies
     *
     * @param string $branchOrTag Branch or Tag to deploy
     * @param string $revision
     * @param array $opts
     *
     * @option $parallel activate parallel deployment mode for deployer
     */
    public function deployMagentoSetup(
        $branchOrTag,
        $revision = '',
        $opts = [
            self::OPT_REINSTALL_PROJECT => false,
            self::OPT_DROP_VENDOR       => false,
            self::OPT_DROP_DATABASE     => false,
        ]
    ) {
        $this->startTimer();

        // options are always set (Robo)
        $reinstallProject = $opts[self::OPT_REINSTALL_PROJECT];
        $dropVendor       = $opts[self::OPT_DROP_VENDOR];
        $dropDatabase     = $opts[self::OPT_DROP_DATABASE];
        if ($dropDatabase === true) {
            $reinstallProject = true;
        }

        $this->printStageInfo('MAGENTO SETUP');

        $this->printTaskInfo('UPDATE SOURCE CODE');
        $this->taskUpdateSourceCode($branchOrTag, $revision)->run();

        $this->printTaskInfo('CLEAN VAR DIRS');
        $this->taskMagentoCleanVarDirs()->run();

        $this->printTaskInfo('UPDATE COMPOSER');
        $this->taskMagentoUpdateDependencies($dropVendor)->run();

        $this->printTaskInfo('MYSQL PREPARE DATABASE');
        $this->taskMysqlCreateDatabase($dropDatabase)->run();

        $this->printTaskInfo('MAGENTO INSTALL / UPGRADE');
        $this->taskMagentoSetup($reinstallProject)->run();

        $this->stopTimer();
        $this->printRuntime(__FUNCTION__);
    }

    /**
     * STAGE command that generates all artifacts for the deploy
     */
    public function deployArtifactsGenerate()
    {
        $this->startTimer();

        $this->printStageInfo('GENERATE ASSETS');

        $this->printTaskInfo('SET PRODUCTION MODE');
        $this->taskMagentoSetProductionMode()->run();

        $this->printTaskInfo('MAGENTO SETUP DI COMPILE');
        $this->taskMagentoSetupDiCompile()->run();

        $this->printTaskInfo('COMPOSER DUMP AUTOLOAD');
        $this->taskMagentoDumpAutoload()->run();

        $this->printTaskInfo('MAGENTO SETUP STATIC CONTENT DEPLOY');
        $this->taskMagentoSetupStaticContentDeploy()->run();

        $this->printTaskInfo('GENERATE ARTIFACTS');
        $this->taskArtifactCreatePackages()->run();

        $this->stopTimer();
        $this->printRuntime(__FUNCTION__);
    }

    /**
     * STAGE command triggering release to server using deployer
     *
     * @param string $stage Environment to deploy to (by default: local/staging/production)
     * @param string $branchOrTag Branch or Tag to deploy
     * @param array $opts
     */
    public function deployDeploy(
        $stage,
        $branchOrTag,
        $opts = [
            self::OPT_DEPLOYER_PARALLEL => false,
        ]
    ) {
        // use string parallel due as robo always submits this string for both triggers
        $parallelMode = $opts['parallel'];

        $this->startTimer();

        $this->printStageInfo('DEPLOYER DEPLOY');

        $task = $this->taskDeployerDeploy($stage, $branchOrTag);
        if ($parallelMode === true) {
            $task->parallel();
        }

        $task->run();

        $this->stopTimer();
        $this->printRuntime(__FUNCTION__);
    }

}
