<?php

namespace NsUtil;

class Config {

    /**
     * @var array
     */
    protected $settings = [];
    private static $data = [];

    /**
     * @var Config|null
     */
    protected $fallback;

    /**
     * Constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings = []) {
        $this->settings = $settings;
    }

    /**
     * Get a setting.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed config setting or default when not found
     */
    public function get($key, $default = null) {
        if (!array_key_exists($key, $this->settings)) {
            return $this->getDefault($key, $default);
        }

        return $this->settings[$key];
    }

    /**
     * Check if an item exists by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key) {
        if (array_key_exists($key, $this->settings)) {
            return true;
        }

        return $this->fallback instanceof Config ? $this->fallback->has($key) : false;
    }

    /**
     * Try to retrieve a default setting from a config fallback.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed config setting or default when not found
     */
    protected function getDefault($key, $default) {
        if (!$this->fallback) {
            return $default;
        }

        return $this->fallback->get($key, $default);
    }

    /**
     * Set a setting.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value) {
        $this->settings[$key] = $value;
        return $this;
    }

    /**
     * Set the fallback.
     *
     * @param Config $fallback
     *
     * @return $this
     */
    public function setFallback(Config $fallback) {
        $this->fallback = $fallback;

        return $this;
    }

    public function getAll() {
        return $this->settings;
    }

    /**
     * Init para utilização da classe de forma estatica
     * @param array $data
     */
    public static function init(array $data = []) {
        self::$data = $data;
    }

    /**
     * Set data
     * @param type $key1
     * @param type $value
     * @param type $key2
     */
    public static function setData($key1, $value, $merge = true) {
        if (isset(self::$data[$key1]) && is_array($value) && $merge) {
            self::$data[$key1] = array_merge(self::$data[$key1], $value);
        } else {
            self::$data[$key1] = $value;
        }
    }

    /**
     * Get data
     * @param type $key
     * @param type $key2
     * @return type
     */
    public static function getData($key, $key2 = false) {
        if ($key2) {
            return self::$data[$key][$key2];
        } else {
            return self::$data[$key];
        }
    }

    public static function getDataFull(): array {
        return self::$data;
    }

}
