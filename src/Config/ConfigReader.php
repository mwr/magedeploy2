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
    const FILENAME_CONFIG = 'magedeploy2.php';

    /**
     * Read Config from file and initialize Config object
     *
     * @return Config
     */
    public function read()
    {
        $data = include self::FILENAME_CONFIG;

        $config = new Config($data);

        return $config;
    }
}