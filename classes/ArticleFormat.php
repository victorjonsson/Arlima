<?php


/**
 * Class that manages information about article formats
 *
 * @package Arlima
 * @since 2.5
 */
class Arlima_ArticleFormat
{

    /**
     * @var array
     */
    private static $formats = array();

    /**
     * @see arlima_register_format()
     * @param string $class
     * @param string $label
     * @param array $templates
     */
    public static function add($class, $label, $templates = array())
    {
        $id = self::generateFormatId($class, $templates);
        self::$formats[$id] = array('class' => $class, 'label' => $label, 'templates' => $templates);
    }

    /**
     * @return array
     */
    public static function getAll()
    {
        return self::$formats;
    }

    /**
     * @param string $class
     * @param array $templates
     */
    public static function remove($class, $templates = array())
    {
        $id = self::generateFormatId($class, $templates);
        if ( isset(self::$formats[$id]) ) {
            unset(self::$formats[$id]);
        }
    }

    /**
     * @param $class
     * @param $templates
     * @return string
     */
    private static function generateFormatId($class, $templates)
    {
        return join('-', $templates) . ':' . $class;
    }

}