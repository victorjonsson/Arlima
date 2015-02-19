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
     * As of 3.1 the article is an object that has this method implemented
     *
     * @deprecated Use Arlima_Article::getURL instead ($article->getURL())
     *
     * @param Arlima_Article $article
     * @return null|string
     */
    public static function resolveURL($article)
    {
        self::warnAboutDeprecation(__METHOD__, 'Arlima_Article::getURL');
        return $article->getURL();
    }

    /**
     * @param Arlima_Article $article
     * @param array $options
     * @param array $header_classes
     * @return string
     */
    public static function getTitleHtml($article, $options, $header_classes=array())
    {
        $underscore_replace = !isset($options['convertBreaks']) || $options['convertBreaks'] ? '<br />':'';
        $title = $article->getTitle($underscore_replace);
        if ( empty($title) ) {
            return '';
        }

        if ( $pre_txt = $article->opt('preTitle') ) {
            $title = '<span class="arlima-pre-title">' . $pre_txt . '</span> ' . $title;
        }

        $title_html = '';
        $header_classes[] = 'fsize-' . $article->getSize();

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

        $title_html .= self::linkWrap($article, $title);

        return $start_tag . $title_html . $end_tag;
    }


    /**
     * Wrap given content with an a-element linking to the URL of the article
     * @param Arlima_Article $article
     * @param string $content
     * @param array $classes
     * @return string
     */
    public static function linkWrap($article, $content, $classes = array())
    {
        if( $url = $article->getURL() ) {
            $target = '';
            if( $link_target = $article->opt('target') ) {
                $target = ' target="'.$link_target.'"';
            }
            return sprintf(
                '<a href="%s"%s%s>%s</a>',
                $url,
                $target,
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
        $parts = explode('.', $num);
        $float = array_shift($parts) . '.';
        foreach( $parts as $p ) {
            if( is_numeric($p) ) {
                $float .= $p;
            } else {
                $float .= '0';
            }
        }
        return (float)$float;
    }
}