<?php


class Arlima_WP_Facade implements Arlima_CMSInterface
{

    private $p;

    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * @param null $wpdb
     */
    public function __construct($wpdb = null)
    {
        if( $wpdb === null ) {
            global $wpdb;
            $this->wpdb = $wpdb;
        } else {
            $this->wpdb = $wpdb;
        }
    }

    /**
     * @var bool
     */
    private static $has_loaded_textdomain = false;

    /**
     */
    static function initLocalization()
    {
        if ( !self::$has_loaded_textdomain ) {
            self::$has_loaded_textdomain = true;
            load_plugin_textdomain('arlima', false, basename(ARLIMA_PLUGIN_PATH).'/lang/');
        }
    }

    /**
     * Translate current string
     * @param $str
     * @return string
     */
    function translate($str)
    {
        self::initLocalization();
        return __($str, 'arlima');
    }

    /**
     * Tells whether or not current website visitor can edit pages/posts
     * @return bool
     */
    function currentVisitorCanEdit()
    {
        return is_user_logged_in() && current_user_can('edit_posts');
    }

    /**
     * @return string
     */
    function getContentOfPostInGlobalScope()
    {
        return get_the_content();
    }

    /**
     * Make string safe for use in a database query
     * @param string $input
     * @return string
     */
    function dbEscape($input)
    {
        return esc_sql(stripslashes($input));
    }

    /**
     * Get URL of where post/page with given id can be edited by an
     * administrator
     * @param int $page_id
     * @return string
     */
    function getPageEditURL($page_id)
    {
        $id = is_object($page_id) ? $page_id->ID : $page_id;
        return admin_url('post.php?action=edit&amp;post=' . $id);
    }

    /**
     * Returns the file path if it resides within the directory of the CMS.
     * @param string $path
     * @param bool $relative
     * @return bool|string
     */
    function resolveFilePath($path, $relative=false)
    {
        $paths = explode(basename(WP_CONTENT_DIR), $path);
        if( count($paths) > 1 ) {
            $new_path = $paths[1];
            if( file_exists(WP_CONTENT_DIR .'/'. $paths[1]) )
                return $relative ? 'wp-content'.$new_path : WP_CONTENT_DIR .'/'. $new_path;
        }
        return false;
    }

    /**
     * Get a human readable string explaining how long ago given time is, or how much time
     * there's left until the time takes place
     * @param int $time
     * @return string
     */
    function humanTimeDiff($time)
    {
        return human_time_diff(Arlima_Utils::timeStamp(), $time);
    }

    /**
     * Get URL of where arlima list with given id can be edited by an
     * administrator
     * @param int $id
     * @return string
     */
    function getListEditURL($id)
    {
        return admin_url('admin.php?page=arlima-main&open_list='.$id);
    }

    /**
     * Get URL for post/page with given id
     * @param int $post_id
     * @return string
     */
    function getPostURL($post_id)
    {
        return get_permalink($post_id);
    }

    /**
     * Get id of the page/post with given slug name
     * @param string $slug
     * @return int|bool
     */
    function getPageIdBySlug($slug)
    {
        $sql = $this->prepare( "SELECT ID, post_type FROM ".$this->getDBPrefix()."posts WHERE post_name = %s AND post_type = 'page' ", $slug );
        $data = $this->runSQLQuery( $sql );
        return $data ? $data[0]->ID : false;
    }

    /**
     * Get base URL of the website that the CMS provides
     * @return string
     */
    function getBaseURL()
    {
        return get_bloginfo('url');
    }

    /**
     * Sanitize text from CMS specific tags/code as well as ordinary html tags. Use
     * $allowed to tell which tags that should'nt become removed
     * @param string $txt
     * @param string $allowed
     * @return string
     */
    function sanitizeText($txt, $allowed='')
    {
        $pattern = get_shortcode_regex();
        return strip_tags(preg_replace_callback( "/$pattern/s", 'strip_shortcode_tag', $txt), $allowed);
    }

    /**
     * An array with URL:s of external lists
     * @return array
     */
    function getImportedLists()
    {
        $plugin = new Arlima_WP_Plugin();
        $settings = $plugin->loadSettings();
        return !empty($settings['imported_lists']) ? $settings['imported_lists'] : array();
    }

    /**
     * @param string $url
     * @return void
     */
    function removeImportedList($url)
    {
        $imported_lists = $this->getImportedLists();
        if ( isset($imported_lists[$url]) ) {
            unset($imported_lists[$url]);
            $this->saveImportedLists($imported_lists);
        }
    }

    /**
     * Save an array with URL:s of external lists that should be
     * available in the list manager
     * @param array $lists
     * @return mixed
     */
    public function saveImportedLists($lists)
    {
        $plugin = new Arlima_WP_Plugin();
        $settings = $plugin->loadSettings();
        $settings['imported_lists'] = $lists;
        $plugin->saveSettings($settings);
    }

    /**
     * Load the contents of an external URL. This function returns an array
     * with  'headers', 'body', 'response', 'cookies', 'filename' if request was
     * successful, or throws an Exception if failed
     * @param string $url
     * @return array
     * @throws Exception
     */
    public function loadExternalURL($url)
    {
        if ( !class_exists('WP_Http') ) {
            require ABSPATH . '/wp-includes/class-http.php';
        }
        $http = new WP_Http();
        $response = $http->get($url);
        if( $response instanceof WP_Error ) {
            throw new Exception('Unable to load external url '.$url.' message: '.$response->get_error_message());
        }
        return $response;
    }


    /* * * * Event functions * * */


    /**
     * Invoke a system event
     * @return mixed
     */
    function doAction()
    {
        return call_user_func_array('do_action', func_get_args());
    }

    /**
     * Filter data
     * @return mixed
     */
    function applyFilters()
    {
        return call_user_func_array('apply_filters', func_get_args());
    }

    /**
     * Schedule an event to take place in the future
     * @param int $schedule_time
     * @param string $event
     * @param mixed $args
     * @return void
     */
    function scheduleEvent($schedule_time, $event, $args)
    {
        wp_schedule_single_event( $schedule_time, $event, $args );
    }



    /* * * * DB functions * * * */


    /**
     * Prepare an SQL-statement.
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    function prepare($sql, $params)
    {
        return $this->wpdb->prepare($sql, $params);
    }

    /**
     * Get the prefix used in database table names
     * @return string
     */
    function getDBPrefix()
    {
        return $this->wpdb->prefix;
    }

    /**
     * Calls a method on DB and throws Exception if db error occurs
     * @param string $sql
     * @return mixed
     * @throws Exception
     */
    public function runSQLQuery($sql)
    {
        if( $sql instanceof WP_Error) {
            throw new Exception($sql->get_error_message());
        }
        elseif( !$sql ) {
            throw new Exception('Empty SQL, last error from wpdb: '.$this->wpdb->last_error);
        }

        $query_method = strtolower(current(explode(' ', trim($sql))));
        $obj = $query_method == 'select' ? $this->wpdb->get_results($sql) : $this->wpdb->query($sql);

        if( is_wp_error($obj) || $this->wpdb->last_error )
            throw new Exception($this->wpdb->last_error);

        switch( $query_method ) {
            case 'insert':
                return $this->wpdb->insert_id;
                break;
            case 'delete':
                return $this->wpdb->rows_affected;
                break;
            case 'update':
                return $this->wpdb->rows_affected;
                break;
            default:
                return $obj;
                break;
        }
    }

    /**
     * @param string $tbl
     * @return bool
     */
    public function dbTableExists($tbl)
    {
        $this->wpdb->query('SHOW TABLES LIKE \''.$tbl.'\'');
        return $this->wpdb->num_rows == 1;
    }

    /**
     * Flush all caches affecting arlima
     * @return void
     */
    public function flushCaches()
    {
        wp_cache_flush();
    }


    /* * * Relation between lists and posts * * * * */


    const META_KEY_LIST = '_arlima_list';
    const META_KEY_ATTR = '_arlima_list_data';


    /**
     * Relate an Arlima list with a post/page
     * @param Arlima_List $list
     * @param int $post_id
     * @param array $attr
     * @return void
     */
    public function relate($list, $post_id, $attr)
    {
        update_post_meta($post_id, self::META_KEY_LIST, is_object($list) ? $list->getId() : $list);
        update_post_meta($post_id, self::META_KEY_ATTR, $attr);
    }

    /**
     * Remove relations made between pages and given list
     * @param Arlima_List $list
     * @return void
     */
    public function removeAllRelations($list)
    {
        foreach($this->loadRelatedPages($list) as $p) {
            $this->removeRelation($p->ID);
        }
    }

    /**
     * Remove possible relation this post/page might have with an Arlima list
     * @param $post_id
     * @return void
     */
    public function removeRelation($post_id)
    {
        delete_post_meta($post_id, self::META_KEY_LIST);
        delete_post_meta($post_id, self::META_KEY_ATTR);
    }

    /**
     * Get an array with all pages that give list is related to
     * @param Arlima_List $list
     * @return array
     */
    public function loadRelatedPages($list)
    {
        if( $list->exists() ) {
            return get_pages(array(
                'meta_key' => self::META_KEY_LIST,
                'meta_value' => $list->getId(),
                'hierarchical' => 0
            ));
        }

        return array();
    }

    /**
     * Returns an array with info about widgets that's related to the list
     * @param Arlima_List $list
     * @return array
     */
    public function loadRelatedWidgets($list)
    {
        global $wp_registered_widgets;
        $related = array();
        $sidebars = wp_get_sidebars_widgets();

        if( is_array($sidebars) && is_array($wp_registered_widgets) ) {
            $list_id = $list->getId();
            $prefix_len = strlen(Arlima_WP_Widget::WIDGET_PREFIX);
            foreach($sidebars as $sidebar => $widgets) {
                $index = 0;
                foreach( $widgets as $widget_id ) {
                    $index++;
                    if( substr($widget_id, 0, $prefix_len) == Arlima_WP_Widget::WIDGET_PREFIX && !empty($wp_registered_widgets[$widget_id])) {
                        $widget = $this->findWidgetObject($wp_registered_widgets[$widget_id]);
                        if( $widget_id !== null) {
                            $settings = current( array_slice($widget->get_settings(), -1) );
                            if( $settings['list'] ==  $list_id )
                                $related[] = array('sidebar' => $sidebar, 'index' => $index, 'width' => $settings['width']);
                        }
                    }
                }
            }
        }

        return $related;
    }

    /**
     * @param array $registered_data
     * @return null|WP_Widget
     */
    private function findWidgetObject($registered_data)
    {
        if( !empty($registered_data['callback']) && !empty( $registered_data['callback'][0] ) ) {
            return is_object($registered_data['callback'][0]) ? $registered_data['callback'][0] : null;
        }
        return null;
    }

    /**
     * Get information about possible relations between given
     * post/page and Arlima lists
     * @param int $post_id
     * @return array
     */
    public function getRelationData($post_id)
    {
        $data = false;
        $list_id = get_post_meta($post_id, self::META_KEY_LIST, true);

        if ( $list_id ) {
            $data = array(
                'id' => $list_id,
                'attr' => get_post_meta($post_id, self::META_KEY_ATTR, true)
            );

            if( !is_array($data['attr']) ) {
                $data['attr'] = $this->getDefaultListAttributes();
            } else {
                $data['attr'] = array_merge($this->getDefaultListAttributes(), $data['attr']);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getDefaultListAttributes()
    {
        return array(
            'width' => 560,
            'offset' => 0,
            'limit' => 0,
            'position' => 'before'
        );
    }

    /**
     * Get URL of a file that resides within the directory of the CMS
     * @param string $file
     * @return string
     */
    public function getFileURL($file)
    {
        $file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
        $content_dir = str_replace(DIRECTORY_SEPARATOR, '/', WP_CONTENT_DIR);

        $url = WP_CONTENT_URL . str_replace($content_dir, '', $file);
        $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);

        return $url;
    }

    /**
     * Get the excerpt of a post/page
     * @param int $post_id
     * @param int $excerpt_length
     * @param string $allowed_tags
     * @return string
     */
    function getExcerpt($post_id, $excerpt_length = 35, $allowed_tags = '') {
        if(!$post_id) {
            return false;
        }
        $the_post = get_post($post_id);

        $the_excerpt = $the_post->post_excerpt;

        if(strlen(trim($the_excerpt)) == 0) {
            // If no excerpt, generate an excerpt from content
            $the_excerpt = $the_post->post_content;
            $the_excerpt = Arlima_Utils::shorten($the_excerpt, $excerpt_length, $allowed_tags);
        }
        return $the_excerpt;

    }

    /* * * * * * * Image stuff  * * * * * * */


    /**
     * Generate an image version of given file with given max width (resizing image).
     * Returns the $attach_url if not possible to create image version
     * @param string $file
     * @param string $attach_url
     * @param int $max_width
     * @param int $img_id
     * @return string
     */
    function generateImageVersion($file, $attach_url, $max_width, $img_id)
    {
        $version_manager = new Arlima_WP_ImageVersionManager($img_id, new Arlima_WP_Plugin($this));
        $img_url = $version_manager->getVersionURL($max_width);
        return $img_url ? $img_url : $attach_url;
    }


    /**
     * Get an array with info (height, width, file path) about image with given id
     * @param int $img_id
     * @return array
     */
    function getImageData($img_id)
    {
        $meta = wp_get_attachment_metadata($img_id);
        if( $meta ) {
            return array($meta['height'], $meta['width'], $meta['file']);
        } else {
            return array(0,0,'');
        }
    }

    function getImageURL($img_id)
    {
        return  wp_get_attachment_url($img_id);
    }


    /* * * * Article loading / iteration * * * * * */


    function prepareForPostLoop($list)
    {
        $this->doAction('arlima_rendering_init', $list);
        $this->p = $this->getPostInGlobalScope();
    }

    /**
     * Get publish time for the post with given id
     * @param int $post_id
     * @return mixed
     */
    function getPostTimeStamp($p)
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

        if( is_numeric($p) )
            $p = get_post($p);

        return $p instanceof WP_Post ? strtotime( $p->$date_prop ) : 0;
    }

    function resetAfterPostLoop()
    {
        // unset global post data
        $this->setPostInGlobalScope($this->p);
        wp_reset_query();
    }

    /**
     * @return bool|mixed
     */
    function getPostInGlobalScope()
    {
        return isset($GLOBALS['post']) ? $GLOBALS['post']:false;
    }

    function setPostInGlobalScope($post)
    {
        $GLOBALS['post'] = is_numeric($post) ? get_post($post) : $post;
    }

    /**
     * @param int|object $post
     * @param array $override
     * @return Arlima_Article
     */
    function postToArlimaArticle($post, $override=array()) {

        $post = is_numeric($post) ? get_post($post) : $post;

        $text = !empty($post->post_excerpt) ? $post->post_excerpt : $this->getExcerpt($post->ID);
        if ( stristr($text, '<p>') === false ) {
            $text = '<p>' . $text . '</p>';
        }

        $art_data = array_merge(array(
            'post' => $post->ID,
            'title' => $post->post_title,
            'content' => $text,
            'size' => 24,
            'created' => Arlima_Utils::timeStamp(),
            'published' => $this->getPostTimeStamp($post)
        ), $override);

        if ( has_post_thumbnail($post->ID) ) {
            $attach_id = get_post_thumbnail_id($post->ID);
            $art_data['image'] = array(
                'url' => wp_get_attachment_url($attach_id),
                'attachment' => $attach_id,
                'size' => 'full',
                'alignment' => '',
                'connected' => true
            );
        }

        return Arlima_ListVersionRepository::createArticle($art_data);
    }

    function havePostsInLoop()
    {
        if( have_posts() ) {
            the_post();
            return true;
        }
        return false;
    }

    function getPostIDInLoop()
    {
        return isset($GLOBALS['post']) ? $GLOBALS['post']->ID : 0;
    }

    /**
     * Get an array with 'attachmend' being image id, 'alignment', 'sizename' and 'url' of the image
     * that is related to the post/page with given id. Returns false if no image exists
     * @param $id
     * @return array|bool
     */
    function getArlimaArticleImageFromPost($id)
    {
        if( $img = get_post_thumbnail_id($id) ) {
            return array(
                'attachment' => $img,
                'alignment' => '',
                'size' => 'full',
                'url' => wp_get_attachment_url($img)
            );
        }
        return array();
    }

    function preLoadPosts($post_ids)
    {
        $post_ids = array_unique($post_ids);
        if( empty($post_ids) )
            return;

        /** @var wpdb $wpdb */
        global $wpdb;
        foreach( $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'posts WHERE ID in ('.implode(',', $post_ids).')') as $post_data ) {
            $post_data = sanitize_post( $post_data, 'raw' );
            wp_cache_add( $post_data->ID, $post_data, 'posts' );
        }
        $this->updateMetaCache('post', $post_ids);
    }

    private function updateMetaCache($meta_type, $object_ids)
    {
        global $wpdb;

        $cache_key = $meta_type . '_meta';
        $ids = array();
        $cache = array();

        foreach ( $object_ids as $id ) {
            $cached_object = wp_cache_get( $id, $cache_key );
            if ( false === $cached_object )
                $ids[] = $id;
            else {
                $cache[$id] = $cached_object;
            }
        }

        if ( empty( $ids ) )
            return;

        $id_list = join( ',', $ids );
        $id_column = 'meta_id';
        $column = $meta_type . '_id';
        $table = _get_meta_table($meta_type);

        $meta_list = $wpdb->get_results( "SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list) ORDER BY $id_column ASC", ARRAY_A );

        if ( !empty($meta_list) ) {
            foreach ( $meta_list as $metarow) {
                $mpid = intval($metarow[$column]);
                $mkey = $metarow['meta_key'];
                $mval = $metarow['meta_value'];

                // Force subkeys to be array type:
                if ( !isset($cache[$mpid]) || !is_array($cache[$mpid]) )
                    $cache[$mpid] = array();
                if ( !isset($cache[$mpid][$mkey]) || !is_array($cache[$mpid][$mkey]) )
                    $cache[$mpid][$mkey] = array();

                // Add a value to the current pid/key:
                $cache[$mpid][$mkey][] = $mval;
            }
        }

        foreach ( $ids as $id ) {
            if ( ! isset($cache[$id]) )
                $cache[$id] = array();
            wp_cache_add( $id, $cache[$id], $cache_key );
        }
    }

    /**
     * Tells whether or not a page/post with given id is preloaded
     * @param int $id
     * @return bool
     */
    function isPreloaded($id)
    {
        return wp_cache_get($id, 'posts') ? true : false;
    }

    /**
     * Get id the page/post that currently is being visited
     * @return int|bool
     */
    function getQueriedPageId()
    {
        if( is_page() ) {
            global $wp_query;
            return $wp_query->post->ID;
        }
        return false;
    }
}