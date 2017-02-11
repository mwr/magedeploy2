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

        $configArray = $this->varExport($configData);

        $configFileContent = "<?php\n\nreturn $configArray;";

        file_put_contents(self::FILENAME_CONFIG, $configFileContent);
    }

    protected function varExport($var, $indent = "")
    {
        $varType = gettype($var);
        if ($varType == 'string') {
            $varOut = addcslashes($var, "\\\$\"\r\n\t\v\f");
            $result = sprintf("'%s'", $varOut);
        } elseif ($varType == 'array') {
            $indexed = array_keys($var) === range(0, count($var) - 1);
            $r = [];
            foreach ($var as $key => $value) {
                $varExport = $this->varExport($value, "$indent    ");
                $subArray = $indexed ? "" : $this->varExport($key) . " => ";
                $r[] = "$indent    " . $subArray . $varExport;
            }
            $subArrayAsString = implode(",\n", $r) . ',';
            $result = sprintf("[\n%s\n%s]", $subArrayAsString, $indent);
        } elseif ($varType == 'boolean') {
            $result = $var ? "true" : "false";
        } else {
            $result = var_export($var, true);

        }

        return $result;
    }
}