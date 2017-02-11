<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Robo\Task;

use Mwltr\MageDeploy2\Config\Config;
use Mwltr\MageDeploy2\Config\ConfigAwareTrait;
use Robo\Result;

/**
 * ValidateEnvironmentTask
 */
class ValidateEnvironmentTask extends \Robo\Task\BaseTask
{
    use ConfigAwareTrait;

    public function run()
    {
        $validation = $this->validateExecutables();

        // @todo validate git repository can be fetched

        return Result::success($this);
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

        $validation = true;
        foreach ($configKeys as $key) {
            $result = $this->validateConfigValueIsExecutable($key);
            if ($result === false) {
                $validation = false;
            }
        }

        return $validation;
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