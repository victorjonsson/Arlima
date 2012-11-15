<?php

/**
 * Class with all the know-how about article lists creation and how its data is stored in the database.
 * All direct use of wpdb in the Arlima plugin should be placed in this class, at least as long as
 * the database communication is about getting data related to article lists.
 *
 * @todo: Remove deprecated functions when moving up to version 3.0
 * @package Arlima
 * @since 2.0
 *
 */
class Arlima_ListFactory {

    const DB_VERSION = '1.7';

    /**
     * Default options for an article list
     * @var array
     */
    private $options = array(
            'previewtemplate' => 'article',
            'before_title' => '<h2>',
            'after_title' => '</h2>',
            'pagestopurge' => ''
        );

    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * @var Arlima_CacheManager
     */
    private $cache;

    /**
     * @param wpdb $db
     * @param null $cache
     */
    public function __construct($db = null, $cache = null) {
        $this->wpdb = $db === null ? $GLOBALS['wpdb'] : $db;
        $this->cache = $cache === null ? Arlima_CacheManager::loadInstance() : $cache;
    }

    /**
     * Creates a new article list
     * @param $title
     * @param $slug
     * @param array $options
     * @param int $max_length
     * @throws Exception
     * @return Arlima_List
     */
    public function createList($title, $slug, $options=array(), $max_length=50) {
        $options = array_merge($this->options, $options);

        $insert_data = array(
            time(),
            $title,
            $slug,
            $max_length,
            serialize( $options )
        );

        // New list!
        $sql = 'INSERT INTO ' . $this->wpdb->prefix . 'arlima_articlelist
                (al_created, al_title, al_slug, al_maxlength, al_options)
                VALUES (%d, %s, %s, %d, %s)';

        $this->wpdb->query( $this->wpdb->prepare($sql, $insert_data) );
        if($this->wpdb->last_error) {
            throw new Exception($this->wpdb->last_error);
        }

        $id = $this->wpdb->insert_id;

        // remove slug related cache, in case we have had a list with this slug before
        $cache = Arlima_CacheManager::loadInstance();
        $cache->delete('arlima_list_slugs');

        $list = new Arlima_List(true, $id);
        $list->setCreated($insert_data[0]);
        $list->setMaxlength($max_length);
        $list->setOptions($options);
        $list->setSlug($slug);
        $list->setTitle($title);

        return $list;
    }

    /**
     * Will update name, slug and options of given list
     * @param Arlima_List $list
     * @throws Exception
     */
    public function updateListProperties($list) {
        $update_data = array(
            $list->getTitle(),
            $list->getSlug(),
            $list->getMaxlength(),
            serialize( $list->getOptions() ),
            (int)$list->id()
        );

        $sql = 'UPDATE ' . $this->wpdb->prefix . 'arlima_articlelist
                    SET al_title = %s, al_slug = %s, al_options = %s
                    WHERE al_id = %d';
        $this->wpdb->query( $this->wpdb->prepare($sql, $update_data) );

        if($this->wpdb->last_error) {
            throw new Exception($this->wpdb->last_error);
        }

        // remove cache
        $this->cache->delete('arlima_list_props_'.$list->id());
    }

    /**
     * @param Arlima_List $list
     */
    public function deleteList($list) {

        // Get versions
        $version_data = $this->wpdb->get_results(sprintf(
                            "SELECT alv_id FROM %sarlima_articlelist_version WHERE alv_al_id=%d",
                            $this->wpdb->prefix,
                            (int)$list->id()
                        ));

        // Remove articles
        if( !empty($version_data) ) {
            foreach($version_data as $data) {
                $versions[] = $data->alv_id;
            }
            $this->wpdb->query(sprintf(
                        "DELETE FROM %sarlima_articlelist_article WHERE ala_alv_id in (%s)",
                        $this->wpdb->prefix,
                        implode(',', $versions)
                    ));
        }

        // Remove list properties
        $this->wpdb->query('DELETE FROM '.$this->wpdb->prefix.'arlima_articlelist WHERE al_id='.$list->id());

        // Remove versions
        $this->wpdb->query('DELETE FROM '.$this->wpdb->prefix.'arlima_articlelist_version WHERE alv_al_id='.$list->id() );

        // remove cache
        $this->cache->delete('arlima_list_props_'.$list->id());
        $this->cache->delete('arlima_list_article_data_'.$list->id());
    }

    /**
     * @param int $list
     * @param array $articles
     * @param bool $preview
     */
    public function saveNewListVersion($list, $articles, $preview = false) {
        $this->cache->delete('arlima_list_article_data_'.$list->id());

    }

    /**
     * @param $id
     * @param bool $version
     * @return Arlima_List
     */
    public function loadList($id, $version=false) {
        $list = $this->cache->get('arlima_list_'.$id);
        if( !$list ) {
            $list = $this->queryList($id);
            if( !$list->exists() ) {
                return $list;
            }

            $this->cache->set('arlima_list_props_'.$id, $list);
        }

        // Get latest version (using cache)
        if( !$version ) {

            $article_data = $this->cache->get('arlima_list_articles_data_'.$id);
            if( !$article_data ) {

                $article_data = array();
                $version_data = $this->queryVersionData($id, false);
                $article_data['version'] = $version_data[0];
                $article_data['version_list'] = $version_data[1];
                $article_data['articles'] = $this->queryListArticles(false, true);

                $this->cache->set('arlima_list_articles_data_'.$id, $article_data);
            }

            if( !empty($article_data) ) {
                $list->setStatus( Arlima_List::STATUS_PUBLISHED );
                $list->setArticles($article_data['articles']);
                $list->setVersions( $article_data['version_list'] );
                $list->setVersion( $article_data['version'] );
            }
        }

        // Preview version or specific version (no cache)
        else {
            list($version_data, $version_list) = $this->queryVersionData($id, $version);
            if( !empty($version_data) ) {
                $list->setVersion($version_data);
                $list->setVersions($version_list);
                $list->setArticles( $this->queryListArticles($version_data['id'], false) );
                $list->setStatus( $version === 'preview' ? Arlima_List::STATUS_PREVIEW : Arlima_List::STATUS_PUBLISHED);
            }
        }

        return $list;
    }


    /**
     * @param $list_id
     * @param $version
     * @return array
     */
    private function queryVersionData($list_id, $version) {

        $version_data_sql = "SELECT alv_id, alv_created, alv_status, alv_user_id FROM %sarlima_articlelist_version";

        // latest preview version
        if( $version === 'preview' ) {
            $version_data_sql = $this->wpdb->prepare(
                        $version_data_sql."WHERE alv_al_id = %d AND alv_status = %d",
                        $this->wpdb->prefix,
                        $list_id,
                        Arlima_List::STATUS_PREVIEW
                    );
        }

        // specific version
        elseif($version !== false) {
            $version_data_sql = $this->wpdb->prepare(
                        $version_data_sql."WHERE alv_id = %d",
                        $this->wpdb->prefix,
                        $version
                    );
        }

        // latest none preview version
        else {
            $version_data_sql = $this->wpdb->prepare(
                        $version_data_sql."WHERE alv_al_id = %d AND alv_status = %d",
                        $this->wpdb->prefix,
                        $list_id,
                        Arlima_List::STATUS_PUBLISHED
                    );
        }

        $version_data_sql .= 'ORDER BY alv_id DESC LIMIT 0,1';

        $version_list_sql = $this->wpdb->prepare (
            "SELECT alv_id FROM " . $this->wpdb->prefix . "arlima_articlelist_version
            WHERE alv_al_id = %d AND alv_status = %d
            ORDER BY alv_id DESC LIMIT 0,10",
            Arlima_List::STATUS_PUBLISHED,
            (int)$list_id
        );

        return array(
            self::removePrefix($this->wpdb->get_row($version_data_sql), 'alv_'),
            $this->wpdb->get_col($version_list_sql)
        );
    }

    /**
     * @param int $id
     * @throws Exception
     * @return Arlima_List
     */
    private function queryList($id)
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM " . $this->wpdb->prefix . "arlima_articlelist WHERE al_id = %d",
            (int)$id
        );

        $result = $this->wpdb->get_row($sql);
        if( is_wp_error($result) )
            throw new Exception( (string)$result );

        $list_data = self::removePrefix($result, 'al_');
        if ( empty($list_data) ) {
            return new Arlima_List(false);
        } else {
            $list = new Arlima_List(true, $id);
            $list->setCreated($list_data['created']);
            $list->setTitle($list_data['title']);
            $list->setSlug($list_data['slug']);
            $list->setOptions(unserialize($list_data['options']));
            return $list;
        }
    }

    /**
     * @see Arlima_ListFactory::loadList()
     * @param string $slug
     * @param bool $version
     * @return Arlima_List
     */
    public function loadListBySlug($slug, $version=false) {
        $id = self::getListId($slug);
        if( $id )
            return $this->loadList($id, $version);

        return new Arlima_List(false);
    }

    /**
     * Load latest preview version of article list with given id.
     * @param int $id
     * @return Arlima_List
     */
    public function loadLatestPreview($id) {
        if( !is_numeric($id) ) {
            Arlima_Plugin::warnAboutUseOfDeprecatedFunction('Arlima_ListFactory::loadLatestPreview', 2.5, 'Should be called using list id as argument, not slug');
            $id = self::getListId($id);
        }

        return $this->loadList($id, 'preview');
    }

    /**
     * @param $version
     * @param bool $exclude_future_posts
     * @return array
     */
    private function queryListArticles($version, $exclude_future_posts) {

        $sql = "SELECT ala_id, ala_created, ala_publish_date, ala_post_id, ala_title, ala_text, ala_status,
                        ala_title_fontsize, ala_url, ala_options, ala_image, ala_image_options, ala_parent, ala_sort
                FROM " . $this->wpdb->prefix . "arlima_articlelist_article %s ORDER BY ala_parent, ala_sort";

        $where = '';
        if($version)
            $where .= ' WHERE ala_alv_id='.intval($version);


        $articles = array();
        foreach($this->wpdb->get_results( sprintf($sql, $where) ) as $row) {

            if( unserialize( $row->ala_options ) !== false ) {
                $row->ala_options = unserialize( $row->ala_options );
            } else {
                $row->ala_options = array();
            }

            if( unserialize( $row->ala_image_options ) !== false ) {
                $row->ala_image_options = unserialize( $row->ala_image_options );
            } else {
                $row->ala_image_options = array();
            }

            $row->children = array();

            if( $row->ala_parent == -1 ) {
                $articles[] = self::removePrefix( $row, 'ala_' );
            } else {

                $articles[ $row->ala_parent ]['children'][] = self::removePrefix( $row, 'ala_' );
            }
        }

        // Remove future posts
        if( $exclude_future_posts ) {
            foreach( $articles as $i => $article ) {
                if( $article['publish_date'] && ( $article['publish_date'] > time() ) ) {
                    unset( $articles[$i] );
                }
            }

            // Reset the numerical order of keys that might have been
            // mangled when removing future articles
            $articles = array_values( $articles );
        }

        return $articles;
    }

    /**
     * will return an array looking like array( stdClass(id => ... title => ... slug => ...) )
     * @todo make none static
     * @static
     * @return array
     */
    public static function loadListSlugs() {
        /* @var wpdb $wpdb */
        global $wpdb;
        $cache = Arlima_CacheManager::loadInstance();
        $data = $cache->get('arlima_list_slugs');
        if(!is_array($data)) {
            $sql = 'SELECT al_id, al_title, al_slug FROM ' . $wpdb->prefix . 'arlima_articlelist ORDER BY al_title ASC';
            $data = self::removePrefix($wpdb->get_results($sql), 'al_', true);
            $cache->set('arlima_list_slugs', $data);
        }

        return $data;
    }

    /**
     * @static
     * @todo make none static
     * @param $slug
     * @return int|bool
     */
    public static function getListId($slug) {
        foreach(self::loadListSlugs() as $data) {
            if($data->slug == $slug)
                return $data->id;
        }

        return false;
    }


    /* * * * * * * * * * * *  DEPRECATED CODE * * * * * * * * * */


    /**
     * Create a new (empty) article list
     * @static
     * @param string $name
     * @param string $slug
     * @param array $options
     * @return Arlima_List
     */
    public static function create($name, $slug, $options = array()) {
        $list = new Arlima_List();
        $list->title = $name;
        $list->slug = $slug;
        $list->options = $options;
        $list->created = time();
        self::saveListProperties($list);
        return $list;
    }

    /**
     * @static
     * @param array $options
     * @return array
     */
    protected static function sanitizeListOptions($options) {
        $default_options = array(
            'previewpage' => '/',
            'previewtemplate' => 'article',
            'before_title' => '<h2>',
            'after_title' => '</h2>',
            'pagestopurge' => ''
        );

        // Override default options
        foreach($default_options as $name => $val) {
            if(empty($options[$name]))
                $options[$name] = $val;
        }

        // Remove options that does not exist
        $opt_names = array_keys($options);
        foreach($opt_names as $name) {
            if( !isset($default_options[$name]) )
                unset($options[$name]);
        }

        return $options;
    }

    /**
     * Will save the changes made to this list, except articles. If you only have changed the articles
     * you should use Arlima_ListFactory::saveNewVersion()
     *
     * @static
     * @param Arlima_List $list
     * @param bool $old_slug[optional=false] - Optional, for cache expiring using the old slug
     * @throws Exception
     */
    public static function saveListProperties($list, $old_slug=false) {
        /* @var wpdb $wpdb */
        global $wpdb;

        self::sanitizeList($list);
        $insert_data = array(
            $list->title,
            $list->slug,
            $list->status,
            $list->maxlength,
            serialize( $list->options )
        );

        // New list!
        if( !$list->id ) {
            array_unshift($insert_data, time());
            $sql = 'INSERT INTO ' . $wpdb->prefix . 'arlima_articlelist
                    (al_created, al_title, al_slug, al_status, al_maxlength, al_options)
                    VALUES (%d, %s, %s, %d, %d, %s)';
            $wpdb->query( $wpdb->prepare($sql, $insert_data) );
            $list->id = $wpdb->insert_id;

            // remove slug related cache, in case we have had a list with this slug before
            $cache = Arlima_CacheManager::loadInstance();
            $cache->delete('arlima_list_'.$old_slug);
            $cache->delete('arlima_list_slugs');
            $list->exists = true;
        }

        // Update list
        else {

            // Remove old cache
            self::removeListCache($list);
            if($old_slug)
                Arlima_CacheManager::loadInstance()->delete('arlima_list_'.$old_slug);

            array_push($insert_data, (int)$list->id);
            $sql = 'UPDATE ' . $wpdb->prefix . 'arlima_articlelist
                    SET al_title = %s, al_slug = %s, al_status = %d, al_maxlength = %d, al_options = %s
                    WHERE al_id = %d';
            $wpdb->query( $wpdb->prepare($sql, $insert_data) );
            if($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
        }
    }

    /**
     * @static
     * @param Arlima_List $list
     */
    private static function removeListCache($list) {
        $cache = Arlima_CacheManager::loadInstance();
        $cache->delete('arlima_list_id_'.$list->id);
        $cache->delete('arlima_list_'.$list->slug);
        $cache->delete('arlima_articles_id_'.$list->id);
        $cache->delete('arlima_version_id_'.$list->id);
        $cache->delete('arlima_version_data_id_'.$list->id);
        $cache->delete('arlima_list_slugs');
    }

    private static function filterNumbers($val, $default) {
        return is_numeric($val) ? (int)$val : (int)$default;
    }

    /**
     * @static
     * @param Arlima_List $list
     * @return array
     */
    protected static function sanitizeList( &$list ) {

        $list->title = stripslashes($list->title);
        $list->slug = sanitize_title(stripslashes($list->slug));
        $list->options = self::sanitizeListOptions($list->options);
        $list->options = array_map( 'stripslashes_deep', $list->options );
        $list->options['previewpage'] = str_replace(home_url(), '', $list->options['previewpage']);
        $list->status = self::filterNumbers($list->status, Arlima_List::STATUS_PUBLISHED);
        $list->maxlength = self::filterNumbers($list->maxlength, 50);
    }

    /**
     * Will save the articles teasers attached to given list as a new version of
     * this list (changes made to article properties will not be saved,
     * use Arlima_ListFactory::saveListProperties() in that case)
     *
     * @static
     * @param Arlima_List $list
     * @param int $user_id
     * @throws Exception
     */
    public static function saveNewVersion($list, $user_id) {
        /* @var wpdb $wpdb */
        global $wpdb;

        self::sanitizeList($list);
        $old_versions = array();

        if(!$list->exists)
            throw new Exception('You can not create a new version of a list that does not exist');
        if($list->is_imported)
            throw new Exception('You can not save a new version of a list that is imported');

        if( $list->status == Arlima_List::STATUS_PUBLISHED ) {

            //fetch all versions older than the last 10
            $sql = $wpdb->prepare( "SELECT alv_id FROM " . $wpdb->prefix . "arlima_articlelist_version WHERE alv_al_id = %d AND alv_status = 1 ORDER BY alv_created DESC LIMIT 10, 10",
                $list->id
            );
            $old_versions = $wpdb->get_col( $sql );
        }

        //fetch all old previews
        $sql = $wpdb->prepare( "SELECT alv_id FROM " . $wpdb->prefix . "arlima_articlelist_version WHERE alv_al_id = %d AND alv_status = 2",
            $list->id
        );

        $old_previews = $wpdb->get_col( $sql );
        $old_versions = array_merge( $old_versions, $old_previews );

        // delete all versions older than the last ten, plus all old previews
        if( sizeof( $old_versions ) > 0 ) {
            // use sprintf instead of prepare, since prepare doesnt work well with comma separated integers
            $sql = sprintf( "DELETE FROM " . $wpdb->prefix . "arlima_articlelist_article WHERE ala_alv_id IN (%s)",
                implode( ',', $old_versions )
            );
            $wpdb->query( $sql );

            $sql = sprintf( "DELETE FROM " . $wpdb->prefix . "arlima_articlelist_version WHERE alv_id IN (%s)",
                implode( ',', $old_versions )
            );
            $wpdb->query( $sql );
        }

        // add the new version
        $sql = $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "arlima_articlelist_version (alv_created, alv_al_id, alv_status, alv_user_id) VALUES (%d, %s, %d, %d)",
            time(),
            $list->id,
            $list->status,
            $user_id
        );

        $wpdb->query( $sql );
        $version = $wpdb->insert_id;

        // Delete old cache
        self::removeListCache($list);

        // Add version data to the list we're working on
        self::addVersion($list, $version);

        // Save the articles of this verion of the list
        if( $list->numArticles() > 0 ) {
            $count = 0;

            // Update possibly changed published date
            $post_id_map = array();
            foreach( $list->articles as $i => $article ) {
                if( !empty($article['post_id']) ) {
                    $post_id_map[$i] = $article['post_id'];
                }
            }

            if( !empty($post_id_map) ) {
                $sql = "SELECT post_date_gmt, ID FROM %sposts WHERE ID in (%s)";
                $sql = sprintf($sql, $wpdb->prefix, implode(',', $post_id_map));
                foreach($wpdb->get_results($sql) as $row) {
                    foreach( array_keys($post_id_map, $row->ID) as $key ) {
                        $list->articles[$key]['publish_date'] = strtotime($row->post_date_gmt);
                    }
                }
            }

            foreach( $list->articles as $sort => $article ) {
                self::saveArticle($article, $sort, -1, $list, $count);
                $count++;
                if( $count >= ( $list->maxlength-1 ) )
                    break;
            }
        }

        // remove cache once more, the odds of cache being generated during creation of the articles
        // is probably slim to null but it doesn't hurt to clear the cache again any how...
        self::removeListCache($list);
    }

    /**
     * @static
     * @param array $article
     * @param mixed $sort,
     * @param int $parent[optional=-1]
     * @param int $offset
     * @param Arlima_List $list
     */
    private static function saveArticle( $article, $sort, $parent=-1, $list, $offset) {
        /* @var wpdb $wpdb */
        global $wpdb;

        if( ! isset( $article['status'] ) )
            $article['status'] = 1;

        $options = !empty( $article[ 'options' ] ) ? serialize( self::cleanArticleOptions($article[ 'options' ]) ) : '';
        $image_options = serialize( isset( $article['image_options']) && is_array( $article['image_options'] ) ? $article[ 'image_options' ] : array() );

        $sql = $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "arlima_articlelist_article (ala_created, ala_publish_date, ala_alv_id, ala_post_id, ala_title, ala_text, ala_sort, ala_status, ala_title_fontsize, ala_url, ala_options, ala_image, ala_image_options, ala_parent) VALUES (%d, %d, %d, %d, %s, %s, %d, %d, %d, %s, %s, %s, %s, %d)",
            empty($article['created']) ? time():(int)$article['created'],
            empty($article['publish_date']) ? time():(int)$article['publish_date'],
            (int)$list->version[ 'id' ],
            (int)$article[ 'post_id' ],
            stripslashes( $article[ 'title' ] ),
            stripslashes( $article[ 'text' ] ),
            (int)$sort,
            (int)$article[ 'status' ],
            (int)$article[ 'title_fontsize' ],
            $article[ 'url' ],
            $options,
            isset($article[ 'image' ]) ? $article[ 'image' ]:'',
            $image_options,
            (int)$parent
        );
        $wpdb->query($sql);

        if( !empty( $article[ 'children' ] ) && is_array( $article[ 'children' ]) ) {
            foreach( $article[ 'children' ] as $sort => $child ) {
                self::saveArticle( $child, $sort, $offset, $list, false );
            }
        }
    }

    /**
     * Removes redundant information from options array
     * @param array $options
     * @return array
     */
    private static function cleanArticleOptions(array $options) {
        if( empty($options['streamer']) ) {
            unset($options['streamer_type']);
            unset($options['streamer_content']);
            unset($options['streamer_color']);
            unset($options['streamer_image']);
        }

        if( empty($options['sticky']) ) {
            unset($options['sticky_pos']);
            unset($options['sticky_interval']);
        }

        return $options;
    }

    /**
     * Get an instance of an existing article list.
     * @static
     * @see Arlima_ListFactory::loadList()
     * @param int|string $slug_or_id
     * @param string|number $version[optional=''] - Empty string to get latest published version excluding future posts, 'preview' to get latest saved preview, including future post. Numeric value to get a specific version, including future posts.
     * @param bool $load_articles[optional=true]
     * @return Arlima_List
     */
    public static function load($slug_or_id, $version='', $load_articles = true) {
        $list = new Arlima_List();
        self::addData($list, $slug_or_id, $version);
        if($list->exists && $load_articles)
            self::addArticles($list, (bool)$version);

        return $list;
    }

    /**
     * Same as calling load() except that this function never uses cache, always includes future posts and always includes articles
     * @static
     * @param int|string $slug_or_id
     * @param number|bool $version[optional=false] - Omit this variable to get the latest version of the list
     * @throws Exception
     * @return Arlima_List
     */
    public static function loadList2($slug_or_id, $version=false) {
        if($version !== false && !is_numeric($version))
            throw new Exception('Only number of boolan false allowed as argument $version for Arlima_ListFactory::loadList(), use Arlima_ListFactory::load if wanting to load a preview version of a list');

        $list = new Arlima_List();
        self::addData($list, $slug_or_id, $version, false);
        if($list->exists && $list->version['id']) {
            $list->articles = self::queryArticles($list->version['id'], false);
        }

        return $list;
    }

    /**
     * @static
     * @param Arlima_List $list
     */
    public static function delete($list) {
        /* @var wpdb $wpdb */
        global $wpdb;

        // remove db info
        $version_data = $wpdb->get_results( $wpdb->prepare('SELECT alv_id FROM '.$wpdb->prefix.'arlima_articlelist_version WHERE alv_al_id=%d', $list->id) );
        if( !empty($version_data) ) {
            $versions = '';
            foreach($version_data as $data) {
                $versions .= $data->alv_id.',';
            }
            $versions = rtrim($versions, ',');
            $wpdb->query( $wpdb->prepare('DELETE FROM '.$wpdb->prefix.'arlima_articlelist_article WHERE ala_alv_id in ('.$versions.')') );
        }
        $wpdb->query( $wpdb->prepare('DELETE FROM '.$wpdb->prefix.'arlima_articlelist WHERE al_id=%d', $list->id ));
        $wpdb->query( $wpdb->prepare('DELETE FROM '.$wpdb->prefix.'arlima_articlelist_version WHERE alv_al_id=%d', $list->id) );

        // remove cache
        self::removeListCache($list);

        // update list props
        $list->exists = false;
        $list->id = 0;
    }

    /**
     * This function is only public for backward compatibility reasons. It's at the moment
     * also used when loading an Arlima_List by instantiating the class Arlima_List, not using
     * Arlima_ListFactory::load()
     *
     * @static
     * @param Arlima_List $list
     * @param $id
     * @param string $version[optional='']
     * @param $use_cache
     */
    public static function addData($list, $id, $version = '', $use_cache=true ) {
        /* @var wpdb $wpdb */
        global $wpdb;

        $is_numeric_id = is_numeric ( $id );
        $data = false;
        $cache_instance = false;

        // Load latest from cache
        if( !$version && $use_cache ) {
            $cache_instance = Arlima_CacheManager::loadInstance();
            $cache_key = ($is_numeric_id ? 'arlima_list_id_':'arlima_list_') . $id;
            $data = $cache_instance->get($cache_key);
        }

        // not in cache, or not supposed to be cached
        if( !$data ) {
            if( !$is_numeric_id ) {

                $sql = $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "arlima_articlelist WHERE al_slug = %s",
                    (string)$id
                );
            }
            else{
                $sql = $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "arlima_articlelist WHERE al_id = %d",
                    (int)$id
                );
            }

            $data = $wpdb->get_row( $sql );
            $data = self::removePrefix($data, 'al_');
            if( $cache_instance && !empty($data) ) {
                $cache_instance->set('arlima_list_'.$data['slug'], $data);
                $cache_instance->set('arlima_list_id_'.$data['id'], $data);
            }
        }

        if( $data ) {
            $list->id = $data['id'];
            $list->created = $data['created'];
            $list->title = $data['title'];
            $list->slug = $data['slug'];
            $list->status = $data['status'];
            $list->maxlength = $data['maxlength'];
            $list->options = unserialize( $data['options'] );
            $list->exists = true;
           self::addVersion($list, $version);
        }
    }

    /**
     * todo: refactor this somehow, seems hard to read. One thing causing this head scratching is probably that the object Arlima_List has one property called "version" and one property called "versions" ... is that necessary? and why do we confuse the status "PREVIEW" with version?? its two separate things, even though the code tries to interpret it the same some how...
     * @static
     * @param Arlima_List $list
     * @param string $version
     */
    private static function addVersion( $list, $version ) {
        /* @var wpdb $wpdb */
        global $wpdb;

        $version_list = false;
        $cache_instance = false;
        $version_added = false;
        $fields = 'alv_id, alv_created, alv_status, alv_user_id';

        if( $version == 'preview' ) {
            $sql = $wpdb->prepare( "SELECT " . $fields . " FROM " . $wpdb->prefix . "arlima_articlelist_version WHERE alv_al_id = %d AND alv_status = %d ORDER BY alv_id DESC LIMIT 1",
                (int)$list->id, Arlima_List::STATUS_PREVIEW
            );

            $version_data = $wpdb->get_row($sql);
            if(is_object($version_data) && is_numeric($version_data->alv_id)) {
                $list->version = self::removePrefix($version_data, 'alv_');
            }
            $version_added = true;
            $list->preview = true;
            $list->status = Arlima_List::STATUS_PREVIEW;
        }

        elseif( is_numeric( $version ) ) {
            // get version from numeric id
            $sql = $wpdb->prepare( "SELECT " . $fields . " FROM " . $wpdb->prefix . "arlima_articlelist_version WHERE alv_id = %d",
                (int)$version
            );
            $vesion_data = $wpdb->get_row($sql);
            if(is_numeric($vesion_data->alv_id)) {
                $list->version = self::removePrefix($vesion_data, 'alv_');
                $list->status = Arlima_List::STATUS_PUBLISHED;
                $version_added = true;
            }
        }

        if(!$version) {
            $cache_instance = Arlima_CacheManager::loadInstance();
            $version_list = $cache_instance->get('arlima_version_data_id_'.$list->id);
            $version = $cache_instance->get('arlima_version_id_'.$list->id);
            if($version) {
                $version_added = true;
                $list->version = $version;
                $list->status = Arlima_List::STATUS_PUBLISHED;
            }
        }

        if( !$version_added ) {

            // get latest version
            $sql = $wpdb->prepare ("SELECT " . $fields . " FROM " . $wpdb->prefix . "arlima_articlelist_version WHERE alv_al_id = %d AND alv_status = 1 ORDER BY alv_id DESC LIMIT 1",
                (int)$list->id
            );

            $version_data = $wpdb->get_row($sql);
            if(is_object($version_data) && is_numeric($version_data->alv_id)) {
                $list->version = self::removePrefix($version_data, 'alv_');
                $list->status = Arlima_List::STATUS_PUBLISHED;
            }else{
                $list->version = array('id' => 0);
            }

            if($cache_instance) {
                $cache_instance->set('arlima_version_id_'.$list->id, $list->version);
            }
        }

        if(!$version_list) {
            $sql = $wpdb->prepare ("SELECT alv_id FROM " . $wpdb->prefix . "arlima_articlelist_version WHERE alv_al_id = %d AND alv_status = 1 ORDER BY alv_id DESC LIMIT 10",
                (int)$list->id
            );
            $version_list = $wpdb->get_col($sql);
            if($cache_instance) {
                $cache_instance->set('arlima_version_data_id_'.$list->id, $version_list);
            }
        }

        $list->versions = $version_list;
    }

    /**
     * Only public for backward compatibility reasons, see Arlima_List::__construct()
     *
     * @param Arlima_List $list
     * @param string $dont_cache - Sorry for variable name with negative meaning, backward compatibility reason...
     */
    public static function addArticles($list, $dont_cache) {

        $cache_instance = false;
        if(!$dont_cache) {
            $cache_instance = Arlima_CacheManager::loadInstance();
            $articles = $cache_instance->get('arlima_articles_id_'.$list->id);
            if($articles) {
                $list->articles = $articles;
                return;
            }
        }

        if( !empty( $list->version ) ) {

            $exclude_future_posts = true;
            if($dont_cache) {
                $exclude_future_posts = false;
            }

            $list->articles = self::queryArticles($list->version['id'], $exclude_future_posts);

            if($cache_instance) {
                $cache_instance->set('arlima_articles_id_'.$list->id, $list->articles);
            }
        }
    }


    /**
     * @static
     * @param $version
     * @param bool $exclude_future_posts
     * @return array
     */
    private static function queryArticles($version, $exclude_future_posts) {
        /* @var wpdb $wpdb */
        global $wpdb;

        $sql = "SELECT ala_id, ala_created, ala_publish_date, ala_post_id, ala_title, ala_text, ala_status,
                        ala_title_fontsize, ala_url, ala_options, ala_image, ala_image_options, ala_parent, ala_sort
                FROM " . $wpdb->prefix . "arlima_articlelist_article %s ORDER BY ala_parent, ala_sort";

        $where = 'WHERE';
        $has_version = $version && is_numeric($version);
        if($has_version)
            $where .= ' ala_alv_id='.intval($version);

        if($where == 'WHERE')
            $where = '';

        $articles = array();
        foreach($wpdb->get_results( sprintf($sql, $where) ) as $row) {

            if( unserialize( $row->ala_options ) !== false ) {
                $row->ala_options = unserialize( $row->ala_options );
            } else {
                $row->ala_options = array();
            }

            if( unserialize( $row->ala_image_options ) !== false ) {
                $row->ala_image_options = unserialize( $row->ala_image_options );
            } else {
                $row->ala_image_options = array();
            }

            $row->children = array();

            if( $row->ala_parent == -1 ) {
                $articles[] = self::removePrefix( $row, 'ala_' );
            } else {
			
                $articles[ $row->ala_parent ]['children'][] = self::removePrefix( $row, 'ala_' );
            }
        }
		if( $exclude_future_posts ) {
			foreach( $articles as $i => $article ) {
				if( $article['publish_date'] && ( $article['publish_date'] > time() ) ) {
					unset( $articles[$i] );
				}
			}
			//reset the numerical order of keys that might have been mangled when removing future articles
			$articles = array_values( $articles );
		}
        return $articles;
    }

    /**
     * Remove prefix from array keys, will also turn stdClass objects to arrays unless
     * $preserve_std_objects is set to true
     * @static
     * @param array $array
     * @param string $prefix
     * @param bool $preserve_std_objects[optional=false]
     * @return array
     */
    protected static function removePrefix($array = array(), $prefix, $preserve_std_objects=false) {
        $convert_to_std = $preserve_std_objects && $array instanceof stdClass;
        $new_array = array();
        $prefix_len = strlen($prefix);
        if($array) {
            foreach ( $array as $key => $value ) {
                $newkey = $key;
                if(substr($key, 0, $prefix_len) == $prefix)
                    $newkey = substr($key, $prefix_len);
                if(is_array($value) || $value instanceof stdClass)
                    $value = self::removePrefix($value, $prefix, $preserve_std_objects);
                $new_array[$newkey] = $value;
            }
        }
        return $convert_to_std ? (object)$new_array:$new_array;
    }



    /**
     * @param Arlima_List $list
     * @return array
     */
    public static function loadRelatedPages($list) {
        return get_pages(array(
                'meta_key' => '_arlima_list',
                'meta_value' => $list->id,
                'hierarchical' => 0
            ));
    }

    /**
     * @static
     * @param $slug
     * @return int|bool
     */
    public static function getListId2($slug) {
        foreach(self::loadListSlugs() as $data) {
            if($data->slug == $slug)
                return $data->id;
        }

        return false;
    }

    /**
     * Installer for this plugin. The install procedures includes creating database
     * tables, copying example template to current theme and creating an
     * example front page.
     * @static
     */
    public function install() {

        $table_name = $this->wpdb->prefix . "arlima_articlelist";
        if($this->wpdb->get_var("show tables like '$table_name'") != $table_name) {
            self::createDatabaseTables($this->wpdb);
            add_option("arlima_db_version", self::DB_VERSION);
        }

        $installed_ver = get_option( "arlima_db_version" );

        if( $installed_ver != self::DB_VERSION ) {
            self::createDatabaseTables($this->wpdb);
            update_option( "arlima_db_version", self::DB_VERSION );
        }
    }

    /**
     * Executes SQL queries that creates or updates the database tables
     * needed by this plugin
     *
     * @static
     * @param wpdb $wpdb
     */
    private static function createDatabaseTables($wpdb) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . "arlima_articlelist";

        $sql = "CREATE TABLE " . $table_name . " (
        al_id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        al_created bigint(11) DEFAULT '0' NOT NULL,
        al_title tinytext NOT NULL,
        al_slug varchar(50),
        al_options text,
        al_maxlength mediumint(9) DEFAULT '100' NOT NULL,
        UNIQUE KEY id (al_id),
        KEY created (al_created),
        KEY slug (al_slug)
        );";

        dbDelta($sql);

        $table_name = $wpdb->prefix . "arlima_articlelist_version";

        $sql = "CREATE TABLE " . $table_name . " (
        alv_id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        alv_created bigint(11) DEFAULT '0' NOT NULL,
        alv_al_id mediumint(9) NOT NULL,
        alv_status tinyint(1) DEFAULT '1' NOT NULL,
        alv_user_id mediumint(9) NOT NULL,
        UNIQUE KEY id (alv_id),
        KEY created (alv_created),
        KEY alid (alv_al_id),
        KEY alid_created (alv_al_id, alv_created)
        );";

        dbDelta($sql);

        $table_name = $wpdb->prefix . "arlima_articlelist_article";

        $sql = "CREATE TABLE " . $table_name . " (
        ala_id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        ala_created bigint(11) DEFAULT '0' NOT NULL,
        ala_publish_date bigint(11) DEFAULT '0' NOT NULL,
        ala_alv_id mediumint(9) NOT NULL,
        ala_post_id mediumint(9) DEFAULT '-1' NOT NULL,
        ala_title varchar(255),
        ala_text text,
        ala_sort mediumint(9) DEFAULT '100' NOT NULL,
        ala_title_fontsize tinyint(1) DEFAULT '24' NOT NULL,
        ala_url varchar(255),
        ala_options text,
        ala_image varchar(255),
        ala_image_options text,
        ala_parent mediumint(9) DEFAULT '-1' NOT NULL,
        UNIQUE KEY id (ala_id),
        KEY created (ala_created),
        KEY alvid (ala_alv_id),
        KEY alvid_created (ala_alv_id, ala_created),
        KEY alvid_sort (ala_alv_id, ala_sort),
        KEY alvid_sort_created (ala_alv_id, ala_sort, ala_created),
        KEY postid (ala_post_id),
        KEY postpublishdate (ala_publish_date)
        );";

        dbDelta($sql);
    }

    public static function databaseUpdates($version) {
        /* @var wpdb $wpdb */
        global $wpdb;
        if($version < 2.2) {
            $wpdb->query('ALTER TABLE '.$wpdb->prefix.'arlima_articlelist_article ADD ala_publish_date bigint(11) NOT NULL DEFAULT \'0\'');
            $wpdb->query('ALTER TABLE '.$wpdb->prefix.'arlima_articlelist_article ADD INDEX `postpublishdate` (ala_publish_date)');
        }
        elseif($version < 2.5) {
            $wpdb->query('ALTER TABLE '.$wpdb->prefix.'arlima_articlelist_article DROP ala_status');
            $wpdb->query('ALTER TABLE '.$wpdb->prefix.'arlima_articlelist DROP al_status');
        }
    }

    /**
     * Updates publish date for all arlima articles related to given post and clears the cache
     * of the lists where they appear
     * @static
     * @param stdClass $post
     */
    public static function updateArlimaArticleData($post) {
        if($post && $post->post_type == 'post') {
            /* @var wpdb $wpdb */
            global $wpdb;

            $date = strtotime($post->post_date_gmt);
            $prep_statement = $wpdb->prepare('UPDATE '.$wpdb->prefix.'arlima_articlelist_article SET ala_publish_date=%d WHERE ala_post_id=%d AND ala_publish_date != %d', $date, (int)$post->ID, $date);
            $wpdb->query($prep_statement);

            // Clear list cache
            if($wpdb->rows_affected > 0) {
                 /* Get id of lists that has this post, could probably be done in a better way... */
                $sql = 'SELECT DISTINCT(alv_al_id)
                        FROM '.$wpdb->prefix.'arlima_articlelist_version
                        WHERE alv_id IN (
                                SELECT DISTINCT(ala_alv_id)
                                FROM '.$wpdb->prefix.'arlima_articlelist_article
                                WHERE ala_post_id=%d
                            )';

                $ids = $wpdb->get_results( $wpdb->prepare($sql, (int)$post->ID) );
                $cache = Arlima_CacheManager::loadInstance();
                foreach($ids as $id) {
                    $cache_id = 'arlima_articles_id_'.$id->alv_al_id;
                    $found = $cache->delete($cache_id);
                }
            }
        }
    }

    /**
     * Removes the database tables created when plugin was installed
     * @static
     */
    public function uninstall() {
        $this->wpdb->query('DROP TABLE IF EXISTS '.$this->wpdb->prefix.'arlima_articlelist');
        $this->wpdb->query('DROP TABLE IF EXISTS '.$this->wpdb->prefix.'arlima_articlelist_version');
        $this->wpdb->query('DROP TABLE IF EXISTS '.$this->wpdb->prefix.'arlima_articlelist_article');
    }

    /**
     * The article data is in fact created with javascript in front-end so you can't
     * see this function as the sole creator of article objects. For that reason it might be
     * good to take look at this function once in a while, making sure it generates a similar object
     * as generated with javascript in front-end.
     *
     * @static
     * @param array $override[optional=array()]
     * @return array
     */
    public static function createArticleDataArray($override=array()) {
        $options = array(
            'pre_title' => '',
            'streamer_color' => '',
            'streamer_content' => '',
            'streamer_image' => '',
            'streamer_type' => 'extra',
            'hiderelated' => false,
            'template' => '',
            'format' => ''
        );

        $data = array(
                'children' => array(),
                'id' => 0,
                'image' => '',
                'image_options' => array(),
                'options' => $options,
                'post_id' => 0,
                'status' => 1,
                'text' => '',
                'title' => 'Unknown',
                'title_fontsize' => 24,
                'url' => '',
                'created' => 0,
                'publish_date' => 0
            );

        foreach($override as $key => $val) {
            if($key == 'children') {
                if(!is_array($val))
                    $val = array();
                foreach($val as $sub_art_key => $sub_art_val)
                    $val[$sub_art_key] = self::createArticleDataArray($sub_art_val);
            }
            elseif($key == 'options') {
                if(is_array($val)) {
                    foreach($val as $opt_key => $opt_val) {
                        $options[$opt_key] = $opt_val;
                    }
                }
                $val = $options;
            }

            $data[$key] = $val;
        }

        return $data;
    }

    private static $formats = array();

    /**
     * @see arlima_register_format()
     * @param $class
     * @param $label
     * @param array $tmpls
     */
    public static function addFormat($class, $label, $tmpls=array()) {
        $id = self::generateFormatId($class, $tmpls);
        self::$formats[$id] = array('class' => $class, 'label' => $label, 'templates' => $tmpls);
    }

    public static function getFormats() {
        return self::$formats;
    }

    public static function removeFormat($class, $tmpls=array()) {
        $id = self::generateFormatId($class, $tmpls);
        if( isset(self::$formats[$id]) ) {
            unset(self::$formats[$id]);
        }
    }

    private static function generateFormatId($class, $tmpls) {
        return join('-',$tmpls).':'.$class;
    }
}