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

        $cache_ttl = 0;
        $cache_name = '';

        if( !self::$is_collecting_args ) {
            $cache_ttl = apply_filters('arlima_file_include_cache_ttl', 0, $file);
            if( $cache_ttl ) {
                $cache_name = 'arlima_fileinc_'.basename($file).implode('_', $args);
                $cached_content = Arlima_CacheManager::loadInstance()->get($cache_name);
                if( $cached_content ) {
                    if( $cached_content['expires'] < time() ) {
                        Arlima_CacheManager::loadInstance()->delete($cache_name);
                    } else {
                        return $cached_content['content'];
                    }
                }
            }
        }

        // Include file and capture output
        ob_start();
        include $file;
        $content = ob_get_contents();
        ob_end_clean();

        self::$current_file_args = false;

        if( $cache_ttl ) {
            $content = "<!-- arlima file cache $cache_name ( ".date('Y-m-d H:i:s')." ttl: $cache_ttl )  -->\n".$content;
            Arlima_CacheManager::loadInstance()->set($cache_name, array('expires' => time()+$cache_ttl, 'content'=>$content));
        }

        return $content;
    }

}
