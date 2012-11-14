<?php


/**
 */
class Arlima_EventBinder {

    private static $width = 468;

    /**
     * @param Arlima_AbstractListRenderingManager $renderer
     */
    public static function bindFilters(&$renderer) {
        $renderer->setBeforeArticleCallback('Arlima_EventBinder::beforeCallback');
        $renderer->setGetImageCallback('Arlima_EventBinder::imageCallback');
        $renderer->setTextModifierCallback('Arlima_EventBinder::textCallback');
        $renderer->setTextModifierCallback('Arlima_EventBinder::relatedPostCallback');
        $renderer->setTextModifierCallback('Arlima_EventBinder::futurePostCallback');
        $renderer->setArticleEndCallback('Arlima_EventBinder::afterCallback');
    }

    public static function setArticleWidth($w) {
        self::$width = (int)$w;
    }

    public static function getArticleWidth() {
        return self::$width;
    }

    public static function beforeCallback($article_counter, &$article, $is_post, $post) {
        $filtered = apply_filters('arlima_article_setup', array('count' =>$article_counter, 'article' => $article, 'post' => $post));
        $article = $filtered['article'];
    }

    public static function afterCallback($article_counter, $article_data) {
        $filtered = apply_filters('arlima_article_end', array('count' => $article_counter, 'article' => $article_data, 'end_content' => ''));
        return $filtered['end_content'];
    }

    public static function imageCallback($article, $img_size, $article_counter) {

        $filtered = apply_filters('arlima_article_image', array('count'=>$article_counter, 'article' => $article, 'image' => '', 'width'=>self::$width));

        if( empty($filtered['image']) ) {
            $has_img = !empty($article['image_options']) && !empty( $article['image_options']['attach_id'] );
            $has_giant_tmpl = !empty($article['options']['template']) && $article['options']['template'] == 'giant';

            if( $has_img && !$has_giant_tmpl ) {

                $attach_meta = wp_get_attachment_metadata($article['image_options']['attach_id']);
                if( !$attach_meta )
                    return false;

                $article_width = empty($article['parent']) || $article['parent'] == -1 ? self::$width : round(self::$width * 0.5);

                switch($article['image_options']['size']) {
                    case 'half':
                        $width = round($article_width * 0.5);
                        $size = array($width, round( $attach_meta['height'] * ($width / $attach_meta['width'])));
                        break;
                    case 'third':
                        $width = round($article_width * 0.33);
                        $size = array($width, round( $attach_meta['height'] * ($width / $attach_meta['width'])));
                        break;
                    case 'quarter':
                        $width = round($article_width * 0.25);
                        $size = array($width, round( $attach_meta['height'] * ($width / $attach_meta['width'])));
                        break;
                    default:
                        $size = array($article_width, round( $attach_meta['height'] * ($article_width / $attach_meta['width'])));
                        break;
                }

                $img_class = $article['image_options']['size'].' '.$article['image_options']['alignment'];
                $img_alt = htmlspecialchars( $article['title'] );
                $attach_url = wp_get_attachment_url( $article['image_options']['attach_id'] );
                $resized_img = image_resize( WP_CONTENT_DIR .'/uploads/'. $attach_meta['file'], $size[0], null, false, null, null, 98);
                if( !is_wp_error($resized_img) ) {
                    $img_url = dirname($attach_url) . '/' . basename($resized_img);
                }
                else {
                    $img_url = $attach_url;
                }

                $filtered['image'] = sprintf('<img src="%s" width="%s" alt="%s" class="%s" />', $img_url, $size[0], $img_alt, $img_class);
            }
        }

        return $filtered['image'];
    }

    /**
     * @param $post
     * @param $article
     * @param $list
     * @return mixed|void
     */
    public static function futurePostCallback($post, $article, $list) {
        $message = '<div class="arlima future-post">
                        Hey dude, <a href="'.admin_url('post.php?action=edit&amp;post='.$post->ID) .'" target="_blank">this post</a>
                        will not show up in the list until it\'s published, unless you\'re not previewing the list that is...
                    </div>';

        $filtered = apply_filters('arlima_future_post', array('article' => $article, 'post'=>$post, 'message'=>$message, 'list'=>$list) );
        return $filtered['message'];
    }

    /**
     * @param $article
     * @param $is_post
     * @param $post
     * @return string
     */
    public static function textCallback($article, $is_post, $post) {
        $data = array(
            'article' => $article,
            'is_post' => $is_post,
            'post' => $post,
            'text' =>  arlima_link_entrywords(trim($article['text']), $article['url'])
        );

        $filtered = apply_filters('arlima_article_text', $data);
        return $filtered['text'];
    }

    /**
     * @param $article
     * @param $is_post
     * @return array|bool|string
     */
    public static function relatedPostsCallback($article, $is_post) {
        return $is_post ? arlima_related_posts('inline', null, false) : '';
    }
}
