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
    public function read()
    {
        $data = include Config::FILENAME;

        $config = new Config($data);

        return $config;
    }
}