<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Robo\Task;

use Mwltr\MageDeploy2\Config\Config;
use Mwltr\MageDeploy2\Robo\RoboFile;
use Robo\Collection\CollectionBuilder;
use Robo\Result;

/**
 * ValidateEnvironmentTask
 */
class ValidateEnvironmentTask extends AbstractTask
{
    public function run()
    {
        $msg = '';

        $isExecValid = $this->validateExecutables();

        $isGitValid = $this->validateGit();

        if ($isExecValid && $isGitValid) {
            $result = Result::success($this, $msg);
        } else {
            $result = Result::error($this, $msg);
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function validateExecutables()
    {
        $configKeys = [
            Config::KEY_ENV . '/' . Config::KEY_GIT_BIN,
            Config::KEY_ENV . '/' . Config::KEY_PHP_BIN,
            Config::KEY_ENV . '/' . Config::KEY_TAR_BIN,
            Config::KEY_ENV . '/' . Config::KEY_COMPOSER_BIN,
            Config::KEY_ENV . '/' . Config::KEY_DEPLOYER_BIN,
        ];

        $result = true;
        foreach ($configKeys as $key) {
            $isExec = $this->validateConfigValueIsExecutable($key);
            if ($isExec === false) {
                $result = false;
            }
        }

        return $result;
    }

    protected function validateGit()
    {
        $result = true;
        $gitUrl = $this->config(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_URL);
        try {
            $collection = $this->collectionBuilder();
            /** @var RoboFile $collection */
            $collection->taskExec("git ls-remote $gitUrl")->printed(false);
            /** @var CollectionBuilder $collection */

            $gitCheckResult = $collection->run();

            $this->printTaskSuccess("<info>$gitUrl</info> is accessible");
        } catch (\Exception $e) {
            $this->printTaskError("$gitUrl not accessible");
            $result = false;
        }

        return $result;
    }

    protected function validateConfigValueIsExecutable($key)
    {
        $bin = $this->config($key);
        if (empty($bin)) {
            $msg = "$key empty";
            $this->printTaskError($msg);

            return false;
        }

        return $this->validateBinIsExecutable($bin);
    }

    /**
     * @param string $bin
     *
     * @return bool
     */
    protected function validateBinIsExecutable($bin)
    {
        $result = false;

        if (is_file($bin) && is_executable($bin)) {
            $this->printTaskSuccess("<info>$bin</info> is executable");
            $result = true;
        } else {
            $this->printTaskError("$bin not executable");
        }

        return $result;
    }

}