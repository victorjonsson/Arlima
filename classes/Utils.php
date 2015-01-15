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
            return Arlima_CMSFacade::load()->getPostURL($article['post']);
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
     * Get unix timestamp
     * @return int
     */
    public static function timeStamp()
    {
        return time() + ARLIMA_TIME_ADJUST;
    }

    /**
     * Shortens any text to number of words.
     * @param $text
     * @param int $num_words
     * @return string
     */
    public static function shorten($text, $num_words = 24, $allowed_tags = '') {
        $text = Arlima_CMSFacade::load()->sanitizeText($text, $allowed_tags);
        $words = explode(' ', $text, $num_words + 1);
            if(count($words) > $num_words) :
                array_pop($words);
                array_push($words, '…');
                $text = implode(' ', $words);
            endif;
        return $text;
    }

    /**
     * @param string $func
     * @param string $new
     */
    public static function warnAboutDeprecation($func, $new)
    {
        if( ARLIMA_DEV_MODE && !defined('ARLIMA_UNIT_TEST') ) {
            trigger_error('Use of deprecated function '.$func.' use '.$new.' instead', E_USER_NOTICE);
        }
    }

    /**
     * @param string $num
     * @return float
     */
    public static function versionNumberToFloat($num)
    {
        $float = str_replace('..', '.', preg_replace('/[^0-9\.]/', '', $num));
        $pos = strpos($float, '.');
        $float = substr($float, 0, $pos+1) . str_replace('.', '', substr($float, $pos));
        return (float)$float;
    }
}