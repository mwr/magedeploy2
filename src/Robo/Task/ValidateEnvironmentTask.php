<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Robo\Task;

use Robo\Result;

/**
 * ValidateEnvironment
 */
class ValidateEnvironmentTask extends \Robo\Task\BaseTask
{
    use \Mwltr\MageDeploy2\Config\ConfigAwareTrait;

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
            'env/git_bin',
            'env/php_bin',
            'env/tar_bin',
            'env/composer_bin',
            'env/deployer_bin',
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