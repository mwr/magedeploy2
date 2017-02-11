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
    const KEY_ENV = 'env';
    const KEY_DEPLOY = 'deploy';
    const KEY_BUILD = 'build';

    const KEY_GIT_BIN = 'git_bin';
    const KEY_PHP_BIN = 'php_bin';
    const KEY_TAR_BIN = 'tar_bin';
    const KEY_COMPOSER_BIN = 'composer_bin';
    const KEY_DEPLOYER_BIN = 'deployer_bin';
    const KEY_GIT_URL = 'git_url';
    const KEY_GIT_DIR = 'git_dir';
    const KEY_APP_DIR = 'app_dir';
    const KEY_THEMES = 'themes';
    const KEY_ASSETS = 'assets';
    const KEY_CLEAN_DIRS = 'clean_dirs';
    const KEY_DB = 'db';

    /** @var array data array containing the configuration data from the magedeploy2.php file */
    protected $data = [
        self::KEY_ENV => [
            self::KEY_GIT_BIN => '/usr/local/bin/git',
            self::KEY_PHP_BIN => '/usr/local/bin/php',
            self::KEY_TAR_BIN => '/usr/local/bin/gtar',
            self::KEY_COMPOSER_BIN => '/usr/local/bin/composer.phar',
            self::KEY_DEPLOYER_BIN => '/usr/local/bin/deployer.phar',
        ],
        self::KEY_DEPLOY => [
            self::KEY_GIT_URL => '',
            self::KEY_GIT_DIR => 'shop',
            self::KEY_APP_DIR => 'shop',
            self::KEY_THEMES => [
                'Magento/luma' => [
                    'de_DE',
                    'en_US',
                ],
                'Magento/backend' => [
                    'de_DE',
                    'en_US',
                ],
            ],
            self::KEY_ASSETS => [
                'var_di.tar.gz' => ['dir' => 'src/var/di'],
                'var_generation.tar.gz' => ['dir' => 'src/var/generation'],
                'pub_static.tar.gz' => ['dir' => 'src/pub/static'],
                'shop.tar.gz' => [
                    'dir' => 'src',
                    'options' => [
                        '--exclude-vcs',
                        // '--exclude-from=artifact.ignore',
                        '--checkpoint=5000',
                    ],
                ],
            ],
            self::KEY_CLEAN_DIRS => [
                'var/cache',
                'var/di',
                'var/generation',
            ],
        ],
        self::KEY_BUILD => [
            self::KEY_DB => [
                'db-host' => '127.0.0.1',
                'db-name' => 'magedeploy2_dev',
                'db-password' => '',
                'db-user' => 'root',
                'admin-email' => 'admin@mwltr.de',
                'admin-firstname' => 'Admin',
                'admin-lastname' => 'Admin',
                'admin-password' => 'admin123',
                'admin-user' => 'admin',
                'backend-frontname' => 'admin',
                'base-url' => 'http://magedeploy2_dev',
                'base-url-secure' => 'https://magedeploy2_dev',
                'currency' => 'EUR',
                'language' => 'en_US',
                'session-save' => 'files',
                'timezone' => 'Europe/Berlin',
                'use-rewrites' => '1',
                'use-secure' => '0',
                'use-secure-admin' => '0',
            ],
        ],
    ];

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