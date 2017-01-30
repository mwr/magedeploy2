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
    const OPT_DROP_VENDOR = 'drop-vendor';

    /**
     * command to trigger deployment process completly
     *
     * @param string $stage
     * @param string $branch
     * @param array $opts
     */
    public function deploy(
        $stage,
        $branch,
        $opts = [
            self::OPT_REINSTALL_PROJECT => false,
            self::OPT_DROP_VENDOR => false,
        ]
    ) {
        $this->startTimer();

        $this->deployMagentoSetup($branch, $opts);

        $this->deployArtifactsGenerate();

        $this->deployDeploy($stage, $branch);

        $this->stopTimer();
        $this->printRuntime(__FUNCTION__);
    }

    public function deployCheck()
    {
        $this->taskDeployCheck();
    }
    
    /**
     * STAGE command to setup / update Magento and its dependencies
     *
     * @param string $branch
     * @param array $opts
     */
    public function deployMagentoSetup(
        $branch,
        $opts = [
            self::OPT_REINSTALL_PROJECT => false,
            self::OPT_DROP_VENDOR => false,
        ]
    ) {
        $this->startTimer();

        // options are always set (Robo)
        $reinstallProject = $opts[self::OPT_REINSTALL_PROJECT];
        $dropVendor = $opts[self::OPT_DROP_VENDOR];

        $this->printStageInfo('MAGENTO SETUP');

        $this->printTaskInfo('UPDATE SOURCE CODE');
        $this->taskUpdateSourceCode($branch)->run();

        $this->printTaskInfo('CLEAN VAR DIRS');
        $this->taskMagentoCleanVarDirs()->run();

        $this->printTaskInfo('UPDATE COMPOSER');
        $this->taskMagentoUpdateDependencies($dropVendor)->run();

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

        $this->printTaskInfo('MAGENTO SETUP STATIC CONTENT DEPLOY');
        $this->taskMagentoSetupStaticContentDeploy()->run();

        $this->printTaskInfo('GENERATE ARTIFACTS');
        $this->taskArtifactCreatePackages();

        $this->stopTimer();
        $this->printRuntime(__FUNCTION__);
    }

    /**
     * STAGE command triggering release to server using deployer
     *
     * @param string $stage
     * @param string $branch
     */
    public function deployDeploy($stage, $branch)
    {
        $this->startTimer();

        $this->printStageInfo('DEPLOYER DEPLOY');
        $this->taskDeployerDeploy($stage, $branch)->run();

        $this->stopTimer();
        $this->printRuntime(__FUNCTION__);
    }

}