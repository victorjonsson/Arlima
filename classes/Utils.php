<?php


/**
 * General functions
 *
 * @package Arlima
 * @since 3.0
 */
class Arlima_Utils
{

    /**
     * Uses the overriding url if it exists, otherwise the permalink of the post that
     * the article is connected to
     * @param $article
     * @return null|string
     */
    public static function resolveURL($article)
    {
        if( !empty($article['options']) && !empty($article['options']['overridingURL']) ) {
            return $article['options']['overridingURL'];
        } elseif( !empty($article['post']) ) {
            return get_permalink($article['post']);
        }
        return '';
    }


    /**
     * @param array $article
     * @param array $options
     * @param array $header_classes
     * @return string
     */
    public static function getTitleHtml($article, $options, $header_classes=array())
    {

        if ( $article['title'] == '' ) {
            return '';
        }

        $underscore_replace = !isset($options['convertBreaks']) || $options['convertBreaks'] ? '<br />':'';
        $title = str_replace('__', $underscore_replace, $article['title']);

        if ( !empty($article['options']['preTitle']) ) {
            $title = '<span class="arlima-pre-title">' . $article['options']['preTitle'] . '</span> ' . $title;
        }

        $title_html = '';
        $header_classes[] = 'fsize-' . $article['size'];

        $start_tag = empty($options['before_title']) ? '<h2>' : $options['before_title'];
        $end_tag = empty($options['after_title']) ? '</h2>' : $options['after_title'];

        if ( !empty($header_classes) ) {
            if ( stristr($start_tag, 'class') !== false ) {
                $start_tag = str_replace(
                    'class="',
                    'class="' . implode(' ', $header_classes) . ' ',
                    $start_tag
                );
            } else {
                $start_tag = str_replace(
                    '>',
                    ' class="' . implode(' ', $header_classes) . '">',
                    $start_tag
                );
            }
        }

        if ( !empty($article['url']) ) {
            $title_html .= self::linkWrap($article, $title);
        } else {
            $title_html .= $title;
        }

        return $start_tag . $title_html . $end_tag;
    }


    /**
     * Wrap given content with an a-element linking to the URL of the article
     * @param array $article
     * @param string $content
     * @param array $classes
     * @return string
     */
    public static function linkWrap($article, $content, $classes = array())
    {
        if( !empty($article['url']) ) {
            $opts = $article['options'];
            return sprintf(
                '<a href="%s"%s%s>%s</a>',
                $article['url'],
                empty($opts['target']) ? '':' target="'.$opts['target'].'"',
                empty($classes) ? '' : ' class="'.implode(' ', $classes).'"',
                $content
            );
        }
        return $content;
    }

    /**
     * @param WP_Post $p
     * @return int
     */
    public static function getPostTimeStamp($p)
    {
        static $date_prop = null;
        if( $date_prop === null ) {
            // wtf?? ask wp why...
            global $wp_version;
            if( (float)$wp_version < 3.9 ) {
                $date_prop = 'post_date';
            } else {
                $date_prop = 'post_date_gmt';
            }
        }
        return strtotime( $p->$date_prop );
    }

    /**
     * If we get a lot of calls to this function we might as well always make a call
     * to load_plugin_textdomain on init, and not only in wp-admin
     *
     * @static
     * @return bool
     */
    public static function loadTextDomain()
    {
        if ( !self::$has_loaded_textdomain ) {
            self::$has_loaded_textdomain = true;
            load_plugin_textdomain('arlima', false, basename(ARLIMA_PLUGIN_PATH).'/lang/');
        }
    }

    /**
     * @var bool
     */
    private static $has_loaded_textdomain = false;

    /**
     * Get unix timestamp
     * @return int
     */
    public static function timeStamp()
    {
        return time() + ARLIMA_TIME_ADJUST;
    }

    /**
     * Returns the excerpt for a post based on post_excerpt or post_content if no post_excerpt is available.
     * @param $post_id
     * @return string
     */
    public static function getExcerptByPostId($post_id, $excerpt_length = 35, $allowed_tags = '') {
        if(!$post_id) {
            return false;
        }
        $the_post = get_post($post_id);

        $the_excerpt = $the_post->post_excerpt;

        if(strlen(trim($the_excerpt)) == 0) {
            // If no excerpt, generate an excerpt from content
            $the_excerpt = $the_post->post_content;
            $the_excerpt = self::shorten($the_excerpt, $excerpt_length, $allowed_tags);
        }
        return $the_excerpt;

    }

    /**
     * Shortens any text to number of words.
     * @param $text
     * @param int $num_words
     * @return string
     */
    public static function shorten($text, $num_words = 24, $allowed_tags = '') {
        $text = strip_tags(strip_shortcodes($text), $allowed_tags);
        $words = explode(' ', $text, $num_words + 1);
            if(count($words) > $num_words) :
                array_pop($words);
                array_push($words, '…');
                $text = implode(' ', $words);
            endif;
        return $text;
    }
}