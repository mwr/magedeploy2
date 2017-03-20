<?php
/**
 * @copyright Copyright (c) 2017 Matthias Walter
 *
 * @see LICENSE
 */

namespace Mwltr\MageDeploy2\Config;

/**
 * ConfigWriter
 */
class ConfigWriter
{
    /**
     * Write Config to file from Config object
     *
     * @return void
     */
    public function write(Config $config)
    {
        $pathBuildDb = Config::KEY_BUILD . '/' . Config::KEY_DB . '/';

        /** @var array $artifacts */
        $artifacts = $config->get(Config::KEY_DEPLOY . '/' . Config::KEY_ARTIFACTS);
        $artifactsExport = '';
        foreach ($artifacts as $artifactName => $artifact) {
            $artifactVarExport = $this->varExport($artifact);
            $artifactsExport .= "'$artifactName' => $artifactVarExport,\n";
        }

        /** @var array $themes */
        $themes = $config->get(Config::KEY_DEPLOY . '/' . Config::KEY_THEMES);
        $themesExport = '';
        foreach ($themes as $theme) {
            $themesExport .= $this->varExport($theme) . ",\n";
        }

        $vars = [
            '{{GIT_BIN}}' => $config->get(Config::KEY_ENV . '/' . Config::KEY_GIT_BIN),
            '{{PHP_BIN}}' => $config->get(Config::KEY_ENV . '/' . Config::KEY_PHP_BIN),
            '{{TAR_BIN}}' => $config->get(Config::KEY_ENV . '/' . Config::KEY_TAR_BIN),
            '{{COMPOSER_BIN}}' => $config->get(Config::KEY_ENV . '/' . Config::KEY_COMPOSER_BIN),
            '{{DEPLOYER_BIN}}' => $config->get(Config::KEY_ENV . '/' . Config::KEY_DEPLOYER_BIN),

            '{{GIT_URL}}' => $config->get(Config::KEY_DEPLOY . '/' . Config::KEY_GIT_URL),
            "'{{ARTIFACTS}}'" => trim($artifactsExport),
            "'{{THEMES}}'" => trim($themesExport),

            '{{DB_HOST}}' => $config->get($pathBuildDb . 'db-host'),
            '{{DB_NAME}}' => $config->get($pathBuildDb . 'db-name'),
            '{{DB_USER}}' => $config->get($pathBuildDb . 'db-user'),
            '{{DB_PASSWORD}}' => $config->get($pathBuildDb . 'db-password'),
        ];

        $search = array_keys($vars);
        $replace = array_values($vars);

        $md2Template = file_get_contents(__DIR__ . '/magedeploy2.template.php');
        $configFileContent = str_replace($search, $replace, $md2Template);

        file_put_contents(Config::FILENAME, $configFileContent);
    }

    protected function varExport($var, $indent = "")
    {
        $varType = gettype($var);
        if ($varType === 'string') {
            $varOut = addcslashes($var, "\\\$\"\r\n\t\v\f");
            $result = sprintf("'%s'", $varOut);
        } elseif ($varType === 'array') {
            $indexed = array_keys($var) === range(0, count($var) - 1);
            $r = [];
            foreach ($var as $key => $value) {
                $varExport = $this->varExport($value, "$indent");
                $subArray = $indexed ? "" : $this->varExport($key) . ' => ';
                $r[] = "$indent" . $subArray . $varExport;
            }
            $subArrayAsString = implode(",", $r);
            $result = sprintf("[%s%s]", $subArrayAsString, $indent);
        } elseif ($varType === 'boolean') {
            $result = $var ? 'true' : 'false';
        } else {
            $result = var_export($var, true);

        }

        return $result;
    }
}