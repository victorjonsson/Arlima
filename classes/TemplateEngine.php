<?php


/**
 * Facade in front of the template engine (mustasche) used
 * by Arlima.
 *
 * @package Arlima
 * @since 3.0
 */
class Arlima_TemplateEngine implements Arlima_TemplateEngineInterface
{

    /**
     * @var Mustache_Template[]
     */
    private static $preloaded_templates = array();

    /**
     * @var array
     */
    private static $not_found_templates = array();

    /**
     * @var Mustache_Engine
     */
    private $mustache;

    /**
     * @var Mustache_Template|null
     */
    private $default_tmpl_obj = null;

    /**
     * @var Arlima_TemplatePathResolver
     */
    private $template_path_resolver;

    /**
     * @var Arlima_TemplateObjectCreator
     */
    private $template_obj_creator;

    /**
     * Factory method for creating instances of the template engine
     * @param Arlima_List $list
     * @param null|string $template_path
     * @return Arlima_TemplateEngineInterface
     */
    public static function create($list, $template_path = null)
    {
        $obj_creator = new Arlima_TemplateObjectCreator();
        $obj_creator->setList($list);
        if ( $list->hasOption('before_title') ) {
            $obj_creator->setBeforeTitleHtml($list->getOption('before_title'));
            $obj_creator->setAfterTitleHtml($list->getOption('after_title'));
        }
        return new self(new Arlima_TemplatePathResolver($template_path), $obj_creator, new Mustache_Engine());
    }

    /**
     * @param Arlima_TemplatePathResolver $tmpl_path_resolver
     * @param Arlima_TemplateObjectCreator $obj_creator
     * @param Mustache_Engine $mustache
     */
    protected function __construct($tmpl_path_resolver, $obj_creator, $mustache)
    {
        $this->template_path_resolver = $tmpl_path_resolver;
        $this->template_obj_creator = $obj_creator;
        $this->mustache = $mustache;
    }

    /**
     * @param string $template_name
     * @param int $article_counter
     * @param Arlima_Article $article
     * @param object $post
     * @param string $child_articles
     * @param bool $child_split_state
     * @return string
     */
    function renderArticle($template_name, $article_counter, $article, $post, $child_articles='', $child_split_state=false)
    {
        $this->template_obj_creator->setIsChild( $article->isChild() );
        $this->template_obj_creator->setChildSplitState($child_split_state);

        $template_obj = $this->template_obj_creator->create(
                                $article,
                                $post,
                                $article_counter,
                                $template_name
                            );

        if ( !empty($child_articles) ) {
            $template_obj['child_articles'] = $child_articles;
        }


        return $this->render($template_obj, $template_name);
    }

    /**
     * @param string $tmpl_data_obj
     * @param string $tmpl_name
     * @return string
     */
    private function render($tmpl_data_obj, $tmpl_name)
    {
        $template = $this->loadTemplateObject($tmpl_name);
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
        $this->default_tmpl_obj = $this->loadTemplateObject($tmpl_name);
        if( !is_object($this->default_tmpl_obj) ) {
            throw new Exception('Template with name '.$tmpl_name.' could not be found'); // todo: create custom exception object
        }
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
    protected function loadTemplateObject($template_name) {

        if( in_array($template_name, self::$not_found_templates) ) {
            return $this->default_tmpl_obj;
        }

        if( empty(self::$preloaded_templates[$template_name]) ) {
            if( $template_file = $this->template_path_resolver->find($template_name) ) {
                self::$preloaded_templates[$template_name] = $this->fileToMustacheTemplate($template_file);
            } else {
                // Does not exist
                self::$not_found_templates[] = $template_name;
                return $this->default_tmpl_obj;
            }
        }

        return self::$preloaded_templates[$template_name];
    }

}
