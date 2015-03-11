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
     * Set $echo_output to false to rendered list as a string
     * @param bool $echo_output
     * @return string
     */
    function generateListHtml($echo_output = true)
    {
        // Set default template
        try {
            $this->template_engine->setDefaultTemplate($this->list->getOption('template'));
        } catch(Exception $e) {
            $message = 'You are using a default template for the list "'.$this->list->getTitle().'" that could not be found';
            if( $echo_output ) {
                echo $message;
            } else {
                return $message;
            }
        }

        return $this->runArticleLoop($echo_output);
    }

    /**
     * @param int $post_id
     * @param int $article_counter
     * @return Arlima_Article
     */
    protected function createArticleFromPost($post_id, $article_counter)
    {
        $article = $this->cms->postToArlimaArticle($post_id, $this->default_article_props);
        $article_data = $article->toArray();
        $article_data['title'] = call_user_func($this->header_callback, $article_counter, $article, $post_id, $this->list);
        $article_data['content'] = $this->cms->applyFilters('the_content', $this->cms->getExcerpt($post_id), 'arlima-list');
        $article_data['content'] = $this->cms->sanitizeText($article_data['content'], '<strong><a><br><p><img/>');
        $article_data['image'] = $this->cms->getArlimaArticleImageFromPost($post_id);

        $article = new Arlima_Article($article_data);
        $article = $this->cms->applyFilters('arlima_wp_loop_article', $article, $post_id); // Backwards compat
        $article = $this->cms->applyFilters('arlima_cms_loop_article', $article, $post_id);
        return is_array($article) ? new Arlima_Article($article) : $article;
    }

    /**
     * @param $arr
     */
    public function setDefaultArticleProperties($arr)
    {
        $this->default_article_props = $arr;
    }

    /**
     * @param bool $echo_output
     * @return string|void
     */
    private function runArticleLoop($echo_output)
    {
        $article_counter = 0;
        $content = '';

        while ($this->cms->havePostsInLoop()) {
            if ($this->getOffset() > $article_counter) {
                $article_counter++;
                continue;
            }

            $post_id = $this->cms->getPostIDInLoop();
            $article = $this->createArticleFromPost($post_id, $article_counter);

            if (!in_array($post_id, $this->exclude_posts)) {

                list($article_counter, $article_content) = $this->renderArticle(
                    $article,
                    $article_counter
                );

                if ($echo_output) {
                    echo $article_content;
                } else {
                    $content .= $article_content;
                }
            }

            if ($article_counter >= 50 || ($this->getLimit() > -1 && $this->getLimit() <= $article_counter)) {
                break;
            }
        }

        return $content;
    }
}