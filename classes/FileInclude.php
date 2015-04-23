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
     * @param string $file
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
     * @param Arlima_Article|null $article
     * @return string
     */
    public function includeFile($file, $args, $renderer=null, $article=null) // Last two arguments should be available in the included file
    {
        self::$current_file_args = $args;

        $cache_name = false;
        $cache_ttl = Arlima_CMSFacade::load()->applyFilters('arlima_file_include_cache_ttl', 0, $file);
        $file = Arlima_CMSFacade::load()->applyFilters('arlima_file_include_path', $file, $article);

        // Load content from cache
        if( $cache_ttl ) {
            $cache_name = $this->generateCacheName($file, $args);
            $content = $this->loadFileContentFromCache($file, $args, $cache_name, $cache_ttl);
            if( $content )
                return $content;
        }

        // Load content from file
        $content = $this->getFileContents($file, $renderer, $article);

        self::$current_file_args = false;

        if( $cache_ttl ) {
            $content = $this->saveFileContentToCache($cache_name, $cache_ttl, $content);
        }

        return $content;
    }

    /**
     * @param string $file
     * @param array $args
     * @return string
     */
    private function generateCacheName($file, $args)
    {
        return  'arlima_fileinc_'.basename($file).( is_array($args) ? implode('_', $args) : $args);
    }

    /**
     * @param $file
     * @param $args
     * @param $cache_name
     * @return bool|string
     */
    private function loadFileContentFromCache($file, $args, $cache_name)
    {
        if( !self::$is_collecting_args ) {
            $cached_content = Arlima_CacheManager::loadInstance()->get($cache_name);
            if( $cached_content ) {
                if( $cached_content['expires'] < time() ) {
                    Arlima_CacheManager::loadInstance()->delete($cache_name);
                } else {
                    return $cached_content['content'];
                }
            }
        }
        return false;
    }

    /**
     * @params string $file
     * @return bool|string
     */
    private function resolvePath($file)
    {
        if( file_exists($file) ) {
            return $file;
        } elseif( $resolved = Arlima_CMSFacade::load()->resolveFilePath($file, false)) {
            return $resolved;
        }
        return false;
    }

    /**
     * @param $file
     * @param null $renderer
     * @param null $article
     * @return string
     */
    private function getFileContents($file, $renderer=null, $article=null)
    {
        if ($resolved_path = $this->resolvePath($file)) {
            ob_start();
            include $resolved_path;
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        } else {
            $content = '';
            trigger_error('Trying to include an arlima file that does not exist ' . $file, E_USER_NOTICE);
            return $content;
        }
    }

    /**
     * @param string $cache_name
     * @param int $cache_ttl
     * @param string $content
     * @return string
     */
    private function saveFileContentToCache($cache_name, $cache_ttl, $content)
    {
        $content = "<!-- arlima file cache $cache_name ( " . date('Y-m-d H:i:s') . " ttl: $cache_ttl )  -->\n" . $content;
        Arlima_CacheManager::loadInstance()->set($cache_name, array('expires' => time() + $cache_ttl, 'content' => $content));
        return $content;
    }
}
