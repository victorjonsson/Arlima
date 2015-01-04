<?php

/**
 * Class with all the know how about template paths
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_TemplatePathResolver
{
    const DEFAULT_TMPL = 'article';
    const TMPL_EXT = '.tmpl';

    /**
     * @var array
     */
    private $paths;

    /**
     * @param array $paths - Optional
     * @param bool $apply_path_filter - Optional, only for testing
     */
    function __construct($paths = null, $apply_path_filter=true)
    {
        if ( $paths === null ) {
            $this->paths = array();
        } elseif( is_string($paths)) {
            $this->paths = array($paths);
        } else {
            $this->paths = $paths;
        }

        $this->paths[] = ARLIMA_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;

        if( $apply_path_filter )
            $this->paths = apply_filters('arlima_template_paths', $this->paths);
    }

    /**
     * Returns all registered template paths
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Returns all files having the extension .tmpl located in registered template paths
     * @return array
     */
    public function getTemplateFiles()
    {
        $templates = array();
        $labels = apply_filters('arlima_template_labels', array());
        foreach ($this->getPaths() as $path) {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            foreach (glob($path . '*' . self::TMPL_EXT) as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if ( empty($templates[$name]) ) {
                    $templates[$name] = array(
                                        'file' => $file,
                                        'label' => empty($labels[$name]) ? str_replace('-', ' ', ucfirst($name)) : $labels[$name],
                                        'name' => $name,
                                        'url' => $this->fileToUrl($file)
                                    );
                }
            }
        }

        return $templates;
    }

    /**
     * Takes a file path to somewhere inside wp-content and turns it into an url.
     * @param string $template_file
     * @return string
     */
    public function fileToUrl($template_file)
    {
        $tmpl_url = WP_CONTENT_URL . str_replace(WP_CONTENT_DIR, '', $template_file);
        if ( DIRECTORY_SEPARATOR != '/' ) {
            $tmpl_url = str_replace(DIRECTORY_SEPARATOR, '/', $tmpl_url);
        }

        return $tmpl_url;
    }

    /**
     * @return string
     */
    public function getDefaultTemplate()
    {
        return ARLIMA_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . self::DEFAULT_TMPL . self::TMPL_EXT;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isTemplateFile($path)
    {
        return file_exists($path) && substr($path, -1 * strlen(self::TMPL_EXT)) == self::TMPL_EXT;
    }

    /**
     * Find the path of a template file with given name
     * @param string $template_name
     * @return bool|string
     */
    public function find($template_name)
    {
        $template_paths = $this->getPaths();
        foreach ($template_paths as $template_path) {
            $template_file = $template_path . DIRECTORY_SEPARATOR . $template_name . Arlima_TemplatePathResolver::TMPL_EXT;
            if ( file_exists($template_file) ) {
                return $template_file;
            }
        }
        return false;
    }
}
