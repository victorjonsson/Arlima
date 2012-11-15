<?php

/**
 * @package Arlima
 * @since 2.5
 */
class Arlima_ArticleFormat {

    private static $formats = array();

    /**
     * @see arlima_register_format()
     * @param $class
     * @param $label
     * @param array $tmpls
     */
    public static function add($class, $label, $tmpls=array()) {
        $id = self::generateFormatId($class, $tmpls);
        self::$formats[$id] = array('class' => $class, 'label' => $label, 'templates' => $tmpls);
    }

    public static function getAll() {
        return self::$formats;
    }

    public static function remove($class, $tmpls=array()) {
        $id = self::generateFormatId($class, $tmpls);
        if( isset(self::$formats[$id]) ) {
            unset(self::$formats[$id]);
        }
    }

    private static function generateFormatId($class, $tmpls) {
        return join('-',$tmpls).':'.$class;
    }

}