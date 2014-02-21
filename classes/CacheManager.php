<?php


/**
 * Wrapper for wp_cache functions.
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_CacheManager
{

    /**
     * @see ArlimaCache::loadInstance()
     */
    protected function __construct() {}

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function get($key)
    {
        return wp_cache_get($key, 'arlima-cache');
    }

    /**
     * @param string $key
     * @param mixed $val
     */
    function set($key, $val)
    {
        wp_cache_set($key, $val, 'arlima-cache');
    }

    /**
     * @param string $key
     * @return bool
     */
    function delete($key)
    {
        return wp_cache_delete($key, 'arlima-cache');
    }

    private static $instance = null;

    /**
     * @static
     * @return Arlima_CacheManager
     */
    public static function loadInstance()
    {
        if ( self::$instance === null ) {
            // Make it possible for other plugin or theme to override
            // the cache manager used by arlima.
            self::$instance = apply_filters('arlima_cache_class', null);
            if ( !is_object(self::$instance) ) {
                self::$instance = new self();
            }
        }

        return self::$instance;
    }
}