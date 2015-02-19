<?php

/**
 * Interface for a class that can cache arbitrary data
 *
 * @package Arlima
 * @since 3.1
 */
interface Arlima_CacheInterface {

    /**
     * @param string $key
     * @return bool
     */
    function delete($key);

    /**
     * @param $key
     * @param $val
     * @param int $expires
     */
    function set($key, $val, $expires = 0);

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function get($key);

}