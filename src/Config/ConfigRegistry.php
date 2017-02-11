<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Config;

/**
 * ConfigRegistry
 */
final class ConfigRegistry
{
    /**
     * @var ConfigReader
     */
    protected static $deployConfigReader;

    /**
     * @var Config
     */
    protected static $deployConfig = null;

    /**
     * @return Config|null
     */
    public static function getDeployConfig()
    {
        if (self::$deployConfig === null) {
            self::$deployConfig = self::getDeployConfigReader()->read();
        }

        return self::$deployConfig;
    }

    /**
     * @return ConfigReader
     */
    public static function getDeployConfigReader()
    {
        if (self::$deployConfigReader === null) {
            self::$deployConfigReader = new ConfigReader();
        }

        return self::$deployConfigReader;
    }

    /**
     * Set DeployConfigReader
     *
     * @param ConfigReader $deployConfigReader
     *
     * @return void
     */
    public static function setDeployConfigReader(ConfigReader $deployConfigReader)
    {
        self::$deployConfigReader = $deployConfigReader;
    }

    /**
     * Set DeployConfig
     *
     * @param Config $deployConfig
     *
     * @return void
     */
    public static function setDeployConfig(Config $deployConfig)
    {
        self::$deployConfig = $deployConfig;
    }

}