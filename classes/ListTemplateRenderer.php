<?php

/**
 * Class that can render an Arlima article list using jQueryTmpl. The class
 * uses templates available in the path given on construct, if template not
 * found it falls back on templates available in this plugin directory (arlima/templates)
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_ListTemplateRenderer extends Arlima_AbstractListRenderingManager {

    /**
     * @var array
     */
    private $template_resolver = array();

    /**
     * @var Arlima_TmplDataObjectCreator
     */
    private $tmpl_obj_creator;

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
    protected  $jQueryTmpl_default = null;

    /**
     * Class constructor
     * @param Arlima_List|stdClass $list
     * @param string $template_path - Optional path to directory where templates should exists (see readme.txt about how to add your own template paths from the theme)
     */
    function __construct($list, $template_path = null) {
        $this->now = time();
        $this->template_resolver = new Arlima_TemplatePathResolver($template_path);
        parent::__construct($list);
    }

    /**
     * Prepares the template object creator
     */
    protected function setupObjectCreator() {
        $this->tmpl_obj_creator = new Arlima_TmplDataObjectCreator();
        if( !empty($this->list->options['before_title']) ) {
            $this->tmpl_obj_creator->setBeforeTitleHtml($this->list->options['before_title']);
            $this->tmpl_obj_creator->setAfterTitleHtml($this->list->options['after_title']);
        }
        $this->tmpl_obj_creator->setAddTitleFontSize( empty($this->list->options['ignore_fontsize']) );
        $this->tmpl_obj_creator->setArticleEndCallback($this->article_end_callback);
        $this->tmpl_obj_creator->setGetArticleImageCallback($this->get_article_image_callback);
        $this->tmpl_obj_creator->setRelatedCallback($this->related_callback);
        $this->tmpl_obj_creator->setTextCallback($this->text_callback);
    }

    /**
     * Do we have a list? Does the list have articles?
     * @return bool
     */
    function havePosts() {
        return $this->list->numArticles() > 0 && $this->list->numArticles() >= $this->getOffset();
    }

    /**
     * Will render all articles in the arlima list using jQuery templates. The template to be
     * used is an option in the article list object (Arlima_List). If no template exists in declared
     * template paths we will fall back on default templates (plugins/arlima/template/[name].tmpl)
     *
     * @param bool $output[optional=true]
     * @return void
     */
    function renderList($output = true) {

        $article_counter = 0;
        $content = '';

        // Create template
        $jQueryTmpl_df = new jQueryTmpl_Data_Factory();
        $this->jQueryTmpl_default = $this->loadTemplate($this->list->options['previewtemplate'], new jQueryTmpl_Factory(), new jQueryTmpl_Markup_Factory());

        // Setup tmpl object creator
        $this->setupObjectCreator();

        $articles = array_slice($this->list->articles, $this->getOffset());

        foreach($articles as $article_data) {
            list($article_counter, $article_content) = $this->outputArticle($article_data, $jQueryTmpl_df, $article_counter);
            if( $output ) {

                echo $article_content;

                // Maybe include something after article
                $after_callback = $this->after_article_callback;
                $after_callback($article_counter, $article_data);
            }
            else
                $content .= $article_content;

            if($article_counter == $this->getLimit())
                break;
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
    protected function outputArticle($article_data, jQueryTmpl_Data_Factory $jQueryTmpl_df, $article_counter) {

        // Sticky article
        if( !empty($article_data['options']) && !empty($article_data['options']['sticky']) ) {
            if( !$this->isInStickyInterval($article_data['options']['sticky_interval']) ) {
                return array($article_counter, ''); // don't show this sticky right now
            }
        }

        // Setup
        list($post, $article, $is_post, $is_empty) = $this->setup($article_data);

        // Future article
        if( !empty($article_data['publish_date']) && $article_data['publish_date'] > $this->now) {
            $future_callback = $this->future_post_callback;
            $future_callback($post, $article, $this->getList());
            return array($article_counter, '');
        }

        // Include something before article
        $before_callback = $this->before_article_callback;
        var_dump(is_callable('Arlima_EventBinder::beforeCallback'));
        call_user_func($before_callback, $article_counter, $article, $is_post, $post);

        $tmpl_data = $this->tmpl_obj_creator->create($article, true, $is_empty, $is_post, $post, $article_counter, $this->img_size_name, true);

        // load sub articles if there's any
        if( !empty( $article[ 'children' ] ) && is_array( $article[ 'children' ] ) ) {
            $tmpl_data['sub_articles'] = $this->renderSubArticles($article[ 'children' ], $jQueryTmpl_df);
        }

        // output the article
        $content = $this->generateTemplateOutput($article, $jQueryTmpl_df, $tmpl_data);

        return array($article_counter + 1, $content);
    }

    /**
     * @param $article
     * @param jQueryTmpl_Data_Factory $jQueryTmpl_df
     * @param $tmpl_data
     * @return string
     */
    private function generateTemplateOutput($article, $jQueryTmpl_df, $tmpl_data) {
        if( !empty($article['options']) && !empty($article['options']['template']) )
            $tmpl_factory = $this->loadTemplate($article['options']['template'], new jQueryTmpl_Factory(), new jQueryTmpl_Markup_Factory());
        else
            $tmpl_factory = $this->jQueryTmpl_default;

        return $tmpl_factory->tmpl('tpl', $jQueryTmpl_df->createFromArray($tmpl_data))->getHtml();
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
    private function isInStickyInterval($sticky_interval) {
        $interval_part = explode(':', $sticky_interval);
        if(count($interval_part) == 2) {

            // Check day
            if(trim($interval_part[0]) != '*') {

                $current_day = strtolower(date('D', $this->now + (get_option( 'gmt_offset' ) * 3600 )));
                $days = array();
                foreach(explode(',', $interval_part[0]) as $day)
                    $days[] = strtolower(substr(trim($day), 0, 3));

                if( !in_array($current_day, $days) ) {
                    return false; // don't show article today
                }

            }

            // Check hour
            if(trim($interval_part[1]) != '*') {

                $current_hour = (int)date('H', $this->now + (get_option( 'gmt_offset' ) * 3600 ));
                $from_to = explode('-', $interval_part[1]);
                if(count($from_to) == 2) {
                    $from = (int)trim($from_to[0]);
                    $to = (int)trim($from_to[1]);
                    if($current_hour < $from || $current_hour > $to) {
                        return false; // don't show article this hour
                    }
                }
                else {
                    $hours = array();
                    foreach(explode(',', $interval_part[1]) as $hour)
                        $hours[] = (int)trim($hour);

                    if( !in_array($current_hour, $hours) ) {
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
    private function renderSubArticles(array $articles, jQueryTmpl_Data_Factory $jQueryTmpl_df) {
        $sub_articles = '';
        $count = 0;
        $children_count = sizeof($articles);
        $image_size = $children_count == 1 ? $this->img_size_name_sub_article_full : $this->img_size_name_sub_article;

        foreach($articles as $article_data) {

            list($post, $article, $is_post, $is_empty) = $this->setup($article_data);

            if(is_object($post) && $post->post_status == 'future') {
                continue;
            }

            $tmpl_data = $this->tmpl_obj_creator->create($article, true, $is_empty, $is_post, $post, -1, $image_size, false);
            $tmpl_data['container']['attr'] = '';
            $tmpl_data['container']['class'] = 'teaser small'.($children_count > 1 ? ' teaser-split' : '').($count%2 == 0 ? ' first' : ' last');

            $sub_articles .= $this->generateTemplateOutput($article, $jQueryTmpl_df, $tmpl_data);
            $count++;
        }

        return $sub_articles;
    }

    /**
     * @param $tmpl_name
     * @param jQueryTmpl_Factory $jQueryTmpl_Factory
     * @param jQueryTmpl_Markup_Factory $jQueryTmpl_Markup_Factory
     * @return jQueryTmpl
     */
    protected function loadTemplate($tmpl_name, jQueryTmpl_Factory $jQueryTmpl_Factory, jQueryTmpl_Markup_Factory $jQueryTmpl_Markup_Factory) {
        if( isset($this->custom_templates[$tmpl_name]) )
            return $this->custom_templates[$tmpl_name];

        $jQueryTmpl = $jQueryTmpl_Factory->create();

        $tmpl_paths = $this->template_resolver->getPaths();
        foreach($tmpl_paths as $tmpl_path) {
            $tmpl_file = $tmpl_path .DIRECTORY_SEPARATOR.$tmpl_name.Arlima_TemplatePathResolver::TMPL_EXT;
            if( file_exists($tmpl_file) ) {
                $this->custom_templates[$tmpl_name] = $this->createTemplate($tmpl_file, $jQueryTmpl, $jQueryTmpl_Markup_Factory);
                return $this->custom_templates[$tmpl_name];
            }
        }

        // If we have come this far the template doesn't exist in any template path
        trigger_error('Arlima tmpl file '. $tmpl_name .' is not present in any template path. Paths registered: '.join(',', $tmpl_paths), E_USER_WARNING);
        $tmpl_fallback = $this->template_resolver->getDefaultTemplate();
        $this->custom_templates['article'] = $this->createTemplate($tmpl_fallback, $jQueryTmpl, $jQueryTmpl_Markup_Factory);

        return $this->custom_templates['article'];
    }

    /**
     * @param string $tmpl_file
     * @param jQueryTmpl $jQueryTmpl
     * @param jQueryTmpl_Markup_Factory $jQueryTmpl_Markup_Factory
     * @return jQueryTmpl
     */
    private function createTemplate($tmpl_file, $jQueryTmpl, $jQueryTmpl_Markup_Factory) {
        $tmpl_content = file_get_contents($tmpl_file);
        preg_match_all('(\{\{include [0-9a-z\/A-Z\-\_\.]*\}\})', $tmpl_content, $sub_parts);
        if (!empty($sub_parts) && !empty($sub_parts[0])) {

            $tmpl_path = dirname($tmpl_file).'/';
            foreach ($sub_parts[0] as $tpl_part) {
                $path = str_replace(array('{{include ', '}}'), '', $tpl_part);
                $included_tmpl = $tmpl_path . $path;
                if (file_exists($included_tmpl)) {
                    $tmpl_content = str_replace($tpl_part, file_get_contents($included_tmpl), $tmpl_content);
                }
                else {
                    $tmpl_content = str_replace($tpl_part, '# ERROR: ' . $included_tmpl . ' does not exist', $tmpl_content);
                }
            }
        }

        $jQueryTmpl->template('tpl', $jQueryTmpl_Markup_Factory->createFromString($tmpl_content));
        return $jQueryTmpl;
    }
}