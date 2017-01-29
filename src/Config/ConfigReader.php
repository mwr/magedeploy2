<?php

namespace Mwltr\MageDeploy2\Config;

/**
 * ConfigReader
 */
class ConfigReader
{
    const FILENAME_CONFIG = 'magedeploy2.php';

    public function read()
    {
        $data = include self::FILENAME_CONFIG;

        $config = new Config($data);

        return $config;
    }
}