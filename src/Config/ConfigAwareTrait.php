<?php

namespace Mwltr\MageDeploy2\Config;

/**
 * Configs
 */
trait ConfigAwareTrait
{
    protected $deployConfigReader;

    /**
     * @var Config
     */
    protected $deployConfig = null;

    /**
     * @param $key
     * @param null $value
     *
     * @return array|bool|float|int|mixed|null|string
     */
    protected function config($key, $value = null)
    {
        $config = $this->getDeployConfig();

        if ($value !== null) {
            $config->set($key, $value);
        } else {
            return $config->get($key);
        }

    }

    protected function getDeployConfig()
    {
        if ($this->deployConfig === null) {
            $this->initDeployConfig();
        }

        return $this->deployConfig;
    }

    protected function initDeployConfig()
    {
        $this->deployConfig = $this->getDeployConfigReader()->read();
    }

    /**
     * @return ConfigReader
     */
    protected function getDeployConfigReader()
    {
        if ($this->deployConfigReader === null) {
            $this->deployConfigReader = new ConfigReader();
        }

        return $this->deployConfigReader;
    }

}