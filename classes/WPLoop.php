<?php

/**
 * Class that makes it possible to use ArlimaAbstractListTemplateRenderer on ordinary
 * wordpress loops (while have_posts() => the_post() ...)
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_WPLoop extends Arlima_ListTemplateRenderer {

    private $header_callback;

    /**
     * @param string $tmpl_path - Optional path to directory where templates should exists (see readme.txt about how to add your own template paths from the theme)
     * @param string $tmpl - Optional name of template file to be used (no extension)
     */
    function __construct($tmpl_path=null, $tmpl = Arlima_TemplatePathResolver::DEFAULT_TMPL) {

        // construct an object implementing the same interface as Arlima_List
        $list = new stdClass();
        $list->options = array(
            'previewtemplate' => $tmpl // todo: rename from 'previewtemplate' to 'template'
        );

        parent::__construct($list, $tmpl_path);

        $this->header_callback = function($article) {
            return $article['html_title'];
        };
    }

    /**
     * @param Closure $callback
     */
    function setHeaderCallback($callback) {
        $this->header_callback = $callback;
    }

    /**
     * @return bool
     */
    function havePosts() {
        return have_posts();
    }

    /**
     * @param bool $output
     * @return string
     */
    function renderList($output = true) {
        $article_counter = 0;
        $content = '';

        // Create template
        $jQueryTmpl_df = new jQueryTmpl_Data_Factory();
        $this->jQueryTmpl_default = $this->loadTemplate($this->list->options['previewtemplate'], new jQueryTmpl_Factory(), new jQueryTmpl_Markup_Factory());

        // Setup tmpl object creatorv
        $this->setup_wp_post_data = false; // prevent this class from overwriting the global post object

        $this->setupObjectCreator();

        while( have_posts() ) {
            if($this->getOffset() > $article_counter) {
                $article_counter++;
                continue;
            }

            the_post();
            global $post;

            $article_data = $this->extractArticleData($post);
            list($article_counter, $article_content) = $this->outputArticle($article_data, $jQueryTmpl_df, $article_counter);

            if( $output ) {

                echo $article_content;

                // Maybe include something after article
                $after_callback = $this->after_article_callback;
                $after_callback($article_counter, $article_data);
            }
            else {
                $content .= $article_content;
            }

            if( $article_counter >= 50 || ($this->getLimit() > -1 && $this->getLimit() <= $article_counter) )
                break;
        }

        // unset global post data
        $GLOBALS['post'] = null;

        return $content;
    }

    /**
     * @param stdClass $post
     * @return array
     */
    protected function extractArticleData(stdClass $post) {
        $date = strtotime($post->post_date_gmt);
        $article = array(
            'post_id' => $post->ID,
            'options' => array(
                'hiderelated' => false
            ),
            'title' => get_the_title($post->ID),
            'html_title' => '<h2>'.$post->post_title.'</h2>',
            'url' => get_permalink($post->ID),
            'text' => '',
            'created' => $date,
            'publish_date' => $date
        );

        $article = Arlima_ListFactory::createArticleDataArray($article);
        $header_callback = $this->header_callback;
        $article['title_html'] = $header_callback($article);
        $article['text'] = apply_filters('the_content', get_the_content());

        return $article;
    }
}