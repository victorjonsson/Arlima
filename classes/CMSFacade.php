<?php


/**
 * Class used to load a singleton instance of the facade in front of underlying system
 *
 * @package Arlima
 * @since 3.0
 */
class Arlima_CMSFacade {

    /**
     * @var Arlima_CMSInterface
     */
    private static $instance = null;

    /**
     * Load facade in front of underlying system
     * @param mixed $in
     * @param string|bool $class
     * @return Arlima_CMSInterface
     */
    public static function load($in=null, $class = false)
    {
        if( self::$instance === null ) {
            $class_name = $class ? $class : ARLIMA_CMS_FACADE;
            self::$instance = new $class_name($in);
        }

        return self::$instance;
    }
}