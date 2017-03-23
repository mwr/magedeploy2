<?php

return [
    'env' => [
        'git_bin' => getenv('GIT_BIN') ?: '{{GIT_BIN}}',
        'php_bin' => getenv('PHP_BIN') ?: '{{PHP_BIN}}',
        'tar_bin' => getenv('TAR_BIN') ?: '{{TAR_BIN}}',
        'mysql_bin' => getenv('MYSQL_BIN') ?: '{{MYSQL_BIN}}',
        'composer_bin' => getenv('COMPOSER_BIN') ?: '{{COMPOSER_BIN}}',
        'deployer_bin' => getenv('DEPLOYER_BIN') ?: '{{DEPLOYER_BIN}}',
    ],
    'deploy' => [
        'git_url' => '{{GIT_URL}}',
        'git_dir' => 'shop',
        'app_dir' => 'shop',
        'artifacts_dir' => 'artifacts',
        'themes' => [
            '{{THEMES}}'
        ],
        'artifacts' => [
            '{{ARTIFACTS}}'
        ],
        'clean_dirs' => [
            'var/cache',
            'var/di',
            'var/generation',
        ],
    ],
    'build' => [
        'db' => [
            'db-host' => getenv('DB_HOST') ?: '{{DB_HOST}}',
            'db-name' => getenv('DB_NAME') ?: '{{DB_NAME}}',
            'db-user' => getenv('DB_USER') ?: '{{DB_USER}}',
            'db-password' => getenv('DB_PASSWORD') ?: '{{DB_PASSWORD}}',
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