<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Robo\Task;

use Mwltr\MageDeploy2\Config\Config;
use Mwltr\MageDeploy2\Config\ConfigReader;
use Mwltr\MageDeploy2\Config\ConfigWriter;
use Mwltr\MageDeploy2\Config\InitDefaultConfigService;
use Robo\Result;

/**
 * GenerateConfigFileTask
 */
class GenerateConfigFileTask extends AbstractTask
{
    public function run()
    {
        // Gather Config information
        $this->yell('env');
        $this->say('environment configuration');
        $gitBin = $this->askDefault('git_bin', '/usr/local/bin/git');
        $phpBin = $this->askDefault('php_bin', '/usr/local/bin/php');
        $tarBin = $this->askDefault('tar_bin', '/usr/local/bin/gtar');
        $composerBin = $this->askDefault('composer_bin', '/usr/local/bin/composer.phar');
        $deployerBin = $this->askDefault('deployer_bin', '/usr/local/bin/deployer.phar');

        $this->yell('deploy');
        $this->say('deploy configuration');
        $gitUrl = $this->ask('git-url');
        $appSubDir = '';
        $gitHasSubDir = $this->askDefault('Is your app composer.json in the root of the vcs', 'y');
        if ($gitHasSubDir == 'n') {
            $appSubDir = $this->askDefault('sub-dir', 'src');
        }

        $this->say('Enter themes to compile for this deployment');
        $enterThemes = $this->askDefault('Add themes? (y/n)', 'y');

        $themes = [];
        if ($enterThemes === 'y') {
            $askForTheme = true;
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
        }

        $this->yell('build');
        $this->say('Enter database configuration for build environment');
        $dbHost = $this->askDefault('db-host', '127.0.0.1');
        $dbName = $this->askDefault('db-name', 'magedeploy2_dev');
        $dbUser = $this->askDefault('db-user', 'root');
        $dbPw = $this->askDefault('db-password', '');

        $configReader = new ConfigReader();
        $config = $configReader->read(true);

        // Create Config data object
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
        if ($themes) {
            $config->set(Config::KEY_DEPLOY . '/' . Config::KEY_THEMES, $themes);
        }

        $pathBuildDb = Config::KEY_BUILD . '/' . Config::KEY_DB . '/';
        $config->set($pathBuildDb . 'db-host', $dbHost);
        $config->set($pathBuildDb . 'db-name', $dbName);
        $config->set($pathBuildDb . 'db-user', $dbUser);
        $config->set($pathBuildDb . 'db-password', $dbPw);

        // Write Config to file
        $configWriter = new ConfigWriter();
        $configWriter->write($config);

        $this->say('config has been created');

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