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
    private $display_article_callback = 'Arlima_SimpleListRenderer::defaultPostDisplayCallback';

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
     * @param WP_Post|bool $post
     * @param Arlima_List $list
     * @return string
     */
    public static function defaultPostDisplayCallback($article_counter, $article, $post, $list) {
        return '<p>No callback given for article' . ($post ? '(post &quot;'.$post->post_title.'&quot;' : '').'</p>';
    }

    /**
     * @param Closure $func
     */
    function setDisplayArticleCallback($func)
    {
        $this->display_article_callback = $func;
    }

    /**
     * Note that no callbacks will be fired except display_post()
     * @see Arlima_SimpleListRendered::setDisplayPostCallback()
     * @param bool $output
     * @return string
     */
    function renderList($output = true)
    {
        $current_global_post = $GLOBALS['post'];
        $content = '';
        $article_counter = 0;
        foreach ($this->getArticlesToRender() as $article) {
            list($post, $article) = $this->setup($article);
            if ( !empty($article_data['publish_date']) && $article_data['publish_date'] > time() ) {
                continue;
            }

            $article_content = call_user_func($this->display_article_callback, $article_counter, $article, $post, $this->list );

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
        $GLOBALS['post'] = $current_global_post;

        return $content;
    }
}