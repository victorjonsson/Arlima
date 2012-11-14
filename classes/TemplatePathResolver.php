<?php

/**
 * Class with all the know how about template paths
 */
class Arlima_TemplatePathResolver {

    const DEFAULT_TMPL = 'article';
    const TMPL_EXT = '.tmpl';

    /**
     * @var array
     */
    private $paths;

    /**
     * @param array $paths - Optional
     */
    function __construct($paths = null) {
        if($paths === null)
            $this->paths = array();

        $this->paths[] = ARLIMA_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' .DIRECTORY_SEPARATOR;
        $this->paths = apply_filters('arlima_template_paths', $this->paths);
    }

    /**
     * Returns all registered template paths
     * @return array
     */
    public function getPaths() {
        return $this->paths;
    }

    /**
     * Returns all files having the extension .tmpl located in registered template paths
     * @return array
     */
    public function getTemplateFiles() {
        $tmpls = array();
        foreach($this->getPaths() as $path) {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            foreach( glob($path . '*' .self::TMPL_EXT) as $file ) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if( empty($tmpls[$name]) )
                    $tmpls[$name] = $file;
            }
        }

        return $tmpls;
    }

    /**
     * Takes a file path to somewhere inside wp-content and turns it into an url.
     * @param string $tmpl_file
     * @return string
     */
    public function fileToUrl($tmpl_file) {
        $content_dir = sprintf('%swp-content%s', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
        $tmpl_url = WP_CONTENT_URL .'/'. current( array_splice(explode($content_dir, $tmpl_file), 1,1) );
        if(DIRECTORY_SEPARATOR != '/') {
            $tmpl_url = str_replace(DIRECTORY_SEPARATOR, '/', $tmpl_url);
        }

        return $tmpl_url;
    }

    public function getDefaultTemplate() {
        return ARLIMA_PLUGIN_PATH . DIRECTORY_SEPARATOR .'templates' . DIRECTORY_SEPARATOR .self::DEFAULT_TMPL. self::TMPL_EXT;
    }

    public static function isTemplateFile($path) {
        return file_exists($path) && substr($path, -1 * strlen(self::TMPL_EXT)) == self::TMPL_EXT;
    }
}