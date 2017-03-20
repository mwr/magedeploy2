<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Config;

/**
 * ConfigReader
 */
class ConfigReader
{
    /**
     * Read Config from file and initialize Config object
     *
     * @return Config
     */
    public function read($fallbackToDefault = false)
    {
        $configFile = Config::FILENAME;

        if (!is_file($configFile)) {
            if ($fallbackToDefault === false) {
                return null;
            }

            $configFile = __DIR__ . '/magedeploy2.default.php';
        }

        $data = include $configFile;

        $config = new Config($data);

        return $config;
    }
}