<?php


/**
 * Facade in front of the template engine (mustasche) used
 * by Arlima.
 *
 * @package Arlima
 * @since 3.0
 */
class Arlima_TemplateEngine
{

    /**
     * @var Mustache_Template[]
     */
    private static $preloaded_templates = array();

    /**
     * @var Mustache_Engine
     */
    private $mustache;

    /**
     * @var Mustache_Template|null
     */
    private $default_tmpl_obj = null;

    /**
     * @param string $template_path - Optional path to directory where templates should exists
     */
    function __construct($template_path=null)
    {
        $this->template_resolver = new Arlima_TemplatePathResolver($template_path);
        $this->mustache = new Mustache_Engine();
    }

    /**
     * @return string $tmpl_data_obj
     * @param string $tmpl_name
     * @return string
     */
    function render($tmpl_data_obj, $tmpl_name)
    {
        $template = $this->load($tmpl_name);
        if( is_object($template) ) {
            return $template->render($tmpl_data_obj);
        } else {
            return $this->default_tmpl_obj->render($tmpl_data_obj);
        }
    }

    /**
     * Set which template that should be used as default. Will return false
     * if given template can't be found
     * @param string $tmpl_name;
     * @return bool
     */
    function setDefaultTemplate($tmpl_name)
    {
        $this->default_tmpl_obj = $this->load($tmpl_name);
        return is_object($this->default_tmpl_obj);
    }


    /**
     * Takes a file and turns it into a mustache template object
     * @param string $template_file
     * @return Mustache_Template
     */
    private function fileToMustacheTemplate($template_file)
    {
        // Load template content
        $template_content = file_get_contents($template_file);

        // Merge with includes
        preg_match_all('(\{\{include .*[^ ]\}\})', $template_content, $sub_parts);
        while ( !empty($sub_parts) && !empty($sub_parts[0]) ) {

            $template_path = dirname($template_file) . '/';
            foreach ($sub_parts[0] as $tpl_part) {
                $path = str_replace(array('{{include ', '}}'), '', $tpl_part);
                $included_tmpl = $template_path . $path;
                if ( file_exists($included_tmpl) ) {
                    $template_content = str_replace($tpl_part, file_get_contents($included_tmpl), $template_content);
                } else {
                    $template_content = str_replace(
                        $tpl_part,
                        '{{! ERROR: ' . $included_tmpl . ' does not exist}}',
                        $template_content
                    );
                }
            }
            preg_match_all('(\{\{include [0-9a-z\/A-Z\-\_\.]*\}\})', $template_content, $sub_parts);
        }

        // Remove image support declarations
        $template_content = preg_replace('(\{\{image-support .*\}\})', '', $template_content);

        return $this->mustache->loadTemplate($template_content);
    }


    /**
     * Load template that should be used for given article.
     * @param string $template_name
     * @return Mustache_Template
     */
    protected function load($template_name) {
        if ( isset(self::$preloaded_templates[$template_name]) ) {
            if( self::$preloaded_templates[$template_name] === '' ) {
                // Don't search for template more than once, we have searched for this template
                // but it was not found == return default object
                return $this->default_tmpl_obj;
            }
            return self::$preloaded_templates[$template_name];
        }

        if( $template_file = $this->template_resolver->find($template_name) ) {
            self::$preloaded_templates[$template_name] = $this->fileToMustacheTemplate($template_file);
            return self::$preloaded_templates[$template_name];
        }

        // Template file not found, return default
        self::$preloaded_templates[$template_name] = '';
        return $this->default_tmpl_obj;
    }

}