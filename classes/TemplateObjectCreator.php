<?php

/**
 * Class with all the knowledge about how to convert a typical arlima
 * article array to jquery-tmpl objects
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_TemplateObjectCreator
{

    /**
     * @var bool
     */
    private $display_streamer = true;

    /**
     * @var string|bool
     */
    private $is_child = false;

    /**
     * @var string|bool
     */
    private $is_child_split = false;

    /**
     * @var string
     */
    private $img_size = '';

    /**
     * @var Closure
     */
    private $content_callback = false;

    /**
     * @var Closure
     */
    private $image_callback = false;

    /**
     * @var Closure
     */
    private $related_callback = false;

    /**
     * @var Closure
     */
    private $article_end_callback = false;

    /**
     * @var Closure
     */
    private $article_begin_callback = false;

    /**
     * @var string
     */
    private $before_title_html = '<h2>';

    /**
     * @var string
     */
    private $after_title_html = '</h2>';

    /**
     * @var bool
     */
    private $add_title_font_size = true;

    /**
     * @var Arlima_List
     */
    private $list;

    /**
     * @param Arlima_List $list
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
     * @param boolean $display_streamer
     */
    public function setDisplayStreamer($display_streamer)
    {
        $this->display_streamer = $display_streamer;
    }

    /**
     * @return boolean
     */
    public function getDisplayStreamer()
    {
        return $this->display_streamer;
    }

    /**
     * Returns an empty jquery tmpl data object
     * @return array
     */
    private function getEmptyObjectArray()
    {
        return array(
            'container' => array(
                'id' => '',
                'class' => 'teaser'
            ),
            'article' => array(
                'title' => '',
                'url' => '',
                'html_title' => '',
                'html_text' => false
            ),
            'sub_articles' => false, // deprecated
            'child_articles' => false,
            'streamer' => false,
            'related' => false,
            'before_related' => false,
            'article_begin' => false,
            'article_end' => false,
            'is_child' => false,
            'image' => false
        );
    }


    /**
     * @param string $img_size
     */
    public function setImgSize($img_size)
    {
        $this->img_size = $img_size;
    }

    /**
     * @return string
     */
    public function getImgSize()
    {
        return $this->img_size;
    }

    /**
     * @param bool|string $is_child
     */
    public function setIsChild($is_child)
    {
        $this->is_child = $is_child;
    }

    /**
     * @return bool|string
     */
    public function getIsChild()
    {
        return $this->is_child;
    }

    /**
     * @param bool|string $is_child_split
     */
    public function setIsChildSplit($is_child_split)
    {
        $this->is_child_split = $is_child_split;
    }

    /**
     * @return bool|string
     */
    public function getIsChildSplit()
    {
        return $this->is_child_split;
    }

    /**
     * @param $article
     * @param $is_empty
     * @param $post
     * @param $article_counter
     * @param bool $load_related_articles
     * @param null $template_name
     * @return array
     */
    public function create(
        $article,
        $is_empty,
        $post,
        $article_counter,
        $load_related_articles = true,
        $template_name = null
    ) {
        $obj = $this->getEmptyObjectArray();
        $has_streamer = $this->display_streamer && isset($article['options']['streamer']);
        $img_opt_size = isset($article['image_options']) && !empty($article['image_options']['size']) ? $article['image_options']['size'] : false;

        $url = isset($article['url']) ? $article['url'] : '';
        $post_id = !empty($article['post_id']) ? $article['post_id'] : '';

        //$obj['container']['id'] = 'teaser-' . (is_numeric($post_id) ? $post_id : $url); // deprecated
        $obj['container']['id'] = $article['id'];
        $obj['container']['class'] = 'teaser' . ($is_empty ? ' empty' : '');
        $obj['article']['title'] = isset($article['title']) ? $article['title'] : '';
        $obj['article']['html_title'] = $is_empty ? '' : Arlima_List::getTitleHtml($article, array('before_title'=>$this->before_title_html, 'after_title'=>$this->after_title_html));
        $obj['article']['url'] = $url;
        $obj['article']['publish_date'] = $article['publish_date'];
        $obj['article']['post'] = $article['post_id'];
        $obj['is_child'] = $this->is_child;
        $obj['is_child_split'] = $this->is_child_split;

        if ( !empty($article['options']) && !empty($article['options']['format']) ) {
            $obj['container']['class'] .= ' ' . $article['options']['format'];
            $obj['container']['format'] = $article['options']['format'];
        }
        if( $has_streamer ) {
            $obj['container']['class'] .= ' has-streamer';
        }

        // Add article end content
        if ($this->article_begin_callback !== false) {
            $obj['article_begin'] = call_user_func($this->article_begin_callback, $article_counter, $article, $post, $this->list);
        }

        if ( !$is_empty ) {

            $this->generateImageData($article, $this->img_size, $article_counter, $obj, $img_opt_size, $post);

            // Text content
            if( $this->content_callback !== false ) {
                $obj['article']['html_content'] = call_user_func($this->content_callback, $article, !empty($post), $post, $article_counter, $this->list);
                $obj['article']['html_text'] = $obj['article']['html_content']; // deprecated todo: remove on v 3.0
            }

            $this->generateStreamerData($has_streamer, $obj, $article);

            // Related posts
            if ( $load_related_articles ) {
                if ( empty($article['options']['hiderelated']) && $this->related_callback !== false ) {
                    $obj['related'] = call_user_func($this->related_callback, $article_counter, $article, $post, $this->list);
                }
            }
        }

        // Add article end content
        if ($this->article_end_callback !== false) {
            $obj['article_end'] = call_user_func($this->article_end_callback, $article_counter, $article, $post, $this->list);
        }

        // child classes
        if( $this->is_child ) {
            $obj['container']['class'] .= ' teaser-child';
        }
        if( $this->is_child_split ) {
            $obj['container']['class'] .= ' teaser-split'; // is one out of many children
        }

        $filter_suffix = Arlima_FilterApplier::getFilterSuffix(); 
        return apply_filters('arlima_template_object'. ($filter_suffix ? '-'.$filter_suffix:''), $obj, $article, $this->list, $template_name);
    }

    /**
     * @param $article
     * @param $img_size
     * @param $article_counter
     * @param $data
     * @param $img_opt_size
     * @param $post
     */
    protected function generateImageData($article, $img_size, $article_counter, &$data, $img_opt_size, $post)
    {
        if( $this->image_callback !== false) {
            $img = call_user_func($this->image_callback, $article, $article_counter, $post, $this->list, $img_size, $this->is_child_split);

            if ( $img || !empty($article['image_options']['url']) ) {

                if ( empty($article['image_options']['url']) ) {
                    preg_match('/src="([^"]*)"/i', $img, $arr);
                    if ( !empty($arr[1]) ) {
                        $article['image_options']['url'] = $arr[1];
                    } else {
                        $article['image_options']['url'] = false;
                    }
                }

                $data['image'] = array(
                    'html' => $img,
                    'src' => false, // src is actually only used by javascript in wp-admin,
                    'url' => $article['image_options']['url'], // this variable is only available in front end
                );
                $data['container']['class'] .= $img_opt_size ? ' img-' . $img_opt_size : ' has-img';
            } else {
                $data['container']['class'] .= ' no-img';
            }
        }
    }

    /**
     * @param $has_streamer
     * @param &$data
     * @param $article
     */
    protected function generateStreamerData($has_streamer, &$data, $article)
    {
        $data['container']['class'] .= ($has_streamer ? '' : ' no-streamer');
        if ( $has_streamer ) {
            $data['streamer']['type'] = $article['options']['streamer_type'];
            switch ($data['streamer']['type']) {
                case 'extra' :
                    $data['streamer']['content'] = 'EXTRA';
                    break;
                case 'image' :
                    $data['streamer']['content'] = '<img src="' . $article['options']['streamer_image'] . '" alt="Streamer" />';
                    break;
                default :
                    $data['streamer']['content'] = $article['options']['streamer_content'];
            }

            $data['streamer']['style'] = !empty($article['options']['streamer_color']) && $data['streamer']['type'] == 'text' ? 'background: #' . $article['options']['streamer_color'] : '';
        }
    }

    /**
     * @param boolean $toggle
     */
    public function doAddTitleFontSize($toggle)
    {
        $this->add_title_font_size = $toggle;
    }

    /**
     * @param string $after_title_html
     */
    public function setAfterTitleHtml($after_title_html)
    {
        $this->after_title_html = $after_title_html;
    }

    /**
     * @param Closure $article_end_callback
     */
    public function setArticleEndCallback($article_end_callback)
    {
        $this->article_end_callback = $article_end_callback;
    }

    /**
     * @param $article_begin_callback
     */
    public function setArticleBeginCallback($article_begin_callback)
    {
        $this->article_begin_callback = $article_begin_callback;
    }

    /**
     * @param string $before_title_html
     */
    public function setBeforeTitleHtml($before_title_html)
    {
        $this->before_title_html = $before_title_html;
    }

    /**
     * @param Closure $get_article_image_callback
     */
    public function setImageCallback($get_article_image_callback)
    {
        $this->image_callback = $get_article_image_callback;
    }

    /**
     * @param Closure $related_callback
     */
    public function setRelatedCallback($related_callback)
    {
        $this->related_callback = $related_callback;
    }

    /**
     * @param Closure $text_callback
     */
    public function setContentCallback($text_callback)
    {
        $this->content_callback = $text_callback;
    }
}