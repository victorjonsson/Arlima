<?php


/**
 * Class that is responsible of exporting Arlima lists related to wordpress pages.
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_ExportManager
{

    const FORMAT_RSS = 'rss';
    const FORMAT_JSON = 'json';
    const DEFAULT_FORMAT = self::FORMAT_JSON;

    const ERROR_UNSUPPORTED_FORMAT = 0;
    const ERROR_PAGE_NOT_FOUND = 1;
    const ERROR_MISSING_LIST_REFERENCE = 2;
    const ERROR_LIST_DOES_NOT_EXIST = 3;
    const ERROR_LIST_BLOCKED_FROM_EXPORT = 4;

    /**
     * @var array
     */
    private $error_messages = array(
        self::ERROR_UNSUPPORTED_FORMAT => 'Requesting unsupported format, only JSON or RSS is allowed',
        self::ERROR_PAGE_NOT_FOUND => 'Page not found',
        self::ERROR_MISSING_LIST_REFERENCE => 'This page has no related article list',
        self::ERROR_LIST_DOES_NOT_EXIST => 'This page is related to an article list that does not exist',
        self::ERROR_LIST_BLOCKED_FROM_EXPORT => 'This list is not available for export'
    );

    /**
     * @var array
     */
    private $available_export;

    /**
     * @var Arlima_Plugin
     */
    private $arlima_plugin;

    /**
     * @param Arlima_Plugin $arlima_plugin
     */
    public function __construct($arlima_plugin)
    {
        $settings = $arlima_plugin->loadSettings();
        $this->available_export = !empty($settings['available_export']) ? $settings['available_export'] : array();
        $this->arlima_plugin = $arlima_plugin;
    }

    /**
     * This function provides a small read-only RESTful API. It sends out appropriate response headers
     * depending on the request being made. It support two response content types, RSS and JSON
     *
     * @param string $page_slug
     * @param string $format
     * @throws Exception
     */
    public function export($page_slug, $format)
    {
        $this->sendInitialHeaders($format);

        if ( !in_array($format, array(self::FORMAT_JSON, self::FORMAT_RSS)) ) {
            $this->sendErrorToClient(self::ERROR_UNSUPPORTED_FORMAT, '400 Bad Request', self::DEFAULT_FORMAT);
        }

        if( $page_slug ) {
            $page = $this->getPageBySlug($page_slug);
        } else {
            $page = get_post(get_option('page_on_front', 0));
        }

        if ( !$page ) {
            $this->sendErrorToClient(self::ERROR_PAGE_NOT_FOUND, '404 Page Not Found', $format);
        } else {

            $factory = new Arlima_ListFactory();
            $connector = new Arlima_ListConnector();
            $relation = $connector->getRelationData($page->ID);

            if ( empty($relation) ) {
                // This logic is here only for backward compatibility
                // todo: Remove when moving to version 3.0
                $arlima_slug = get_post_meta($page->ID, 'arlima', true);
                if( empty($arlima_slug) ) {
                    $this->sendErrorToClient(self::ERROR_MISSING_LIST_REFERENCE, '404 Bad Request', $format);
                    die;
                }
                else {
                    $list = $factory->loadList($arlima_slug);
                }
            } else {
                $list = $factory->loadList($relation['id'], false, true);
            }

            if ( !$list->exists() ) {
                $this->sendErrorToClient(self::ERROR_LIST_DOES_NOT_EXIST, '404 Content not found', $format);
            } elseif ( !$this->isAvailableForExport($list) ) {
                $this->sendErrorToClient(self::ERROR_LIST_BLOCKED_FROM_EXPORT, '403 Forbidden', $format);
            } else {
                echo $this->convertList($list, $format);
            }
        }

        die(0);
    }

    /**
     * @param $slug
     * @return bool|mixed
     */
    private function getPageBySlug( $slug ) {
        $page_id = wp_cache_get('arlima_slug_2_page_'.$slug, 'arlima');
        if( !$page_id ) {
            /* @var wpdb $wpdb */
            global $wpdb;
            $page = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_type FROM $wpdb->posts WHERE post_name = %s ", $slug ) );
            if ( !empty($page) && in_array($page[0]->post_type, array('page', 'post')) ) {
                $page_id = $page[0]->ID;
                wp_cache_set('arlima_slug_2_page_'.$slug, $page_id, 'arlima', 60);
            }
        }

        return $page_id ? get_post($page_id):false;
    }

    /**
     * @param $format
     */
    private function sendInitialHeaders($format)
    {
        if( function_exists('header_remove') ) {
            header_remove();
        }
        switch ($format) {
            case self::FORMAT_RSS:
                header('Content-Type: text/xml; charset=utf8');
                break;
            default:
                header('Content-Type: application/json');
                break;
        }
    }

    /**
     * @param int $error_code
     * @param string $http_status
     * @param string $format
     */
    private function sendErrorToClient($error_code, $http_status, $format)
    {
        header('HTTP/1.1 ' . $http_status);
        $message = isset($this->error_messages[$error_code]) ? $this->error_messages[$error_code] : 'Unknown error';
        switch ($format) {
            case self::FORMAT_RSS:
                echo '<error><message>' . $message . '</message><code>' . $error_code . '</code></error>';
                break;
            default:
                echo '{"error":"' . $message . '","code":' . $error_code . '}';
                break;
        }

        die;
    }

    /**
     * @param Arlima_List $list
     * @param string $format Either 'json' or 'rss'
     * @return string
     */
    public function convertList($list, $format)
    {

        // Modify data exported
        $base_url = rtrim(home_url(), '/') . '/';
        $articles = $list->getArticles();
        foreach (array_keys($articles) as $key) {
            $this->prepareArticleForExport($articles[$key], $base_url);
        }
        $list->setArticles($articles);

        // RSS export
        if ( $format == self::FORMAT_RSS ) {
            $base_url = get_bloginfo('url');
            $rss = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/"><channel>
                <title><![CDATA[' . $list->getTitle() . ' (' . $list->getSlug() . ')]]></title>
                <description>RSS export feed for Arlima article list</description>
                <link>' . $base_url . '</link>
                <lastBuildDate>' . date('r') . '</lastBuildDate>
                <pubDate>' . date('r', $list->lastModified()) . '</pubDate>
                <ttl>1</ttl>
                <generator>Arlima v' . Arlima_Plugin::VERSION . ' (wordpress plugin)</generator>
                ';

            $list_last_mod_time = $list->lastModified();
            foreach ($articles as $article) {
                $rss .= $this->articleToRSSItem($article, $list_last_mod_time);
                if ( !empty($article['children']) ) {
                    foreach ($article['children'] as $child_article) {
                        $rss .= $this->articleToRSSItem($child_article, $list_last_mod_time);
                    }
                }
            }

            return $rss . '</channel></rss>';
        } // JSON export
        else {
            return json_encode($list->toArray());
        }
    }

    /**
     * - Move all ID numbers from key "xxx_id" to "external_xxx_id"
     * - Make sure all URL:s starts with a base url
     * - move permalink/url to external url
     *
     * @param array &$article_data
     * @param string $base_url
     * @return array
     */
    private function prepareArticleForExport(&$article_data, $base_url)
    {
        $article_data['external_url'] = Arlima_List::resolveURL($article_data);

        if ( strpos($article_data['external_url'], 'http') === false ) {
            $article_data['external_url'] = $base_url . ltrim($article_data['external_url'], '/');
        }

        // Add url for backward compatibility @todo: remove when moving up to version 3.0
        $article_data['url'] = $article_data['external_url'];

        $article_data['external_post_id'] = 0;
        if ( !empty($article_data['post_id']) ) {
            $article_data['external_post_id'] = $article_data['post_id'];
            $article_data['post_id'] = 0;
        }

        if ( !empty($article_data['image_options']) ) {
            $article_data['image_options']['external_attach_id'] = isset($article_data['image_options']['attach_id']) ? $article_data['image_options']['attach_id'] : 0;
            $article_data['image_options']['attach_id'] = 0;
        }

        if ( !empty($article_data['children']) ) {
            foreach (array_keys($article_data['children']) as $key) {
                $this->prepareArticleForExport($article_data['children'][$key], $base_url);
            }
        }

        $article_data = apply_filters('arlima_prepare_article_for_export', $article_data);
    }

    /**
     * @param array $article
     * @param int $last_mod
     * @return string
     */
    private function articleToRSSItem(array $article, $last_mod)
    {
        $img = '';
        if ( isset($article['image_options']) && !empty($article['image_options']['url']) ) {

            $node_type = defined('ARLIMA_RSS_IMG_TAG') ? ARLIMA_RSS_IMG_TAG : 'enclosure';
            $img_url = apply_filters('arlima_rss_image', $article['image_options']['url'], $article['image_options']);
            $img_type = pathinfo($img_url, PATHINFO_EXTENSION);
            $img_type = 'image/'. current(explode('?', $img_type));

            if( $node_type == 'media:content' ) {
                $img = '<media:content url="'.$img_url.'" type="'.$img_type.'" />';
            } elseif( $node_type == 'image' ) {
                $img = '<image>' . $img_url . '</image>';
            } else {
                $img = '<enclosure url="' . $img_url . '" length="1" type="'.$img_type.'"  />';
            }
        }

        $guid = $article['url'];
        $post_id = intval($article['external_post_id']);
        if ( $post_id ) {
            $guid = '/?p=' . $post_id;
            $date = date('r', strtotime(get_post($post_id)->post_date));
        } else {
            $date = date('r', $last_mod);
        }

        return '<item>
                    <title><![CDATA[' . str_replace('__', '', $article['title']) . ']]></title>
                    <description><![CDATA[' . strip_tags($article['text']) . ']]></description>
                    <link>' . $article['external_url'] . '</link>
                    <guid isPermaLink="false">' . $guid . '</guid>
                    <pubDate>' . $date . '</pubDate>
                    ' . $img . '
                </item>';
    }

    /**
     * @return array
     */
    public function getListsAvailableForExport()
    {
        return $this->available_export;
    }

    /**
     * @param array $lists
     */
    public function setListsAvailableForExport(array $lists)
    {
        $this->available_export = $lists;
        $settings = $this->arlima_plugin->loadSettings();
        $settings['available_export'] = $lists;
        $this->arlima_plugin->saveSettings($settings);
    }

    /**
     * @param Arlima_List|int $list
     * @return bool
     */
    public function isAvailableForExport($list)
    {
        $id = is_object($list) ? $list->id() : $list;
        return in_array($id, $this->available_export);
    }
}