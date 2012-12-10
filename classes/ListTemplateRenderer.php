<?php

/**
 * Class that can render an Arlima article list using jQueryTmpl. The class
 * uses templates available in the path given on construct, if template not
 * found it falls back on templates available in this plugin directory (arlima/templates)
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_ListTemplateRenderer extends Arlima_AbstractListRenderingManager
{
    /**
     * @var array
     */
    private $template_resolver = array();

    /**
     * @var Arlima_TemplateObjectCreator
     */
    private $template_obj_creator;

    /**
     * Current unix time
     * @var int
     */
    private $now;

    /**
     * @var array
     */
    private $custom_templates = array();

    /**
     * @var jQueryTmpl
     */
    protected $jQueryTmpl_default = null;

    /**
     * Class constructor
     * @param Arlima_List|stdClass $list
     * @param string $template_path - Optional path to directory where templates should exists (see readme.txt about how to add your own template paths from the theme)
     */
    function __construct($list, $template_path = null)
    {
        $this->now = time();
        $this->template_resolver = new Arlima_TemplatePathResolver(array($template_path));
        parent::__construct($list);
    }

    /**
     * Prepares the template object creator
     */
    protected function setupObjectCreator()
    {
        $this->template_obj_creator = new Arlima_TemplateObjectCreator();
        $this->template_obj_creator->setList($this->getList());
        if ( !empty($this->list->options['before_title']) ) {
            $this->template_obj_creator->setBeforeTitleHtml($this->list->options['before_title']);
            $this->template_obj_creator->setAfterTitleHtml($this->list->options['after_title']);
        }

        $this->template_obj_creator->setArticleBeginCallback($this->article_begin_callback);
        $this->template_obj_creator->doAddTitleFontSize($this->list->getOption('ignore_fontsize') ? false : true);
        $this->template_obj_creator->setArticleEndCallback($this->article_end_callback);
        $this->template_obj_creator->setImageCallback($this->image_callback);
        $this->template_obj_creator->setRelatedCallback($this->related_posts_callback);
        $this->template_obj_creator->setContentCallback($this->content_callback);
    }

    /**
     * Do we have a list? Does the list have articles?
     * @return bool
     */
    function havePosts()
    {
        return $this->list->numArticles() > 0 && $this->list->numArticles() >= $this->getOffset();
    }

    /**
     * Will render all articles in the arlima list using jQuery templates. The template to be
     * used is an option in the article list object (Arlima_List). If no template exists in declared
     * template paths we will fall back on default templates (plugins/arlima/template/[name].tmpl)
     *
     * @param bool $output[optional=true]
     * @return string
     */
    function renderList($output = true)
    {

        $article_counter = 0;
        $content = '';

        // Create template
        $jQueryTmpl_df = new jQueryTmpl_Data_Factory();
        $this->jQueryTmpl_default = $this->loadTemplate(
            $this->list->getOption('template'),
            new jQueryTmpl_Factory(),
            new jQueryTmpl_Markup_Factory()
        );

        // Setup tmpl object creator
        $this->setupObjectCreator();

        $articles = array_slice($this->list->getArticles(), $this->getOffset());

        foreach ($articles as $article_data) {
            list($article_counter, $article_content) = $this->outputArticle(
                $article_data,
                $jQueryTmpl_df,
                $article_counter
            );
            if ( $output ) {

                echo $article_content;

            } else {
                $content .= $article_content;
            }

            if ( $article_counter == $this->getLimit() ) {
                break;
            }
        }

        // unset global post data
        $GLOBALS['post'] = null;
        wp_reset_query();

        return $content;
    }

    /**
     * @param array|stdClass $article_data
     * @param jQueryTmpl_Data_Factory $jQueryTmpl_df
     * @param int $article_counter
     * @return int
     */
    protected function outputArticle($article_data, jQueryTmpl_Data_Factory $jQueryTmpl_df, $article_counter)
    {

        // Sticky article
        if ( !empty($article_data['options']) && !empty($article_data['options']['sticky']) ) {
            if ( !$this->isInStickyInterval($article_data['options']['sticky_interval']) ) {
                return array($article_counter, ''); // don't show this sticky right now
            }
        }

        // Setup
        list($post, $article, $is_post, $is_empty) = $this->setup($article_data);

        // Future article
        if ( !empty($article_data['publish_date']) && $article_data['publish_date'] > $this->now ) {
            return array(
                    $article_counter,
                    call_user_func(
                        $this->future_post_callback,
                        $post,
                        $article,
                        $this->list,
                        $article_counter
                    )
                );
        }

        $template_data = $this->template_obj_creator->create(
                            $article,
                            true,
                            $is_empty,
                            $is_post,
                            $post,
                            $article_counter,
                            $this->img_size_name,
                            true
                        );

        // load sub articles if there's any
        if ( !empty($article['children']) && is_array($article['children']) ) {
            $template_data['sub_articles'] = $this->renderSubArticles($article['children'], $jQueryTmpl_df);
        }

        // output the article
        $content = $this->generateTemplateOutput($article, $jQueryTmpl_df, $template_data);

        return array($article_counter + 1, $content);
    }

    /**
     * @param $article
     * @param jQueryTmpl_Data_Factory $jQueryTmpl_df
     * @param $template_data
     * @return string
     */
    private function generateTemplateOutput($article, $jQueryTmpl_df, $template_data)
    {
        if ( !empty($article['options']) && !empty($article['options']['template']) ) {
            $template_factory = $this->loadTemplate(
                $article['options']['template'],
                new jQueryTmpl_Factory(),
                new jQueryTmpl_Markup_Factory()
            );
        } else {
            $template_factory = $this->jQueryTmpl_default;
        }

        return $template_factory->tmpl('tpl', $jQueryTmpl_df->createFromArray($template_data))->getHtml();
    }

    /**
     * Will try to parse a sticky-interval-formatted string and determine
     * if we're currently in this time interval
     * @example
     *  isInStickyInterval('*:*');
     *  isInStickyInterval('Mon,Tue,Fri:*');
     *  isInStickyInterval('*:10-12');
     *  isInStickyInterval('Thu:12,15,18');
     *
     * @param string $sticky_interval
     * @return bool
     */
    private function isInStickyInterval($sticky_interval)
    {
        $interval_part = explode(':', $sticky_interval);
        if ( count($interval_part) == 2 ) {

            // Check day
            if ( trim($interval_part[0]) != '*' ) {

                $current_day = strtolower(date('D', $this->now + (get_option('gmt_offset') * 3600)));
                $days = array();
                foreach (explode(',', $interval_part[0]) as $day) {
                    $days[] = strtolower(substr(trim($day), 0, 3));
                }

                if ( !in_array($current_day, $days) ) {
                    return false; // don't show article today
                }

            }

            // Check hour
            if ( trim($interval_part[1]) != '*' ) {

                $current_hour = (int)date('H', $this->now + (get_option('gmt_offset') * 3600));
                $from_to = explode('-', $interval_part[1]);
                if ( count($from_to) == 2 ) {
                    $from = (int)trim($from_to[0]);
                    $to = (int)trim($from_to[1]);
                    if ( $current_hour < $from || $current_hour > $to ) {
                        return false; // don't show article this hour
                    }
                } else {
                    $hours = array();
                    foreach (explode(',', $interval_part[1]) as $hour) {
                        $hours[] = (int)trim($hour);
                    }

                    if ( !in_array($current_hour, $hours) ) {
                        return false; // don't show article this hour
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param array $articles
     * @param jQueryTmpl_Data_Factory $jQueryTmpl_df
     * @internal param \jQueryTmpl $jQueryTmpl
     * @return string
     */
    private function renderSubArticles(array $articles, jQueryTmpl_Data_Factory $jQueryTmpl_df)
    {
        $sub_articles = '';
        $count = 0;
        $children_count = sizeof($articles);
        $image_size = $children_count == 1 ? $this->img_size_name_sub_article_full : $this->img_size_name_sub_article;

        foreach ($articles as $article_data) {

            list($post, $article, $is_post, $is_empty) = $this->setup($article_data);

            if ( is_object($post) && $post->post_status == 'future' ) {
                continue;
            }

            $template_data = $this->template_obj_creator->create(
                $article,
                true,
                $is_empty,
                $is_post,
                $post,
                -1,
                $image_size,
                false
            );
            $template_data['container']['attr'] = '';
            $template_data['container']['class'] = 'teaser small' . ($children_count > 1 ? ' teaser-split' : '') . ($count % 2 == 0 ? ' first' : ' last');

            $sub_articles .= $this->generateTemplateOutput($article, $jQueryTmpl_df, $template_data);
            $count++;
        }

        return $sub_articles;
    }

    /**
     * @param $template_name
     * @param jQueryTmpl_Factory $jQueryTmpl_Factory
     * @param jQueryTmpl_Markup_Factory $jQueryTmpl_Markup_Factory
     * @return jQueryTmpl
     */
    protected function loadTemplate(
        $template_name,
        jQueryTmpl_Factory $jQueryTmpl_Factory,
        jQueryTmpl_Markup_Factory $jQueryTmpl_Markup_Factory
    ) {
        if ( isset($this->custom_templates[$template_name]) ) {
            return $this->custom_templates[$template_name];
        }

        $jQueryTmpl = $jQueryTmpl_Factory->create();

        $template_paths = $this->template_resolver->getPaths();
        foreach ($template_paths as $template_path) {
            $template_file = $template_path . DIRECTORY_SEPARATOR . $template_name . Arlima_TemplatePathResolver::TMPL_EXT;
            if ( file_exists($template_file) ) {
                $this->custom_templates[$template_name] = $this->createTemplate(
                    $template_file,
                    $jQueryTmpl,
                    $jQueryTmpl_Markup_Factory
                );
                return $this->custom_templates[$template_name];
            }
        }

        // If we have come this far the template doesn't exist in any template path
        $error_msg = 'Arlima tmpl file ' . $template_name . ' is not present in '.
                    'any template path. Paths registered: ' . join(',',$template_paths);

        trigger_error($error_msg, E_USER_WARNING);
        
        $template_fallback = $this->template_resolver->getDefaultTemplate();
        $this->custom_templates['article'] = $this->createTemplate(
                                                $template_fallback,
                                                $jQueryTmpl,
                                                $jQueryTmpl_Markup_Factory
                                            );

        return $this->custom_templates['article'];
    }

    /**
     * @param string $template_file
     * @param jQueryTmpl $jQueryTmpl
     * @param jQueryTmpl_Markup_Factory $jQueryTmpl_Markup_Factory
     * @return jQueryTmpl
     */
    private function createTemplate($template_file, $jQueryTmpl, $jQueryTmpl_Markup_Factory)
    {
        $template_content = file_get_contents($template_file);
        preg_match_all('(\{\{include [0-9a-z\/A-Z\-\_\.]*\}\})', $template_content, $sub_parts);
        if ( !empty($sub_parts) && !empty($sub_parts[0]) ) {

            $template_path = dirname($template_file) . '/';
            foreach ($sub_parts[0] as $tpl_part) {
                $path = str_replace(array('{{include ', '}}'), '', $tpl_part);
                $included_tmpl = $template_path . $path;
                if ( file_exists($included_tmpl) ) {
                    $template_content = str_replace($tpl_part, file_get_contents($included_tmpl), $template_content);
                } else {
                    $template_content = str_replace(
                        $tpl_part,
                        '# ERROR: ' . $included_tmpl . ' does not exist',
                        $template_content
                    );
                }
            }
        }

        $jQueryTmpl->template('tpl', $jQueryTmpl_Markup_Factory->createFromString($template_content));
        return $jQueryTmpl;
    }
}