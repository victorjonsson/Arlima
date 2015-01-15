<?php


/**
 * Wrapper for wp_cache functions.
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_CacheManager
{

    private static $instance = null;

    /**
     * @static
     * @return Arlima_WP_Cache
     */
    public static function loadInstance()
    {
        if ( self::$instance === null ) {
            // Make it possible for other plugin or theme to override
            // the cache manager used by arlima.
            self::$instance = apply_filters('arlima_cache_class', null);
            if ( !is_object(self::$instance) ) {
                self::$instance = new Arlima_WP_Cache();
            }
        }

        return self::$instance;
    }
}