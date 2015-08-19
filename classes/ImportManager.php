<?php


/**
 * Class that can import lists in JSON or RSS format from a remote server, and turn them
 * into Arlima_List objects.
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_ImportManager
{

    /**
     */
    public function __construct()
    {
        $this->sys = Arlima_CMSFacade::load();
    }

    /**
     * @return array
     */
    function getImportedLists()
    {
        return $this->sys->getImportedLists();
    }

    /**
     * This function is used to register an external list as an imported list,
     * if you only want to fetch content from an external list use Arlima_ImportManager::loadList()
     * @param string $url
     * @param bool $refresh[optional=true]
     * @throws Exception
     * @return array containing 'title' and 'url'
     */
    function importList($url, $refresh = true)
    {
        $imported_lists = $this->getImportedLists();
        if ( $refresh || empty($imported_lists[$url]) ) {
            $list = $this->loadList($url);
            $list_data = array(
                'title' => $list->getTitle(),
                'url' => $url
            );

            $imported_lists[$url] = $list_data;
            $this->sys->saveImportedLists($imported_lists);
            return $list_data;
        } else {
            return $imported_lists[$url];
        }
    }

    /**
     * Load a Arlima list or RSS feed from a remote website and convert to Arlima list object
     * @param $url
     * @return Arlima_List
     * @throws Arlima_FailedListImportException
     */
    function loadList($url)
    {
        try {
            return $this->serverResponseToArlimaList($this->sys->loadExternalURL($url), $url);
        } catch(Exception $e) {
            $exc = new Arlima_FailedListImportException('Failed importing '.$url.', '. $e->getMessage(), $e->getCode(), $e);
            $exc->setURL($url);
            throw $exc;
        }
    }

    /**
     * @param $response
     * @return null|string
     */
    private function getResponseType($response)
    {
        $type = null;
        if ( strpos($response['headers']['content-type'], 'json') !== false ) {
            $type = 'json';
        } elseif ( strpos($response['headers']['content-type'], 'rss') !== false ||
                strpos($response['headers']['content-type'],'text/xml') !== false ) {
            $type = 'rss';
        }

        return $type;
    }

    /**
     * @param mixed $response
     * @param string $url
     * @throws Exception
     * @return Arlima_List
     */
    public function serverResponseToArlimaList($response, $url)
    {
        $response_type = $this->getResponseType($response);

        if ( $response_type === null ) {
            throw new Exception('Remote server did not respond with neither JSON nor RSS? got content-type: ' . $response['headers']['content-type']);
        }
        if ( $response['response']['code'] != 200 ) {
            $list_data = @json_decode($response['body'], true);
            $error_message = $list_data && isset($list_data['error']) ? $list_data['error'] : $response['body'];
            throw new Exception('Remote server responded with error: ' . $error_message . ' (status ' . $response['response']['code'] . ')');
        }

        $list = new Arlima_List(true, $url, true);
        $list_data = $this->parseListData($response['body'], $response_type);
        $this->populateList($list_data, $list);
        $this->setListVersionInfo($list_data, $list);
        $this->setListTitle($url, $list);

        return $list;
    }

    /**
     * @param array $article_data
     * @return array
     */
    private function moveURLToOverridingURL( $article_data )
    {
        $external_url = isset($article_data['externalURL']) ? $article_data['externalURL']:$article_data['external_url']; // If loading a list from an installation having Arlima version < 3.0
        $url = !empty($article_data['url']) ? $article_data['url'] : $external_url;
        $article_data['options']['overridingURL'] =  $url;
        $article_data['options']['target'] =  '_blank';
        return $article_data;
    }

    /**
     * @param string $str
     * @param string $response_type
     * @return array|mixed
     * @throws Exception
     */
    private function parseListData($str, $response_type)
    {
        $list_data = array();

        // JSON DATA
        if ( $response_type == 'json' ) {
            $list_data = @json_decode($str, true);
            if ( !$list_data ) {
                throw new Exception('Unable to parse json. json error: ' . self::getLastJSONErrorMessage());
            }
            if ( empty($list_data['title']) || empty($list_data['slug']) || !isset($list_data['articles']) ) {
                throw new Exception('JSON data invalid. Properties "title", "slug" and "articles" is mandatory');
            }
            if( !empty($list_data['articles']) ) {
                foreach($list_data['articles'] as $key => $data) {
                    $children_copy = $data['children'];
                    unset($data['children']);
                    $article = Arlima_ListVersionRepository::createArticle($this->moveURLToOverridingURL($data));
                    foreach($children_copy as $child_article) {
                        $article->addChild($this->moveURLToOverridingURL($child_article));
                    }
                    $list_data['articles'][$key] = $article;
                }
            }
        }

        // RSS DATA
        else {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($str);
            if ( !$xml ) {
                throw new Exception('Unable to parse xml'); // todo: display what error that was thrown internally
            }
            if ( empty($xml->channel) || empty($xml->channel->title) ) {
                throw new Exception('Not a valid rss format, could not find title nor items');
            }

            $pub_date = isset($xml->channel->pubDate) ? (string)$xml->channel->pubDate : (string)$xml->channel->lastBuildDate;
            $version = array('id' => 0, 'user' => 0, 'created' => strtotime($pub_date), 'status' => Arlima_List::STATUS_PUBLISHED);
            $list_data = array(
                'title' => rtrim((string)$xml->channel->title),
                'slug' => sanitize_title((string)$xml->channel->title),
                'articles' => array(),
                'versions' => array($version),
                'version' => $version
            );

            if ( !empty($xml->channel->item) ) {
                foreach ($xml->channel->item as $item) {
                    $guid = (string)$item->guid;
                    $list_data['articles'][$guid] = $this->itemNodeToArticle($item);
                    if ( $list_data['articles'][$guid]['published'] > $list_data['version']['created'] ) {
                        // ... when people don't really care about the RSS specs
                        $list_data['version']['created'] = $list_data['articles'][$guid]['published'];
                    }
                }
            }
        }

        return $list_data;
    }

    /**
     * @param SimpleXMLElement|stdClass $item
     * @return array
     */
    private function itemNodeToArticle($item)
    {
        $data = array(
            'image' => $this->getImageDataFromRSSItem($item),
            'content' => $this->getDescriptionFromRSSItem($item),
            'title' => (string)$item->title,
            'published' => strtotime((string)$item->pubDate),
            'options' => array(
                'overridingURL' => (string)$item->link
            )
        );

        return Arlima_ListVersionRepository::createArticle($data);
    }

    /**
     * @param string $src
     * @return array
     */
    private function generateArticleImageOptions($src)
    {
        return array(
            'url' => $src,
            'alignment' => 'alignright',
            'size' => 'full',
            'attachment' => 0
        );
    }

    /**
     * @param SimpleXMLElement|stdClass $enc
     * @return bool
     */
    private function isEnclosureValidImage($enc)
    {
        $attr = $enc->attributes();
        return isset($attr->type) && in_array(
            strtolower($attr->type),
            array('image/jpg', 'image/jpeg', 'image/gif', 'image/png')
        );
    }

    /**
     * @static
     * @return string
     */
    private static function getLastJSONErrorMessage()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return ' - No errors';
                break;
            case JSON_ERROR_DEPTH:
                return ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                return ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                return ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                return ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                return ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                return ' - Unknown error';
                break;
        }
    }

    /**
     * Helper function that displays info and remove button
     * for an imported list
     * @param string $url
     * @param string $name
     */
    public static function displayImportedList($url, $name)
    {
        ?>
        <div class="imported">
            <strong><?php echo $name ?></strong>
            <a href="#" class="del" data-link="<?php echo $url ?>">&times;</a>
            <br/>
            <a href="<?php echo $url ?>" target="_blank"><?php echo $url ?></a>
        </div>
        <?php
    }

    /**
     * @param string $url
     * @param Arlima_List $list
     */
    private function setListTitle($url, $list)
    {
        $base_url = str_replace(array('http://', 'www.'), '', $url);
        $base_url = substr($base_url, 0, strpos($base_url, '/'));
        if ($list->getTitle()) {
            $list->setTitle('[' . $base_url . '] ' . $list->getTitle());
        } else {
            $parts = explode('/', $url);
            $list->setTitle('[' . $base_url . '] ...' . implode('/', array_slice($parts, -2)));
        }
    }

    /**
     * @param array $list_data
     * @param Arlima_List $list
     * @return mixed
     */
    private function setListVersionInfo(&$list_data, $list)
    {
        if (empty($list_data['version_history'])) {
            $list_data['version_history'] = array();
            foreach ($list_data['versions'] as $ver) {
                $list_data['version_history'][] = array('id' => $ver, 'scheduled' => 0, 'status' => Arlima_List::STATUS_PUBLISHED, 'user_id' => 0);
            }
        }

        // This may not been set if external arlima site uses an older ARlima version
        $list->setScheduledVersions(empty($list_data['scheduled_versions']) ? array() : $list_data['scheduled_versions']);
        $list->setPublishedVersions($list_data['version_history']);

        if (isset($list_data['versions']))
            unset($list_data['versions']);return $list_data;
    }

    /**
     * Throw all data in $list_data into the list (except version info and list title which is managed by
     * two other functions)
     * @param array $list_data
     * @param Arlima_List $list
     */
    private function populateList($list_data, $list)
    {
        $called = array();
        foreach ($list_data as $prop => $val) {

            // @todo: wtf?? fix this the right way
            if (in_array($prop, $called)) {
                continue;
            }
            $called[] = $prop;

            $set_method = 'set' . ucfirst($prop);
            if ($prop != 'id' && $prop != 'versions' && method_exists($list, $set_method)) {
                if ($val instanceof stdClass) {
                    $val = (array)$val;
                }
                call_user_func(array($list, $set_method), $val);
            }
        }
    }

    /**
     * @param $item
     * @return mixed|string
     */
    private function getDescriptionFromRSSItem($item)
    {
        $description = strip_tags((string)$item->description, '<em><strong><span><cite><code><pre>');
        $description = force_balance_tags('<p>' . trim($description) . '</p>', true);
        $description = $description == '<p></p>' ? '<p>...</p>' : str_replace(array('"', '”'), '&quot;', $description);
        return $description;
    }

    /**
     * @param \SimpleXMLElement|\stdClass $item
     * @return array
     */
    private function getImageDataFromRSSItem($item)
    {
        $img_options = array();

        if (isset($item->image)) {
            // get image from node
            $img_options = $this->generateArticleImageOptions((string)$item->image);
        }
        elseif (isset($item->enclosure) && $this->isEnclosureValidImage($item->enclosure)) {
            // get image from enclosure
            $img_options = $this->generateArticleImageOptions((string)$item->enclosure->attributes()->url);
        }
        else {
            // Try to find an image in the description
            preg_match('/<img[^>]+\>/i', (string)$item->description, $matches);
            if (isset($matches[0])) {
                preg_match('/src="([^"]*)"/i', $matches[0], $src);
                if ( isset($src[1]) ) {
                    $source = trim($src[1]);
                    $ext = strtolower(substr($source, -4));
                    if ($ext == '.jpg' || $ext == 'jpeg' || $ext == '.png' || $ext == '.gif') {
                        $img_options = $this->generateArticleImageOptions($source);
                    }
                }
            }
        }

        return $img_options;
    }
}