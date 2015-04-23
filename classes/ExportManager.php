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
     * @var Arlima_CMSInterface
     */
    private $cms;


    /**
     * @param array $avail_for_export
     */
    public function __construct($avail_for_export=array())
    {
        $this->cms = Arlima_CMSFacade::load();
        $this->available_export = $avail_for_export;
    }

    /**
     * This function will output the list related to given page in a JSON or RSS format. It sends out appropriate
     * response headers depending on the request being made.
     *
     * @param string $page_slug
     * @param string $format
     * @throws Exception
     */
    public function export($page_slug, $format)
    {
        $format = strtolower($format);
        $this->sendInitialHeaders($format);

        if ( !in_array($format, array(self::FORMAT_JSON, self::FORMAT_RSS)) ) {
            $this->sendErrorToClient(self::ERROR_UNSUPPORTED_FORMAT, '400 Bad Request', self::DEFAULT_FORMAT);
        }

        if( $page_slug ) {
            $page_id = $this->getPageIdBySlug($page_slug);
        } else {
            $page_id = get_option('page_on_front', 0);
        }

        if ( empty($page_id) ) {
            $this->sendErrorToClient(self::ERROR_PAGE_NOT_FOUND, '404 Page Not Found', $format);
        } else {
            $this->outputListConnectedToPage($format, $page_id);
        }

        die(0);
    }

    /**
     * @param $slug
     * @return bool|mixed
     */
    private function getPageIdBySlug( $slug )
    {
        $cache = Arlima_CacheManager::loadInstance();
        $page_id = $cache->get('arlima_slug_2_page_'.$slug, 'arlima');
        if( !$page_id ) {
            if ( $page_id = $this->cms->getPageIdBySlug($slug) ) {
                $cache->set('arlima_slug_2_page_'.$slug, $page_id, 60);
            }
        }

        return $page_id;
    }

    /**
     * @param $format
     */
    private function sendInitialHeaders($format)
    {
        if( function_exists('header_remove') ) {
            header_remove();
        }

        header('X-Arlima-Version: '.ARLIMA_PLUGIN_VERSION);

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
        $base_url = rtrim($this->cms->getBaseURL(), '/') . '/';
        if ( $format == self::FORMAT_RSS ) {
            return $this->getListAsRSS($list, $base_url);
        } else {
            return $this->getListAsJSON($list, $base_url);
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
        if( isset($article_data['url']) ) {
            $article_data['externalURL'] = $article_data['url'];
            unset($article_data['url']);

            if ( strpos($article_data['externalURL'], 'http') === false ) {
                $article_data['externalURL'] = $base_url . ltrim($article_data['externalURL'], '/');
            }

        } elseif( isset($article_data['options']) && isset($article_data['options']['overridingURL'])) {
            $article_data['externalURL'] = $article_data['options']['overridingURL'];
        } else {
            $article_data['externalURL'] = $base_url.'?';
        }

        $article_data['externalPost'] = 0;
        if ( isset($article_data['post']) ) {
            $article_data['externalPost'] = $article_data['post'];
            unset($article_data['post']);
        }

        if ( !empty($article_data['image']) ) {
            if( isset($article_data['image']['attachment']) ) {
                $article_data['image']['externalAttachment'] = $article_data['image']['attachment'];
                unset($article_data['image']['attachment']);
            }
        }

        if ( !empty($article_data['children']) ) {
            foreach ($article_data['children'] as $key => $child_article) {
                $this->prepareArticleForExport($article_data['children'][$key], $base_url);
            }
        }

        $article_data = $this->cms->applyFilters('arlima_prepare_article_for_export', $article_data);
    }

    /**
     * @param Arlima_Article $article
     * @param int $last_mod
     * @param Arlima_List $list
     * @return string
     */
    private function articleToRSSItem($article, $last_mod, $list)
    {
        if( !$article->canBeRendered() )
            return '';

        $img = $this->getArticleImageAsXML($article);

        $content = $this->cms->sanitizeText($article->getContent());
        $content = $this->cms->applyFilters('arlima_rss_content', $content, $article, $list);
        $url = $article->getURL();
        $guid = $url;

        if ( $post_id = $article->getPostID() ) {
            $guid = '/?p=' . $post_id;
            $date = date('r', $article->getPublishTime());
        } else {
            $date = date('r', $last_mod);
        }

        return '<item>
                    <title><![CDATA[' . $article->getTitle() . ']]></title>
                    <description><![CDATA[' . $content . ']]></description>
                    <link>' . $this->cms->applyFilters('arlima_rss_link', $url, $list) . '</link>
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
     * @param Arlima_List|int $list
     * @return bool
     */
    public function isAvailableForExport($list)
    {
        $id = is_object($list) ? $list->getId() : $list;
        return in_array($id, $this->available_export);
    }

    /**
     * @param $format
     * @param $page_id
     */
    private function outputListConnectedToPage($format, $page_id)
    {
        $list = Arlima_List::builder()
                    ->fromPage($page_id)
                    ->includeFutureArticles()
                    ->build();

        if (!$list->exists()) {
            $this->sendErrorToClient(self::ERROR_LIST_DOES_NOT_EXIST, '404 List not found', $format);
        } elseif (!$this->isAvailableForExport($list)) {
            $this->sendErrorToClient(self::ERROR_LIST_BLOCKED_FROM_EXPORT, '403 Forbidden', $format);
        } else {
            echo $this->convertList($list, $format);
        }
    }

    /**
     * @param Arlima_List $list
     * @param string $base_url
     * @return mixed
     */
    private function getListAsRSS($list, $base_url)
    {
        $rss = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/"><channel>
                <title><![CDATA[' . $list->getTitle() . ']]></title>
                <description>RSS export for article list &quot;' . $list->getTitle() . '&quot;</description>
                <link>' . $base_url . '</link>
                <lastBuildDate>' . date('r') . '</lastBuildDate>
                <pubDate>' . date('r', $list->lastModified()) . '</pubDate>
                <ttl>1</ttl>
                <generator>Arlima v' . ARLIMA_PLUGIN_VERSION . ' (wordpress plugin)</generator>
                ';

        $list_last_mod_time = $list->lastModified();
        foreach ($list->getArticles() as $article) {
            $rss .= $this->articleToRSSItem($article, $list_last_mod_time, $list);
            foreach ($article->getChildArticles() as $child_article) {
                $rss .= $this->articleToRSSItem($child_article, $list_last_mod_time, $list);
            }
        }
        $rss .= '</channel></rss>';
        return $this->cms->applyFilters('arlima_rss_feed', $rss, $list);
    }

    /**
     * @param Arlima_List $list
     * @param string $base_url
     * @return mixed|string|void
     */
    private function getListAsJSON($list, $base_url)
    {
        $list_data = $list->toArray();
        foreach ($list_data['articles'] as $i => $art) {
            $this->prepareArticleForExport($list_data['articles'][$i], $base_url);
        }

        return json_encode($list_data);
    }

    /**
     * @param $article
     * @return string
     */
    private function getArticleImageAsXML($article)
    {
        $img = '';
        if ($img_url = $article->getImageURL()) {

            $node_type = ARLIMA_RSS_IMG_TAG;
            $img_data = array(
                'attachment' => $article->getImageId(),
                'alignment' => $article->getImageAlignment(),
                'size' => $article->getImageSize()
            );
            $img_url = $this->cms->applyFilters('arlima_rss_image', $img_url, $img_data);
            $img_type = pathinfo($img_url, PATHINFO_EXTENSION);
            $img_type = 'image/' . current(explode('?', $img_type));

            if ($node_type == 'media:content') {
                $img = '<media:content url="' . $img_url . '" type="' . $img_type . '" />';
                return $img;
            } elseif ($node_type == 'image') {
                $img = '<image>' . $img_url . '</image>';
                return $img;
            } else {
                $img = '<enclosure url="' . $img_url . '" length="1" type="' . $img_type . '"  />';
                return $img;
            }
        }
        return $img;
    }
}