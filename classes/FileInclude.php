<?php

/**
 * Class that can include php files in article lists
 *
 * @package Arlima
 * @since 2.8
 */
class Arlima_FileInclude {

    /**
     * @var bool|array
     */
    private static $current_file_args = false;

    /**
     * @var bool
     */
    private static $is_collecting_args = false;

    /**
     * @var array
     */
    private static $collected_args = array();

    /**
     * @return array
     */
    public function getFileArgs ($file)
    {
        self::$is_collecting_args = true;
        $this->includeFile($file, array(), null, null);
        self::$is_collecting_args = false;
        return self::$collected_args;
    }

    /**
     * @return bool
     */
    public static function isCollectingArgs()
    {
        return self::$is_collecting_args;
    }

    /**
     * @param array $args
     */
    public static function setCollectedArgs($args)
    {
        self::$collected_args = $args;
    }

    /**
     * @return array|bool
     */
    public static function currentFileArgs()
    {
        return self::$current_file_args;
    }

    /**
     * @param string $file
     * @param array $args
     * @param Arlima_AbstractListRenderingManager|null $renderer
     * @param array|null $article
     * @return string
     */
    public function includeFile($file, $args, $renderer=null, $article=null)
    {
        self::$current_file_args = $args;

        // Include file and capture output
        ob_start();
        include $file;
        $content = ob_get_contents();
        ob_end_clean();

        self::$current_file_args = false;

        return $content;
    }

}
