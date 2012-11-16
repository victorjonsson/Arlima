<?php


/**
 * The most simple type of list renderer
 *
 * @example
 *  <code>
 *  <?php
 *  $list = $list_factory->loadListBySlug('my-arlima-list');
 *  $renderer = new Arlima_SimpleListRenderer($list);
 *  $renderer->setDisplayPostCallback(function($article_counter, $article, $post, $list) {
 *      return '...';
 *  });
 *
 *  $renderer->renderList();
 *  </code>
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_SimpleListRenderer extends Arlima_AbstractListRenderingManager
{

    /**
     * @var callable
     */
    private $display_post_callback = 'Arlima_SimpleListRenderer::defaultPostDisplayCallback';

    /**
     * @param Arlima_List|null $list
     */
    function __construct($list)
    {
        parent::__construct($list);
    }

    /**
     * @param int $article_counter
     * @param array $article
     * @param stdClass|false $post
     * @param Arlima_List $list
     */
    public static function defaultPostDisplayCallback($article_counter, $article, $post, $list) {
        echo '<br />No callback given, this is article #' . $post->ID;
    }

    /**
     * @param Closure $func
     */
    function setDisplayPostCallback($func)
    {
        $this->display_post_callback = $func;
    }

    /**
     * Do we have a list? Does the list have articles?
     * @return bool
     */
    function havePosts()
    {
        return $this->list->numArticles() > 0;
    }

    /**
     * Note that no callbacks will be fired except display_post()
     * @see Arlima_SimpleListRendered::setDisplayPostCallback()
     * @param bool $output
     * @return string
     */
    function renderList($output = true)
    {
        $content = '';
        $article_counter = 0;
        foreach (array_slice($this->list->getArticles(), $this->getOffset()) as $article) {
            list($post, $article) = $this->setup($article);
            if ( is_object($post) && $post->post_status == 'future' ) {
                continue;
            }

            $article_content = call_user_func($this->display_post_callback, $article_counter, $article, $post, $this->list );

            if( $output ) {
                echo $article_content;
            } else {
                $content .= $article_content;
            }

            $article_counter++;
            if ( $article_counter == $this->getLimit() ) {
                break;
            }
        }

        // unset global post data
        $GLOBALS['post'] = null;

        return $content;
    }
}