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

    static function initLocalization()
    {
        if ( !self::$has_loaded_textdomain ) {
            self::$has_loaded_textdomain = true;
            load_plugin_textdomain('arlima', false, basename(ARLIMA_PLUGIN_PATH).'/lang/');
        }
    }

    /**
     * @inheritdoc
     */
    function translate($str)
    {
        self::initLocalization();
        return __($str, 'arlima');
    }

    /**
     * @inheritdoc
     */
    function currentVisitorCanEdit()
    {
        return is_user_logged_in() && current_user_can('edit_posts');
    }

    /**
     * @inheritdoc
     */
    function getContentOfPostInGlobalScope()
    {
        return get_the_content();
    }

    /**
     * @inheritdoc
     */
    function dbEscape($input)
    {
        return esc_sql(stripslashes($input));
    }

    /**
     * @inheritdoc
     */
    function getPageEditURL($page_id)
    {
        $id = is_object($page_id) ? $page_id->ID : $page_id;
        return admin_url('post.php?action=edit&amp;post=' . $id);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    function humanTimeDiff($time)
    {
        return human_time_diff(Arlima_Utils::timeStamp(), $time);
    }

    /**
     * @inheritdoc
     */
    function getListEditURL($id)
    {
        return admin_url('admin.php?page=arlima-main&open_list='.$id);
    }

    /**
     * @inheritdoc
     */
    function getPostURL($post_id)
    {
        return get_permalink($post_id);
    }

    /**
     * @inheritdoc
     */
    function getPageIdBySlug($slug)
    {
        $sql = $this->prepare( "SELECT ID, post_type FROM ".$this->getDBPrefix()."posts WHERE post_name = %s AND post_type = 'page' ", $slug );
        $data = $this->runSQLQuery( $sql );
        return $data ? $data[0]->ID : false;
    }

    /**
     * @inheritdoc
     */
    function getBaseURL()
    {
        return get_bloginfo('url');
    }

    /**
     * @inheritdoc
     */
    function sanitizeText($txt, $allowed='')
    {
        $pattern = get_shortcode_regex();
        return strip_tags(preg_replace_callback( "/$pattern/s", 'strip_shortcode_tag', $txt), $allowed);
    }

    /**
     * @inheritdoc
     */
    function getImportedLists()
    {
        $plugin = new Arlima_WP_Plugin();
        $settings = $plugin->loadSettings();
        return !empty($settings['imported_lists']) ? $settings['imported_lists'] : array();
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function saveImportedLists($lists)
    {
        $plugin = new Arlima_WP_Plugin();
        $settings = $plugin->loadSettings();
        $settings['imported_lists'] = $lists;
        $plugin->saveSettings($settings);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    function doAction()
    {
        return call_user_func_array('do_action', func_get_args());
    }

    /**
     * @inheritdoc
     */
    function applyFilters()
    {
        return call_user_func_array('apply_filters', func_get_args());
    }

    /**
     * @inheritdoc
     */
    function scheduleEvent($schedule_time, $event, $args)
    {
        wp_schedule_single_event( $schedule_time, $event, $args );
    }



    /* * * * DB functions * * * */


    /**
     * @inheritdoc
     */
    function prepare($sql, $params)
    {
        return $this->wpdb->prepare($sql, $params);
    }

    /**
     * @inheritdoc
     */
    function getDBPrefix()
    {
        return $this->wpdb->prefix;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function dbTableExists($tbl)
    {
        $this->wpdb->query('SHOW TABLES LIKE \''.$tbl.'\'');
        return $this->wpdb->num_rows == 1;
    }

    /**
     * @inheritdoc
     */
    public function flushCaches()
    {
        wp_cache_flush();
    }


    /* * * Relation between lists and posts * * * * */


    const META_KEY_LIST = '_arlima_list';
    const META_KEY_ATTR = '_arlima_list_data';


    /**
     * @inheritdoc
     */
    public function relate($list, $post_id, $attr)
    {
        update_post_meta($post_id, self::META_KEY_LIST, is_object($list) ? $list->getId() : $list);
        update_post_meta($post_id, self::META_KEY_ATTR, $attr);
    }

    /**
     * @inheritdoc
     */
    public function removeAllRelations($list)
    {
        foreach($this->loadRelatedPages($list) as $p) {
            $this->removeRelation($p->ID);
        }
    }

    /**
     * @inheritdoc
     */
    public function removeRelation($post_id)
    {
        delete_post_meta($post_id, self::META_KEY_LIST);
        delete_post_meta($post_id, self::META_KEY_ATTR);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    private function findWidgetObject($registered_data)
    {
        if( !empty($registered_data['callback']) && !empty( $registered_data['callback'][0] ) ) {
            return is_object($registered_data['callback'][0]) ? $registered_data['callback'][0] : null;
        }
        return null;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    function generateImageVersion($file, $attach_url, $max_width, $img_id)
    {
        $version_manager = new Arlima_WP_ImageVersionManager($img_id, new Arlima_WP_Plugin($this));
        $img_url = $version_manager->getVersionURL($max_width);
        return $img_url ? $img_url : $attach_url;
    }

    /**
     * @inheritdoc
     */
    function getImageData($img_id)
    {
        $meta = wp_get_attachment_metadata($img_id);
        if( $meta ) {
            return array($meta['height'], $meta['width'], Arlima_WP_ImageVersionManager::uploadDirData('basedir').'/'.$meta['file']);
        } else {
            return array(0,0,'');
        }
    }

    /**
     * @inheritdoc
     */
    function getImageURL($img_id)
    {
        return  wp_get_attachment_url($img_id);
    }


    /* * * * Article loading / iteration * * * * * */

    /**
     * @inheritdoc
     */
    function prepareForPostLoop($list)
    {
        $this->doAction('arlima_rendering_init', $list);
        $this->p = $this->getPostInGlobalScope();
    }

    /**
     * @inheritdoc
     */
    function getPostTimeStamp($p)
    {
        $date_prop = ARLIMA_POST_DATE_PROP;
        if( is_numeric($p) )
            $p = get_post($p);

        return $p instanceof WP_Post ? strtotime( $p->$date_prop ) : 0;
    }

    /**
     * @inheritdoc
     */
    function resetAfterPostLoop()
    {
        // unset global post data
        $this->setPostInGlobalScope($this->p);
        wp_reset_query();
    }

    /**
     * @inheritdoc
     */
    function getPostInGlobalScope()
    {
        return isset($GLOBALS['post']) ? $GLOBALS['post']:false;
    }

    /**
     * @inheritdoc
     */
    function setPostInGlobalScope($post)
    {
        $GLOBALS['post'] = is_numeric($post) ? get_post($post) : $post;
    }

    /**
     * @inheritdoc
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

    /**
     * @inheritdoc
     */
    function havePostsInLoop()
    {
        if( have_posts() ) {
            the_post();
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    function getPostIDInLoop()
    {
        return isset($GLOBALS['post']) ? $GLOBALS['post']->ID : 0;
    }

    /**
     * @inheritdoc
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

    /**
     * @inheritdoc
     */
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

    /**
     * @param $meta_type
     * @param $object_ids
     */
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
     * @inheritdoc
     */
    function isPreloaded($id)
    {
        return wp_cache_get($id, 'posts') ? true : false;
    }

    /**
     * @inheritdoc
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