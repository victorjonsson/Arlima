<?php

/**
 * Class with all the knowledge about how to convert a typical arlima
 * article array to an object used when the TemplateEngine constructs
 * the articles view (template)
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_TemplateObjectCreator
{

    /**
     * @var string|bool
     */
    private $is_child = false;

    /**
     * @var null|array
     */
    private $child_split_state = null;

    /**
     * @var Arlima_List
     */
    private $list;

    /**
     * @var string
     */
    private $after_title_html = '';

    /**
     * @var string
     */
    private $before_title_html = '';

    /**
     * @var string
     */
    private static $filter_suffix='';

    /**
     * @var int
     */
    private static $width = 468;

    /**
     * @var Arlima_CMSInterface
     */
    private $cms;

    /**
     */
    public function __construct()
    {
        $this->cms = Arlima_CMSFacade::load();
    }

    /**
     * @param string $s
     */
    public static function setFilterSuffix($s)
    {
        self::$filter_suffix = $s;
    }

    /**
     * @return string
     */
    public static function getFilterSuffix()
    {
        return self::$filter_suffix;
    }

    /**
     * @param int $width
     */
    public static function setArticleWidth($width)
    {
        self::$width = $width;
    }

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
     * @param null|array $split_state
     */
    public function setChildSplitState($split_state)
    {
        if (!is_array($split_state) || $split_state['count'] <= 1) {
            $split_state = null;
        }
        $this->child_split_state = $split_state;
    }

    /**
     * @return null|array
     */
    public function getChildSplitState()
    {
        return $this->child_split_state;
    }

    /**
     * @param $article
     * @param $is_empty
     * @param $post
     * @param $article_counter
     * @param null $template_name
     * @return array
     */
    public function create(
        $article,
        $is_empty,
        $post,
        $article_counter,
        $template_name = null
    ) {
        $obj = $article;
        $has_streamer = !empty($article['options']['streamerType']);
        $img_opt_size = isset($article['image']) && !empty($article['image']['size']) ? $article['image']['size'] : false;

        if( !empty($article['post']) ) {
            $obj['url'] = $this->cms->getPostURL($article['post']);
        } elseif( !empty($article['options']['overridingURL']) ) {
            $obj['url'] = $article['options']['overridingURL'];
        }

        $obj['class'] = 'teaser' . ($is_empty ? ' empty' : '');
        $obj['html_title'] = $is_empty ? '' : Arlima_Utils::getTitleHtml($obj, $this->list->getOptions());
        $obj['is_child'] = $this->is_child;

        $obj['is_child_split'] = (bool)$this->child_split_state;

        if ( !empty($article['options']) && !empty($article['options']['format']) ) {
            $obj['class'] .= ' ' . $article['options']['format'];
        }
        if( $has_streamer ) {
            $obj['class'] .= ' has-streamer';
        }
        if( $article_counter == 0 ) {
            $obj['class'] .= ' first-in-list';
        }
        if( $this->is_child ) {
            $obj['class'] .= ' teaser-child';
        }
        if( $this->child_split_state ) {
            $obj['class'] .= ' teaser-split'; // is one out of many children
            
            if ($this->child_split_state['index'] == 0) {
                $obj['class'] .= ' first';
            }
            if ($this->child_split_state['index'] == $this->child_split_state['count'] - 1) {
                $obj['class'] .= ' last';
            }
        }
        
        if( !empty($article['children']) ) {
            $obj['class'] .= ' has-children';
        }

        // Add article end content
        $obj['article_begin'] = $this->applyFilter('arlima_article_begin', $article_counter, $article, $post);

        if ( !$is_empty ) {

            $this->generateImageData($article, $article_counter, $obj, $img_opt_size, $post);

            $obj['html_content'] = $this->applyFilter('arlima_article_content', $article_counter, $article, $post, $article['content']);

            $this->generateStreamerData($has_streamer, $obj, $article);

            if ( empty($article['options']['hideRelated']) ) {
                $obj['related'] = $this->applyFilter('arlima_article_related_content', $article_counter, $article, $post);
            }
        }

        // Add article end content
        $obj['article_end'] = $this->applyFilter('arlima_article_end', $article_counter, $article, $post);

        return $this->cms->applyFilters('arlima_template_object'. (self::$filter_suffix ? '-'.self::$filter_suffix:''),
                        $obj, $article, $this->list, $template_name);
    }

    /**
     * @param $article
     * @param $article_counter
     * @param $data
     * @param $img_opt_size
     * @param $post
     */
    protected function generateImageData($article, $article_counter, &$data, $img_opt_size, $post)
    {
        $img = self::createImage($article, $article_counter, $post, $this->list, $this->child_split_state);
        $has_img_url = !empty($article['image']['url']);

        if ( $img || $has_img_url ) {

            if ( $has_img_url ) {
                // todo: wtf??
                preg_match('/src="([^"]*)"/i', $img, $arr);
                if ( !empty($arr[1]) ) {
                    $article['image']['url'] = $arr[1];
                } else {
                    $article['image']['url'] = false;
                }
            }

            if( $img ) {
                $img = $this->cms->applyFilters('arlima_article_image_tag', $img, $img_opt_size, $article, $this->list);
            }

            $data['html_image'] = $img;
            $data['class'] .= $img_opt_size ? ' img-' . $img_opt_size : ' has-img';

        } else {
            $data['class'] .= ' no-img';
        }
    }

    /**
     * Deprecated since 3.0.beta.37
     * @deprecated
     * @see createImage()
     * @return string
     */
    public static function applyImageFilters($article, $article_counter, $post, $list, $is_child_split=false)
    {
        return self::createImage($article, $article_counter, $post, $list, $is_child_split);
    }

    /**
     * @param $article
     * @param $article_counter
     * @param $post
     * @param $list
     * @param null|array $child_split_state
     * @return string
     */
    private static function createImage($article, $article_counter, $post, $list, $child_split_state=null)
    {
        $filtered = array('content'=>'');
        $img_alt = '';
        $img_class = '';
        $sys = Arlima_CMSFacade::load();
        $has_img = !empty($article['image']) && !empty($article['image']['attachment']);
        $has_giant_tmpl = !empty($article['options']['template']) && $article['options']['template'] == 'giant';

        $article_width = $child_split_state ? round(self::$width / $child_split_state['count']) : self::$width;

        if ( $has_img && !$has_giant_tmpl && $data = $sys->getImageData($article['image']['attachment']) ) {

            list($width, $height, $file) = $data;

            $dimension = self::getNewImageDimensions($article, $article_width, $width, $height);
            $img_class = $article['image']['size'] . ' ' . $article['image']['alignment'];
            $img_alt = htmlspecialchars($article['title']);

            // Let other plugin take over this function entirely
            $filtered = self::filter(
                'arlima_generate_image_version',
                $article_counter,
                $article,
                $post,
                $list,
                $img_class,
                null,
                null,
                $article_width,
                $dimension
            );

            if( !empty($filtered['content']) )
                return $filtered['content'];

            $attach_url = $sys->getImageURL($article['image']['attachment']);
            $resized_url = Arlima_CMSFacade::load()->generateImageVersion(
                                $file,
                                $attach_url,
                                $dimension,
                                $article['image']['attachment']
                            );

            $filtered = self::filter(
                'arlima_article_image',
                $article_counter,
                $article,
                $post,
                $list,
                $article['image']['size'],
                $attach_url,
                $resized_url,
                $article_width
            );

        }
        elseif( empty($article['image']['attachment']) && !empty($article['image']['externalAttachment']) ) {
            //external images, just try to fit them
            switch ($article['image']['size']) {
                case 'half':
                    $dimension = array(round($article_width * 0.5));
                    break;
                case 'third':
                    $dimension = array(round($article_width * 0.33));
                    break;
                case 'quarter':
                    $dimension = array(round($article_width * 0.25));
                    break;
                case 'fifth':
                    $dimension = array(round($article_width * 0.20));
                    break;
                case 'sixth':
                    $dimension = array(round($article_width * 0.15));
                    break;
                default:
                    $dimension = array($article_width);
                    break;
            }
            $img_class = $article['image']['size'] . ' ' . $article['image']['alignment'];
            $img_alt = htmlspecialchars($article['title']);
            $filtered['resized'] = $article['image']['url'];
        }
        elseif(!$has_giant_tmpl) {
            // Callback for empty image
            $filtered = self::filter(
                'arlima_article_image',
                $article_counter,
                $article,
                $post,
                $list,
                '',
                false,
                false,
                $article_width
            );
        }

        if( empty($filtered['content']) && !empty($filtered['resized']) && $filtered['content'] !== false) {
            $filtered['content'] = sprintf(
                '<img src="%s" %s alt="%s" class="%s" />',
                $filtered['resized'],
                !empty($dimension) ? 'width="'.$dimension[0].'"':'',
                $img_alt,
                $img_class
            );
        }

        return empty($filtered['content']) ? '':Arlima_Utils::linkWrap($article, $filtered['content']);
    }


    /**
     * @param $article
     * @param $article_width
     * @param $width
     * @param $height
     * @return array
     */
    private static function getNewImageDimensions($article, $article_width, $width, $height)
    {
        switch ($article['image']['size']) {
            case 'half':
                $width = round($article_width * 0.5);
                $size = array($width, round($height * ($width / $width)));
                break;
            case 'third':
                $width = round($article_width * 0.33);
                $size = array($width, round($height * ($width / $width)));
                break;
            case 'quarter':
                $width = round($article_width * 0.25);
                $size = array($width, round($height * ($width / $width)));
                break;
            case 'fifth':
                $width = round($article_width * 0.20);
                $size = array($width, round($height * ($width / $width)));
                break;
            case 'sixth':
                $width = round($article_width * 0.15);
                $size = array($width, round($height * ($width / $width)));
                break;
            default:
                $size = array(
                    $article_width,
                    round($height * ($article_width / $width))
                );
                break;
        }
        return $size;
    }

    /**
     * @param $has_streamer
     * @param &$data
     */
    protected function generateStreamerData($has_streamer, &$data)
    {
        $data['class'] .= ($has_streamer ? '' : ' no-streamer');
        if ( $has_streamer ) {

            $content = '';
            $style_attr = '';
            $streamer_classes = $data['options']['streamerType'];
            $streamer_content = isset($data['options']['streamerContent']) ?
                    $data['options']['streamerContent'] : '';

            switch ($data['options']['streamerType']) {
                case 'extra' :
                    $content = 'EXTRA';
                    break;
                case 'image' :
                    if ($streamer_content)
                        $content = '<img src="' . $streamer_content . '" alt="Streamer" />';
                    break;
                case 'text':
                    $content = $streamer_content;
                    if( isset($data['options']['streamerColor']) ) {
                        $style_attr = ' style="background: #'.$data['options']['streamerColor'].'"';
                        $streamer_classes .= ' color-'.$data['options']['streamerColor'];
                    }
                    break;
                default :
                    // Custom streamer
                    $content = $streamer_content;
                    break;
            }

            if ($content)
                $data['html_streamer'] = sprintf(
                    '<div class="streamer %s"%s>%s</div>',
                    $streamer_classes,
                    $style_attr,
                    $content
                );
        }
    }

    /**
     * @param string $after_title_html
     */
    public function setAfterTitleHtml($after_title_html)
    {
        $this->after_title_html = $after_title_html;
    }

    /**
     * @param string $after_title_html
     */
    public function setBeforeTitleHtml($after_title_html)
    {
        $this->before_title_html = $after_title_html;
    }

    /**
     * @param $tag
     * @param $article_counter
     * @param $article
     * @param $post
     * @param string $content
     * @return mixed
     */
    private function applyFilter($tag, $article_counter, $article, $post, $content='')
    {
        $filtered = self::filter($tag, $article_counter, $article, $post, $this->list, false, false, false, false, false, $content);
        return $filtered['content'];
    }

    /**
     * @param $filter
     * @param $article_counter
     * @param $article
     * @param $post
     * @param $list
     * @param bool $img_size
     * @param bool $source_url
     * @param bool $resized_url
     * @param bool $width
     * @param bool $dim
     * @param string $content
     * @return array
     */
    private static function filter($filter, $article_counter, &$article,
                                   $post, $list, $img_size=false, $source_url = false,
                                   $resized_url=false, $width=false, $dim=false, $content='')
    {
        $data = array(
            'article' => $article,
            'count' => $article_counter,
            'post' => $post,
            'content' => $content,
            'list' => $list,
            'filter_suffix' => self::$filter_suffix
        );
        if($img_size) {
            $data['size_name'] = $img_size;
            $data['width'] = $width;
            $data['resized'] = $resized_url;
            $data['source'] = $source_url;
        }
        if( $dim ) {
            $data['dimensions'] = $dim;
        }

        if( !empty(self::$filter_suffix) )
            $filter .= '-'.self::$filter_suffix;

        $filtered_data = Arlima_CMSFacade::load()->applyFilters($filter, $data);
        if( empty($filtered_data) ) {
            $filtered_data = array('content' => false);
        }

        return $filtered_data;
    }


}
