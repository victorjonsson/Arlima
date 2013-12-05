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
                            $resized_url=false, $width=false, $dim=false)
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
        if( $dim ) {
            $data['dimensions'] = $dim;
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
     * @param Arlima_List $list
     * @param string $img_size
     * @return string
     */
    public static function imageCallback($article, $article_counter, $post, $list, $img_size, $is_child_split=false)
    {
        $filtered = array('content'=>'');
        $img_alt = '';
        $img_class = '';
        $has_img = !empty($article['image_options']) && !empty($article['image_options']['attach_id']);
        $has_giant_tmpl = !empty($article['options']['template']) && $article['options']['template'] == 'giant';

        $article_width = $is_child_split ? round(self::$width / 2) : self::$width;

        if ( $has_img && !$has_giant_tmpl && $attach_meta = wp_get_attachment_metadata($article['image_options']['attach_id']) ) {

            $size = self::getNewImageDimensions($article, $article_width, $attach_meta, $article['image_options']['attach_id']);

            if( !$size ) {
                // For some reason unable to calculate new image dimensions, more info in error log
                return '';
            }

            $img_class = $article['image_options']['size'] . ' ' . $article['image_options']['alignment'];
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
                            $size
                        );

            if( !empty($filtered['content']) )
                return $filtered['content'];

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
        elseif( empty($article['image_options']['attach_id']) && !empty($article['image_options']['external_attach_id']) ) {
            //external images, just try to fit them
            switch ($article['image_options']['size']) {
                case 'half':
                    $size = array(round($article_width * 0.5));
                    break;
                case 'third':
                    $size = array(round($article_width * 0.33));
                    break;
                case 'quarter':
                    $size = array(round($article_width * 0.25));
                    break;
                case 'fifth':
                    $size = array(round($article_width * 0.20));
                    break;
                case 'sixth':
                    $size = array(round($article_width * 0.15));
                    break;
                default:
                    $size = array($article_width);
                    break;
            }
            $img_class = $article['image_options']['size'] . ' ' . $article['image_options']['alignment'];
            $img_alt = htmlspecialchars($article['title']);
            $filtered['resized'] = $article['image_options']['url'];
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

        return Arlima_List::linkWrap($article, $filtered['content']);
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
        if( !Arlima_Plugin::supportsImageEditor() ) {
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
            $version_manager = new Arlima_ImageVersionManager($attach_id, new Arlima_Plugin());
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
            $url = $article['post_id'] ? admin_url('post.php?action=edit&amp;post=' . $post->ID) : $article['url'];
            $filtered['content'] = '<div class="arlima future-post"><p>
                        Hey dude, <a href="' . $url . '" target="_blank">&quot;'.$article['title'].'&quot;</a> is
                        connected to a post that isn\'t published yet. The article will become public in '.
                        human_time_diff(time(), $article['publish_date']).'.</p>
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
            $target = empty($article['options']['target']) ? false:$article['options']['target'];
            $filtered['content'] = arlima_link_entrywords(trim($article['text']), $article['url'], $target);
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

    /**
     * @param $article
     * @param $article_width
     * @param $attach_meta
     * @param int $attach_id
     * @return array
     */
    private static function getNewImageDimensions($article, $article_width, $attach_meta, $attach_id)
    {
        if( empty($attach_meta['height']) || empty($attach_meta['width']) ) {
            error_log('PHP Warning: Have to regenerate height and width for '.$attach_meta['file']);
            list($width, $height) = getimagesize($attach_meta['file']);
            $attach_meta['height'] = $height;
            $attach_meta['width'] = $width;
            wp_update_attachment_metadata($attach_id, $attach_meta);
            return false;
        }

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
            case 'fifth':
                $width = round($article_width * 0.20);
                $size = array($width, round($attach_meta['height'] * ($width / $attach_meta['width'])));
                break;
            case 'sixth':
                $width = round($article_width * 0.15);
                $size = array($width, round($attach_meta['height'] * ($width / $attach_meta['width'])));
                break;
            default:
                $size = array(
                    $article_width,
                    round($attach_meta['height'] * ($article_width / $attach_meta['width']))
                );
                break;
        }
        return $size;
    }
}
