<?php

/**
 * Class that makes it possible to use Arlima_ListTemplateRenderer on ordinary
 * article list iterations done by the underlying CMS
 *
 * @package Arlima
 * @since 3.1 (renamed WPLoop created in v2.0)
 */
class Arlima_CMSLoop extends Arlima_ListTemplateRenderer
{

    /**
     * @var array
     */
    private $exclude_posts = array();

    /**
     * @var callable
     */
    private $header_callback = 'Arlima_CMSLoop::defaultHeaderCallback';

    /**
     * @var array
     */
    private $default_article_props = array();

    /**
     * @param string $template_path - Optional path to directory where templates should exists (see readme.txt about how to add your own template paths from the theme)
     */
    function __construct($template_path = null)
    {
        $list = new Arlima_List();
        parent::__construct($list, $template_path);
    }

    /**
     * @param array $exclude_posts
     */
    public function setExcludePosts($exclude_posts)
    {
        $this->exclude_posts = $exclude_posts;
    }

    /**
     * @return array
     */
    public function getExcludePosts()
    {
        return $this->exclude_posts;
    }

    /**
     * @param int $article_counter
     * @param Arlima_Article $article
     * @param stdClass $post_id
     * @param Arlima_List $list
     * @return mixed
     */
    public static function defaultHeaderCallback($article_counter, $article, $post_id, $list) {
        return $article->getTitle('<br />', true);
    }

    /**
     * @param Closure $callback
     */
    function setHeaderCallback($callback)
    {
        $this->header_callback = $callback;
    }

    /**
     * @return bool
     */
    function havePosts()
    {
        return have_posts();
    }

    /**
     * @param bool $output
     * @return string
     */
    function generateListHtml($output = true)
    {
        $article_counter = 0;
        $content = '';

        // Set default template
        try {
            $this->template_engine->setDefaultTemplate($this->list->getOption('template'));
        } catch(Exception $e) {
            $message = 'You are using a default template for the list "'.$this->list->getTitle().'" that could not be found';
            if( $output ) {
                echo $message;
            } else {
                return $message;
            }
        }

        while ( $this->cms->havePostsInLoop() ) {
            if ( $this->getOffset() > $article_counter ) {
                $article_counter++;
                continue;
            }

            $post_id = $this->cms->getPostIDInLoop();
            $template_data = $this->extractTemplateData($post_id, $article_counter);

            if( $template_data && !in_array($post_id, $this->exclude_posts) ) {

                list($article_counter, $article_content) = $this->renderArticle(
                    $template_data,
                    $article_counter
                );

                if ( $output ) {
                    echo $article_content;

                } else {
                    $content .= $article_content;
                }
            }

            if ( $article_counter >= 50 || ($this->getLimit() > -1 && $this->getLimit() <= $article_counter) ) {
                break;
            }
        }

        return $content;
    }

    /**
     * @param int $post_id
     * @param int $article_counter
     * @return array
     */
    protected function extractTemplateData($post_id, $article_counter)
    {
        $article = $this->cms->postToArlimaArticle($post_id, $this->default_article_props);
        $article['html_title'] = call_user_func($this->header_callback, $article_counter, $article, $post_id, $this->list);
        $article['html_content'] = $this->cms->applyFilters('the_content', $this->cms->getContentOfPostInGlobalScope(), 'arlima-list');
        $article['image'] = $this->cms->getArlimaArticleImageFromPost($post_id);
        $article = $this->cms->applyFilters('arlima_wp_loop_article', $article, $post_id); // Backwards compat
        return $this->cms->applyFilters('arlima_cms_loop_article', $article, $post_id);
    }

    /**
     * @param $arr
     */
    public function setDefaultArticleProperties($arr)
    {
        $this->default_article_props = $arr;
    }
}