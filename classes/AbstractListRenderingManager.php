<?php

/**
 * Abstract class extended by classes responsible of rendering an Arlima article list
 *
 * @package Arlima
 * @since 2.0
 */
abstract class Arlima_AbstractListRenderingManager
{

    /**
     * @var Arlima_List
     */
    protected $list = null;

    /**
     * @var Closure
     */
    protected $future_post_callback = false;

    /**
     * @var Closure
     */
    protected $image_callback = false;

    /**
     * @var Closure
     */
    protected $content_callback = false;

    /**
     * @var Closure
     */
    protected $related_posts_callback = false;

    /**
     * @var string
     */
    protected $img_size_name = 'first';

    /**
     * @var string
     */
    protected $img_size_name_sub_article = 'first-child';

    /**
     * @var string
     */
    protected $img_size_name_sub_article_full = 'first-child-full';

    /**
     * @var bool
     */
    protected $setup_wp_post_data = true;

    /**
     * @var Closure
     */
    protected $article_begin_callback = false;

    /**
     * @var Closure
     */
    protected $article_end_callback = false;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var int
     */
    private $limit = -1;


    /**
     * Class constructor
     * @param Arlima_List|stdClass $list
     */
    function __construct($list)
    {
        $this->list = $list;
    }



    /* * * * * * * * * * * * LIST CALLBACKS * * * * * * * * * * */



    /**
     * Makes it possible to add content that is supposed to be put in
     * the end of the article
     * @param Closure $callback_func
     */
    function setArticleEndCallback($callback_func)
    {
        $this->article_end_callback = $callback_func;
    }

    /**
     * This callback will be called once for each article in the list, right before the
     * article is rendered. Should return content that is placed before article content
     * @param Closure $callback_func
     */
    function setArticleBeginCallback($callback_func)
    {
        $this->article_begin_callback = $callback_func;
    }

    /**
     * Function that will be called every time list contains a future post, instead of rendering the article
     * @param Closure|bool $callback_func
     */
    function setFuturePostCallback($callback_func)
    {
        $this->future_post_callback = $callback_func;
    }

    /**
     * This callback should return the html code for the image
     * @param Closure $callback_func
     */
    function setImageCallback($callback_func)
    {
        $this->image_callback = $callback_func;
    }

    /**
     * This callback should return the final preamble text of the article as a string
     * @param Closure $callback_func
     */
    function setContentCallback($callback_func)
    {
        $this->content_callback = $callback_func;
    }

    /**
     * This callback should return a string with the html code for content that is
     * related to this post
     *
     * @param Closure $callback_func
     */
    function setRelatedPostsCallback($callback_func)
    {
        $this->related_posts_callback = $callback_func;
    }



    /* * * * * * * * * * * * LIST RENDERING FUNCTIONS * * * * * * * * * * * * * */



    /**
     * Do we have a list? Does the list have articles?
     * @abstract
     * @return bool
     */
    abstract function havePosts();

    /**
     * Render the list of articles
     * @abstract
     * @param bool $output[optional=true]
     * @return string
     */
    abstract function renderList($output = true);

    /**
     * Use the article object/array and set up the wordpress environment
     * as if we were in an ordinary wordpress loop, right after having called the_post();
     *
     * @param array|stdClass $article
     * @return array
     */
    protected function setup($article)
    {
        $is_post = false;
        $post = false;

        if ( !empty($article['post_id']) && is_numeric($article['post_id']) ) {
            global $post;

            if ( $this->setup_wp_post_data ) {
                $post = get_post($article['post_id']);
            }

            if ( $post ) {
                $is_post = true;
            }
        }

        $is_empty = false;
        if ( empty($article['text']) && empty($article['title']) && empty($article['image']) ) {
            $is_empty = true;
        }

        return array($post, $article, $is_post, $is_empty);
    }




    /* * * * * * * * * * * * SETTERS ´n GETTERS * * * * * * * * * * * * */



    /**
     * @param string $img_size_name_sub_article_full
     */
    public function setImgSizeNameSubArticleFull($img_size_name_sub_article_full)
    {
        $this->img_size_name_sub_article_full = $img_size_name_sub_article_full;
    }

    /**
     * @return string
     */
    public function getImgSizeNameSubArticleFull()
    {
        return $this->img_size_name_sub_article_full;
    }

    /**
     * @param string $img_size_name_sub_article
     */
    public function setImgSizeNameSubArticle($img_size_name_sub_article)
    {
        $this->img_size_name_sub_article = $img_size_name_sub_article;
    }

    /**
     * @return string
     */
    public function getImgSizeNameSubArticle()
    {
        return $this->img_size_name_sub_article;
    }

    /**
     * @param string $img_size_name
     */
    public function setImgSizeName($img_size_name)
    {
        $this->img_size_name = $img_size_name;
    }

    /**
     * @return string
     */
    public function getImgSizeName()
    {
        return $this->img_size_name;
    }

    /**
     * @param \Arlima_List $list
     */
    public function setList($list)
    {
        $this->list = $list;
    }

    /**
     * @return Arlima_List
     */
    public function getList()
    {
        return $this->list;
    }


    /**
     * Set to -1 to not limit the number of articles that will be rendered
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = (int)$limit;
        if ( !$this->limit ) {
            $this->limit = -1;
        }
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = (int)$offset;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }


    /* * * * * * * *  DEPRECATED  * * * * * * */


    /**
     * @deprecated
     * @see Arlima_AbstractListManager::setContentCallback()
     */
    function setTextModifierCallback($callback_func)
    {
        $this->setContentCallback($callback_func);
    }

    /**
     * @deprecated
     * @see Arlima_AbstractListManager::setImageCallback()
     */
    function setGetImageCallback($callback_func)
    {
        $this->setImageCallback($callback_func);
    }

    /**
     * @deprecated
     * @see Arlima_AbstractListManager::setArticleBeginCallback()
     */
    function setBeforeArticleCallback($callback_func)
    {
        $this->setArticleBeginCallback($callback_func);
    }

    /**
     * @deprecated
     * @see Arlima_AbstractListManager::setArticleEndCallback()
     */
    function setAfterArticleCallback($callback_func)
    {
        $this->setArticleEndCallback($callback_func);
    }
}
