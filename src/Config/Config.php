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
    protected $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
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
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

}