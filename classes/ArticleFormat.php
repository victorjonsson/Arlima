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
     * Register a format name that should be accessible in the list manager when editing an article (see https://github.com/victorjonsson/Arlima/wiki/Article-formats)
     * @param string $class
     * @param string $label
     * @param array $templates
     * @param string $ui_color
     */
    public static function add($class, $label, $templates = array(), $ui_color='')
    {
        $id = self::generateFormatId($class, $templates);
        self::$formats[$id] = array('class' => $class, 'label' => $label, 'templates' => $templates, 'ui_color'=>$ui_color);
    }

    /**
     * Get all formats registered up to this point
     * @return array
     */
    public static function getAll()
    {
        return self::$formats;
    }

    /**
     * Remove a registered format
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