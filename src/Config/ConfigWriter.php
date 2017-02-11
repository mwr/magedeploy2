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
    const FILENAME_CONFIG = 'magedeploy2_test.php';

    /**
     * Write Config to file from Config object
     *
     * @return void
     */
    public function write(Config $config)
    {
        $configData = $config->get();

        $configArray = $this->var_export($configData);

        $configFileContent = "<?php\n\nreturn $configArray;";

        file_put_contents(self::FILENAME_CONFIG, $configFileContent);
    }

    protected function var_export($var, $indent = "")
    {
        $varType = gettype($var);
        if ($varType == "string") {
            $varOut = addcslashes($var, "\\\$\"\r\n\t\v\f");
            $result = sprintf('"%s"', $varOut);
            // $result = '"' . $varOut . '"';
        } elseif ($varType == "array") {
            $indexed = array_keys($var) === range(0, count($var) - 1);
            $r = [];
            foreach ($var as $key => $value) {
                $varExport = $this->var_export($value, "$indent    ");
                $subArray = $indexed ? "" : $this->var_export($key) . " => ";
                $r[] = "$indent    " . $subArray . $varExport;
            }
            $result = sprintf("[\n%s\n%s]", implode(",\n", $r), $indent);
        } elseif ($varType == "boolean") {
            $result = $var ? "true" : "false";
        } else {
            $result = var_export($var, true);

        }

        return $result;
    }
}