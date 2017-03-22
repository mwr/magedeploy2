<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Config;

/**
 * Config
 */
class Config
{
    const FILENAME = 'magedeploy2.php';

    const KEY_ENV = 'env';
    const KEY_DEPLOY = 'deploy';
    const KEY_BUILD = 'build';

    const KEY_GIT_BIN = 'git_bin';
    const KEY_PHP_BIN = 'php_bin';
    const KEY_TAR_BIN = 'tar_bin';
    const KEY_MYSQL_BIN = 'mysql_bin';
    const KEY_COMPOSER_BIN = 'composer_bin';
    const KEY_DEPLOYER_BIN = 'deployer_bin';
    const KEY_GIT_URL = 'git_url';
    const KEY_GIT_DIR = 'git_dir';
    const KEY_APP_DIR = 'app_dir';
    const KEY_ARTIFACTS_DIR = 'artifacts_dir';
    const KEY_THEMES = 'themes';
    const KEY_ARTIFACTS = 'artifacts';
    const KEY_CLEAN_DIRS = 'clean_dirs';
    const KEY_DB = 'db';

    /** @var array data array containing the configuration data from the magedeploy2.php file */
    protected $data = [];

    /**
     * Config constructor.
     *
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        if (is_array($data)) {
            $this->data = $data;
        }
    }

    /**
     * @param null $path
     *
     * @return array|mixed|string|float|int|boolean|null
     */
    public function get($path = null)
    {
        if ($path === null) {
            return $this->data;
        }

        $keys = explode('/', $path);
        $data = $this->data;
        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return null;
            }
        }

        return $data;
    }

    /**
     * Set a Config Value
     *
     * @param string $path
     * @param mixed $value
     *
     * @return $this
     */
    public function set($path, $value)
    {
        if (strpos($path, '/') === false) {
            $this->data[$path] = $value;

            return $this;
        }

        $pathKeys = explode('/', $path);
        $key = array_pop($pathKeys);
        $data = &$this->data;
        foreach ($pathKeys as $pathKey) {
            if (is_array($data) && array_key_exists($pathKey, $data)) {
                $data = &$data[$pathKey];
            } else {
                continue;
            }
        }
        $data[$key] = $value;

        return $this;
    }

}