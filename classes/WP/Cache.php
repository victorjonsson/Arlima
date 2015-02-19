<?php


/**
 * Wrapper for wp_cache functions.
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_WP_Cache implements Arlima_CacheInterface
{

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function get($key)
    {
        return wp_cache_get($key, 'arlima-cache');
    }

    /**
     * @param $key
     * @param $val
     * @param int $expires
     */
    function set($key, $val, $expires=0)
    {
        wp_cache_set($key, $val, 'arlima-cache', $expires);
    }

    /**
     * @param string $key
     * @return bool
     */
    function delete($key)
    {
        return wp_cache_delete($key, 'arlima-cache');
    }
}