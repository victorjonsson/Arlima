<?php


/**
 * Applies wordpress filters on callback functions invoked
 * when rendering a Arlima list
 *
 * @package Arlima
 * @since 2.5
 */
class Arlima_FilterApplier
{
    /**
     * @var string
     */
    private static $filter_suffix='';

    /**
     * @var int
     */
    private static $width = 468;

    /**
     * @var null|bool
     */
    private static $is_wp_support_img_editor = null;

    /**
     * @return bool
     */
    protected static function supportsImageEditor()
    {
        if( self::$is_wp_support_img_editor === null ) {
            global $wp_version;
            self::$is_wp_support_img_editor = version_compare( $wp_version, '3.5', '>=' );
        }
        return self::$is_wp_support_img_editor;
    }

    /**
     * @param string $s
     */
    public static function setFilterSuffix($s)
    {
        self::$filter_suffix = $s;
    }

    /**
     * @param Arlima_AbstractListRenderingManager $renderer
     */
    public static function applyFilters(&$renderer)
    {
        $renderer->setFuturePostCallback('Arlima_FilterApplier::futurePostCallback');

        $renderer->setArticleBeginCallback('Arlima_FilterApplier::articleBeginCallback');

        $renderer->setImageCallback('Arlima_FilterApplier::imageCallback');
        $renderer->setContentCallback('Arlima_FilterApplier::contentCallback');
        $renderer->setRelatedPostsCallback('Arlima_FilterApplier::relatedPostsCallback');

        $renderer->setArticleEndCallback('Arlima_FilterApplier::articleEndCallback');
    }

    /**
     * @param $w
     */
    public static function setArticleWidth($w)
    {
        self::$width = (int)$w;
    }

    /**
     * @return int
     */
    public static function getArticleWidth()
    {
        return self::$width;
    }

    /**
     * @param $article_counter
     * @param $article
     * @param $post
     * @param $list
     * @return string
     */
    public static function articleBeginCallback($article_counter, $article, $post, $list)
    {
        $filtered = self::filter('arlima_article_begin', $article_counter, $article, $post, $list);
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
     * @internal param bool $img_url
     * @return array
     */
    private static function filter($filter, $article_counter, &$article,
                            $post, $list, $img_size=false, $source_url = false,
                            $resized_url=false, $width=false)
    {
        $data = array(
            'article' => $article,
            'count' => $article_counter,
            'post' => $post,
            'content' => '',
            'list' => $list,
            'filter_suffix' => self::$filter_suffix
        );
        if($img_size) {
            $data['size_name'] = $img_size;
            $data['width'] = $width;
            $data['resized'] = $resized_url;
            $data['source'] = $source_url;
        }

        if( !empty(self::$filter_suffix) )
            $filter .= '-'.self::$filter_suffix;

        $filtered_data = apply_filters($filter, $data);
        if( empty($filtered_data) ) {
            $filtered_data = array('content' => false);
        }

        return $filtered_data;
    }

    /**
     * @param $article_counter
     * @param $article
     * @param $post
     * @param $list
     * @return mixed
     */
    public static function articleEndCallback($article_counter, $article, $post, $list)
    {
        $filtered = self::filter('arlima_article_end', $article_counter, $article, $post, $list);
        return $filtered['content'];
    }

    /**
     * @param $article_counter
     * @param $article
     * @param $post
     * @param $list
     * @param $img_size
     * @return string
     */
    public static function imageCallback($article, $article_counter, $post, $list, $img_size)
    {
        $filtered = array('content'=>'');
        $img_alt = '';
        $img_class = '';
        $has_img = !empty($article['image_options']) && !empty($article['image_options']['attach_id']);
        $has_giant_tmpl = !empty($article['options']['template']) && $article['options']['template'] == 'giant';
        $is_child_article = !empty($article['parent']) && $article['parent'] != -1;
        $article_width = $is_child_article ? round(self::$width / 2) : self::$width;

        if ( $has_img && !$has_giant_tmpl && $attach_meta = wp_get_attachment_metadata($article['image_options']['attach_id']) ) {

            switch ($article['image_options']['size']) {
                case 'half':
                    $width = round($article_width * 0.5);
                    $size = array($width, round($attach_meta['height'] * ($width / $attach_meta['width'])));
                    break;
                case 'third':
                    $width = round($article_width * 0.33);
                    $size = array($width, round($attach_meta['height'] * ($width / $attach_meta['width'])));
                    break;
                case 'quarter':
                    $width = round($article_width * 0.25);
                    $size = array($width, round($attach_meta['height'] * ($width / $attach_meta['width'])));
                    break;
                default:
                    $size = array(
                        $article_width,
                        round($attach_meta['height'] * ($article_width / $attach_meta['width']))
                    );
                    break;
            }

            $img_class = $article['image_options']['size'] . ' ' . $article['image_options']['alignment'];
            $img_alt = htmlspecialchars($article['title']);
            $attach_url = wp_get_attachment_url($article['image_options']['attach_id']);
            $resized_url = self::generateImageVersion(
                                $attach_meta['file'],
                                $attach_url,
                                $size,
                                $article['image_options']['attach_id']
                            );

            $filtered = self::filter(
                            'arlima_article_image',
                            $article_counter,
                            $article,
                            $post,
                            $list,
                            $img_size,
                            $attach_url,
                            $resized_url,
                            $article_width
                        );
        }
        elseif(!$has_giant_tmpl) {
            // Callback for empty image
            $filtered = self::filter(
                'arlima_article_image',
                $article_counter,
                $article,
                $post,
                $list,
                $img_size,
                false,
                false,
                $article_width
            );
        }

        if( empty($filtered['content']) && !empty($filtered['resized']) && $filtered['content'] !== false) {
            $filtered['content'] = sprintf(
                '<img src="%s" %s alt="%s" class="%s" />',
                $filtered['resized'],
                !empty($size) ? 'width="'.$size[0].'"':'',
                $img_alt,
                $img_class
            );
        }

        return $filtered['content'];
    }

    /**
     * @param string $file
     * @param string $attach_url
     * @param array $size
     * @param int $attach_id
     * @return string
     */
    private static function generateImageVersion($file, $attach_url, $size, $attach_id)
    {
        if( !self::supportsImageEditor() ) {
            $resized_img = image_resize(
                WP_CONTENT_DIR . '/uploads/' . $file,
                $size[0],
                null,
                false,
                null,
                null,
                98
            );

            if ( !is_wp_error($resized_img) ) {
                $img_url = dirname($attach_url) . '/' . basename($resized_img);
            } else {
                $img_url = $attach_url;
            }
        } else {

            $version_manager = new Arlima_ImageVersionManager($attach_id);
            $img_url = $version_manager->getVersionURL($size[0]);
            if( $img_url === false )
                $img_url = $attach_url;
        }

        return $img_url;
    }

    /**
     * @param $article_counter
     * @param $article
     * @param $post
     * @param $list
     * @return string
     */
    public static function futurePostCallback($post, $article, $list, $article_counter)
    {
        $filtered = self::filter('arlima_future_post', $article_counter, $article, $post, $list);

        if( empty($filtered['content']) && $filtered['content'] !== false) {
            $filtered['content'] = '<div class="arlima future-post">
                        Hey dude, <a href="' . admin_url('post.php?action=edit&amp;post=' . $post->ID) . '" target="_blank">this post</a>
                        will not show up in the list until it\'s published, unless you\'re not previewing the list that is...
                    </div>';
        }

        return $filtered['content'];
    }

    /**
     * @param $article
     * @param $deprecated
     * @param $post
     * @param $article_counter
     * @param $list
     * @return string
     */
    public static function contentCallback($article, $deprecated, $post, $article_counter, $list)
    {
        $filtered = self::filter('arlima_article_content', $article_counter, $article, $post, $list);

        if( empty($filtered['content']) && $filtered['content'] !== false ) {
            $filtered['content'] = arlima_link_entrywords(trim($article['text']), $article['url']);
        }

        return $filtered['content'];
    }

    /**
     * @param $article_counter
     * @param $article
     * @param $post
     * @param $list
     * @return array|bool|string
     */
    public static function relatedPostsCallback($article_counter, $article, $post, $list)
    {
        $filtered = self::filter('arlima_article_related_content', $article_counter, $article, $post, $list);

        if ( empty($filtered['content']) && $filtered['content'] !== false && !empty($post) ) {
            $filtered['content'] = arlima_related_posts();
        }

        return $filtered['content'];
    }
}
