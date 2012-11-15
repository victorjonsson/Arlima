<?php

/**
 * Abstract class extended by classes responsible of rendering an Arlima article list
 *
 * @package Arlima
 * @since 2.0
 */
abstract class Arlima_AbstractListRenderingManager {

    /**
     * @var Arlima_List
     */
    protected $list = null;

    /**
     * @var Closure
     */
    protected $future_post_callback;

    /**
     * @var Closure
     */
    protected $get_article_image_callback;

    /**
     * @var Closure
     */
    protected $text_callback;

    /**
     * @var Closure
     */
    protected $related_callback;

    /**
     * @var Closure
     */
    protected $before_article_callback;

    /**
     * @var Closure
     */
    protected $after_article_callback;

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
    function __construct($list) {
        $this->list = $list;
    }

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
     * @return void
     */
    abstract function renderList($output = true);

    /**
     * Makes it possible to add content that is supposed to be put in
     * the end of the article
     * @param Closure $callback_func
     */
    function setArticleEndCallback($callback_func) {
        $this->article_end_callback = $callback_func;
    }

    /**
     * Function that will be called every time list contains a future post, instead of rendering the article
     * @param Closure|bool $callback_func
     */
    function setFuturePostCallback($callback_func) {
        $this->future_post_callback = $callback_func;
    }

    /**
     * This callback should return the html code for the image
     * @param Closure $callback_func
     */
    function setGetImageCallback($callback_func) {
        $this->get_article_image_callback = $callback_func;
    }

    /**
     * This callback should return the final preamble text of the article as a string
     * @param Closure $callback_func
     */
    function setTextModifierCallback($callback_func) {
        $this->text_callback = $callback_func;
    }

    /**
     * This callback should return a string with the html code for content that is
     * related to this post
     *
     * @todo rename this function to something more generic like "setRelatedContentCallback"
     * @param Closure $callback_func
     */
    function setRelatedPostsCallback($callback_func) {
        $this->related_callback = $callback_func;
    }

    /**
     * This callback will be called once for each article in the list, right before the
     * article is rendered
     * @param Closure $callback_func
     */
    function setBeforeArticleCallback($callback_func) {
        $this->before_article_callback = $callback_func;
    }

    /**
     * This callback will be called once for each article in the list, right after the
     * article is rendered
     * @param Closure $callback_func
     */
    function setAfterArticleCallback($callback_func) {
        $this->after_article_callback = $callback_func;
    }

    /**
     * Use the article object/array and set up the wordpress environment
     * as if we were in an ordinary wordpress loop, right after having called the_post();
     *
     * @param array|stdClass $article
     * @return array
     */
    protected function setup($article) {
        $is_post = false;
        $post = false;

        if( is_object ( $article ) )  // todo: make sure that this variable never becomes/is an object and remove this code
            $article = get_object_vars( $article );

        if( !empty($article['post_id']) && is_numeric( $article['post_id'] ) ) {
            global $post;

            if( $this->setup_wp_post_data )
                $post = get_post( $article['post_id'] );

            if( $post ) {
                $is_post = true;
            }
        }

        $is_empty = false;
        if( empty( $article[ 'text' ] ) && empty( $article[ 'title' ] ) && empty( $article[ 'image' ] ) ){
            $is_empty = true;
        }

        return array($post, $article, $is_post, $is_empty);
    }

    /**
     * @param string $img_size_name_sub_article_full
     */
    public function setImgSizeNameSubArticleFull($img_size_name_sub_article_full) {
        $this->img_size_name_sub_article_full = $img_size_name_sub_article_full;
    }

    /**
     * @return string
     */
    public function getImgSizeNameSubArticleFull() {
        return $this->img_size_name_sub_article_full;
    }

    /**
     * @param string $img_size_name_sub_article
     */
    public function setImgSizeNameSubArticle($img_size_name_sub_article) {
        $this->img_size_name_sub_article = $img_size_name_sub_article;
    }

    /**
     * @return string
     */
    public function getImgSizeNameSubArticle() {
        return $this->img_size_name_sub_article;
    }

    /**
     * @param string $img_size_name
     */
    public function setImgSizeName($img_size_name) {
        $this->img_size_name = $img_size_name;
    }

    /**
     * @return string
     */
    public function getImgSizeName() {
        return $this->img_size_name;
    }

    /**
     * @param \Arlima_List $list
     */
    public function setList($list) {
        $this->list = $list;
    }

    /**
     * @return Arlima_List
     */
    public function getList() {
        return $this->list;
    }


    /**
     * Set to -1 to not limit the number of articles that will be rendered
     * @param int $limit
     */
    public function setLimit($limit) {
        $this->limit = (int)$limit;
        if($this->limit === false)
            $this->limit = -1;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset) {
        $this->offset = (int)$offset;
        if($this->offset === false)
            $this->offset = 0;
    }

    /**
     * @return int
     */
    public function getOffset() {
        return $this->offset;
    }
}
