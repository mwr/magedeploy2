<?php

use Mwltr\MageDeploy2\Config\Config;

return [
    Config::KEY_ENV => [
        Config::KEY_GIT_BIN => '/usr/local/bin/git',
        Config::KEY_PHP_BIN => '/usr/local/bin/php',
        Config::KEY_TAR_BIN => '/usr/local/bin/gtar',
        Config::KEY_COMPOSER_BIN => '/usr/local/bin/composer.phar',
        Config::KEY_DEPLOYER_BIN => '/usr/local/bin/deployer.phar',
    ],
    Config::KEY_DEPLOY => [
        Config::KEY_GIT_URL => '',
        Config::KEY_GIT_DIR => 'shop',
        Config::KEY_APP_DIR => 'shop',
        Config::KEY_ARTIFACTS_DIR => 'artifacts',
        Config::KEY_THEMES => [
            [
                'code' => 'Magento/backend',
                'languages' => ['en_US', 'de_DE'],
            ],
            [
                'code' => 'Magento/luma',
                'languages' => ['de_DE'],
            ],
            [
                'code' => 'Magento/luma',
                'languages' => ['en_US'],
            ],
        ],
        Config::KEY_ARTIFACTS => [
            'shop.tar.gz' => [
                'dir' => '.',
                'options' => [
                    '--exclude-vcs',
                    // '--exclude-from=artifact.ignore',
                    '--checkpoint=5000',
                ],
            ],
        ],
        Config::KEY_CLEAN_DIRS => [
            'var/cache',
            'var/di',
            'var/generation',
        ],
    ],
    Config::KEY_BUILD => [
        Config::KEY_DB => [
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