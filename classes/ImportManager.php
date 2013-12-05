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
     * @var array
     */
    private $imported_lists;

    /**
     * @var Arlima_Plugin
     */
    private $arlima_plugin;

    /**
     * @param Arlima_Plugin $arlima_plugin[optional=null]
     */
    public function __construct($arlima_plugin = null)
    {
        if ( $arlima_plugin !== null ) {
            $this->setPlugin($arlima_plugin);
        }
    }

    /**
     * @param Arlima_Plugin $arlima_plugin
     */
    function setPlugin($arlima_plugin)
    {
        $settings = $arlima_plugin->loadSettings();
        $this->imported_lists = !empty($settings['imported_lists']) ? $settings['imported_lists'] : array();
        $this->arlima_plugin = $arlima_plugin;
    }

    /**
     * @return array
     */
    function getImportedLists()
    {
        return $this->imported_lists;
    }

    /**
     * @param string $url
     */
    function removeImportedList($url)
    {
        if ( isset($this->imported_lists[$url]) ) {
            unset($this->imported_lists[$url]);
            $this->saveImportedLists();
        }
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
        if ( $refresh || empty($this->imported_lists[$url]) ) {
            $list = $this->loadList($url);
            $list_data = array(
                'title' => $list->getTitle(),
                'url' => $url
            );

            $this->imported_lists[$url] = $list_data;
            $this->saveImportedLists();
            return $list_data;
        } else {
            return $this->imported_lists[$url];
        }
    }

    /**
     * @param string $url
     * @return array|WP_Error
     */
    protected function loadExternalURL($url)
    {
        if ( !class_exists('WP_Http') ) {
            require ABSPATH . '/wp-includes/class-http.php';
        }
        $http = new WP_Http();
        $response = $http->get($url);
        return $response;
    }

    /**
     * Save an array with all the lists that we use to import
     */
    private function saveImportedLists()
    {
        $settings = $this->arlima_plugin->loadSettings();
        $settings['imported_lists'] = $this->imported_lists;
        $this->arlima_plugin->saveSettings($settings);
    }

    /**
     * @deprecated
     * @see Arlima_ImportMAnager::loadList()
     */
    function loadListContent($url)
    {
        return $this->loadList($url);
    }

    /**
     * Load a Arlima list or RSS feed from a remote website and convert to Arlima list object
     * @param string $url
     * @return Arlima_List
     */
    function loadList($url)
    {
        return $this->serverResponseToArlimaList($this->loadExternalURL($url), $url);
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
        if ( $response instanceof WP_Error ) {
            throw new Exception($response->get_error_message());
        }

        $response_type = $this->getResponseType($response);

        if ( $response_type === null ) {
            throw new Exception('Remote server did not respond with neither JSON nor RSS? got content-type: ' . $response['headers']['content-type']);
        }
        if ( $response['response']['code'] != 200 ) {
            $list_data = @json_decode($response['body'], true);
            $error_message = $list_data && isset($list_data['error']) ? $list_data['error'] : $response['body'];
            throw new Exception('Remote server responded with error: ' . $error_message . ' (status ' . $response['response']['code'] . ')');
        }

        // Parse response
        $list_data = $this->parseListData($response['body'], $response_type);

        // Populate the imported list
        $list = new Arlima_List(true, $url, true);

        foreach ($list_data as $prop => $val) {
            $set_method = 'set' . ucfirst($prop);
            if ( method_exists($list, $set_method) ) {
                if ( $val instanceof stdClass ) {
                    $val = (array)$val;
                }

                call_user_func(array($list, $set_method), $val);
            }
        }

        $base_url = str_replace(array('http://', 'www.'), '', $url);
        $base_url = substr($base_url, 0, strpos($base_url, '/'));
        $list->setTitle('[' . $base_url . '] ' . $list->getTitle());

        return $list;
    }

    /**
     * @param array $article_data
     * @return array
     */
    private function moveURLToOverridingURL( $article_data )
    {
        $url = !empty($article_data['url']) ? $article_data['url'] : $article_data['external_url'];
        $article_data['options']['overriding_url'] =  $url;
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
                    $list_data['articles'][$key] = $this->moveURLToOverridingURL($data);
                    if( !empty($data['children']) ) {
                        foreach($data['children'] as $child_key => $child_article) {
                            $list_data['articles'][$key]['children'][$child_key] = $this->moveURLToOverridingURL($child_article);
                        }
                    }
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
            $list_data = array(
                'title' => (string)$xml->channel->title,
                'slug' => sanitize_title((string)$xml->channel->title),
                'articles' => array(),
                'versions' => array(),
                'version' => array('id' => 0, 'user_id' => 0, 'created' => strtotime($pub_date))
            );

            if ( !empty($xml->channel->item) ) {
                foreach ($xml->channel->item as $item) {
                    $guid = (string)$item->guid;
                    $list_data['articles'][$guid] = $this->itemNodeToArticle($item);
                    if ( $list_data['articles'][$guid]['publish_date'] > $list_data['version']['created'] ) {
                        // ... when people don't really care about the RSS specs
                        $list_data['version']['created'] = $list_data['articles'][$guid]['publish_date'];
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
        $img_options = array();

        // get image from node
        if ( isset($item->image) ) {
            $img_options = $this->generateArticleImageOptions((string)$item->image);
        }

        // get image from enclosure
        elseif ( isset($item->enclosure) && $this->isEnlcosureValidImage($item->enclosure) ) {
            $img_options = $this->generateArticleImageOptions((string)$item->enclosure->attributes()->url);
        }

        // Try to find an image in the description
        else {
            preg_match('/<img[^>]+\>/i', (string)$item->description, $matches);
            if ( isset($matches[0]) ) {
                preg_match('/src="([^"]*)"/i', $matches[0], $src);
                if ( isset($src[1]) ) {
                    $source = trim($src[1]);
                    $ext = strtolower(substr($source, -4));
                    if ( $ext == '.jpg' || $ext == 'jpeg' || $ext == '.png' || $ext == '.gif' ) {
                        $img_options = $this->generateArticleImageOptions($source);
                    }
                }
            }
        }

        // description cleanup
        $description = strip_tags((string)$item->description, '<em><strong><span><cite><code><pre>');
        $description = force_balance_tags('<p>' . trim($description) . '</p>', true);
        $description = $description == '<p></p>' ? '<p>...</p>' : str_replace(array('"', '”'), '&quot;', $description);

        $post_date = strtotime((string)$item->pubDate);

        $art = Arlima_ListFactory::createArticleDataArray(
            array(
                'image_options' => $img_options,
                'text' => $description,
                'title' => (string)$item->title,
                'publish_date' => $post_date,
                'html_title' => '<h2>' . ((string)$item->title) . '</h2>'
            )
        );

        $art['options']['overriding_url'] = (string)$item->link;

        return $art;
    }

    /**
     * @param string $src
     * @return array
     */
    private function generateArticleImageOptions($src)
    {
        return array(
            'url' => $src,
            'html' => '<img src="' . $src . '" alt="" class="attachment large" />',
            'image_class' => 'attachment',
            'image_size' => 'large',
            'attach_id' => 0
        );
    }

    /**
     * @param SimpleXMLElement|stdClass $enc
     * @return bool
     */
    private function isEnlcosureValidImage($enc)
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
}