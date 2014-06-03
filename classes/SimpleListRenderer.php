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
     * @var bool
     */
    private $echo = true;

    /**
     * @param Arlima_List $list
     * @param bool $echo
     */
    function __construct($list, $echo = true)
    {
        $this->echo = $echo;
        parent::__construct($list);
    }

    /**
     * @param int $article_counter
     * @param array $article
     * @param WP_Post|bool $post
     * @param Arlima_AbstractListRenderingManager $renderer
     * @param bool $echo
     * @return string
     */
    public static function defaultPostDisplayCallback($article_counter, $article, $post, $renderer, $echo) {
        return '<p>No callback given for article' . ($post ? '(post &quot;'.$post->post_title.'&quot;' : '').'</p>';
    }

    /**
     * @param \Closure $func
     */
    function setDisplayArticleCallback($func)
    {
        $this->display_article_callback = $func;
    }

    /**
     * @see Arlima_SimpleListRendered::setDisplayPostCallback()
     * @param bool $output
     * @return string
     */
    function generateListHtml($output = true)
    {
        $content = '';
        $article_counter = 0;
        foreach ($this->getArticlesToRender() as $article) {

            list($index, $article_content) = $this->renderArticle($article, $article_counter);

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

        return $content;
    }

    /**
     * @param array $article_data
     * @param int $index
     * @param null|stdClass|WP_Post $post
     * @param $is_empty
     * @return mixed
     */
    protected function generateArticleHtml($article_data, $index, $post, $is_empty)
    {
        return call_user_func($this->display_article_callback, $index, $article_data, $post, $this);
    }
}