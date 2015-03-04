<?php


/**
 * @package Arlima
 * @since 2.0
 */
class Arlima_CacheManager
{

    private static $instance = null;

    /**
     * @return Arlima_CacheInterface
     */
    public static function loadInstance()
    {
        if ( self::$instance === null ) {
            // Make it possible for other plugin or theme to override
            // the cache manager used by arlima.
            self::$instance = Arlima_CMSFacade::load()->applyFilters('arlima_cache_class', null);
            if ( !is_object(self::$instance) ) {
                self::$instance = new Arlima_WP_Cache();
            }
        }

        return self::$instance;
    }
}