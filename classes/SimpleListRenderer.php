<?php

/**
 * The most simple type of list renderer
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_SimpleListRenderer extends Arlima_AbstractListRenderingManager {

    private $display_post_callback;

    /**
     * @param Arlima_List|null $list
     */
    function __construct($list) {
        parent::__construct($list);
        $this->display_post_callback = function($post, $article, $is_post, $article_counter) {
            echo '<br />No callback given, this is article #'.$post->ID;
        };
    }

    /**
     * @param Closure $func
     */
    function setDisplayPostCallback($func) {
        $this->display_post_callback = $func;
    }

    /**
     * Do we have a list? Does the list have articles?
     * @return bool
     */
    function havePosts() {
        return !empty($this->list->articles);
    }

    /**
     * Note that no callbacks will be fired except display_post()
     * Render the list of articles
     * @return void
     */
    function renderList() {
        $article_counter = 0;
        $display_callback = $this->display_post_callback;

        foreach(array_slice($this->list->articles, $this->getOffset()) as $article) {
            list($post, $article, $is_post) = $this->setup($article);
            if(is_object($post) && $post->post_status == 'future') {
                continue;
            }

            $display_callback($post, $article, $is_post, $article_counter);

            $article_counter++;
            if($article_counter == $this->getLimit())
                break;
        }

        // unset global post data
        $GLOBALS['post'] = null;
    }
}