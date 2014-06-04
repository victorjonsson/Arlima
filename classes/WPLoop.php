<?php

/**
 * Class that makes it possible to use Arlima_ListTemplateRenderer on ordinary
 * wordpress loops (while have_posts() => the_post() ...)
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_WPLoop extends Arlima_ListTemplateRenderer
{

    /**
     * @var array
     */
    private $exclude_posts = array();

    /**
     * @var callable
     */
    private $header_callback = 'Arlima_WPLoop::defaultHeaderCallback';

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
        $list->setOption('title', 'WP Arlima Loop');
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
     * @param array $article
     * @param stdClass $post
     * @param Arlima_List $list
     * @return mixed
     */
    public static function defaultHeaderCallback($article_counter, $article, $post, $list) {
        return $article['title'];
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

        while ( $this->system->havePostsInLoop() ) {
            if ( $this->getOffset() > $article_counter ) {
                $article_counter++;
                continue;
            }

            $post = $this->system->getPostInLoop();
            $template_data = $this->extractTemplateData($post, $article_counter);

            if( $template_data && !in_array($post->ID, $this->exclude_posts) ) {

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
     * @param WP_Post $post
     * @param int $article_counter
     * @return array
     */
    protected function extractTemplateData($post, $article_counter)
    {
        $article = Arlima_ListFactory::postToArlimaArticle($post, null, $this->default_article_props);
        $article['html_title'] = call_user_func($this->header_callback, $article_counter, $article, $post, $this->list);
        $article['html_content'] = $this->system->applyFilters('the_content', get_the_content(), 'arlima-list');
        $article['html_content'] = $this->system->applyFilters('the_content', get_the_content());
        $article['image'] = $this->system->getArlimaArticleImageFromPost($post->ID);
        return $this->system->applyFilters('arlima_wp_loop_article', $article, $post);
    }

    /**
     * @param $arr
     */
    public function setDefaultArticleProperties($arr)
    {
        $this->default_article_props = $arr;
    }
}