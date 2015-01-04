<?php

/**
 * Class with all the know-how about creation of article lists and how the data is stored in the database.
 * All direct use of wpdb in the Arlima plugin should be placed in this class, at least as long as
 * the database communication is about getting data related to article lists.
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_ListFactory {

    const DB_VERSION = '3.1';

    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * @var Arlima_CacheManager
     */
    private $cache;

    /**
     * @var string
     */
    private $dbTablePrefix;

    /**
     * @param wpdb $db
     * @param null $cache
     */
    public function __construct($db = null, $cache = null)
    {
        $this->wpdb = $db === null ? $GLOBALS['wpdb'] : $db;
        $this->cache = $cache === null ? Arlima_CacheManager::loadInstance() : $cache;
        $this->dbTablePrefix = $this->wpdb->prefix .(defined('ARLIMA_DB_PREFIX') ? ARLIMA_DB_PREFIX:'arlima_');
    }

    /**
     * @param object|mixed $cache_instance
     */
    public function setCacheManager( $cache_instance )
    {
        $this->cache = $cache_instance;
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
    public function createList($title, $slug, $options=array(), $max_length=50)
    {
        // Create list object
        $list = new Arlima_List(true);
        $list->setCreated(Arlima_Utils::timeStamp());
        $list->setMaxlength($max_length);
        $list->setSlug($slug);
        $list->setTitle($title);
        $list->addOptions($options);

        // Insert list data in DB
        $sql = 'INSERT INTO ' . $this->dbTable() . '
                (al_created, al_title, al_slug, al_maxlength, al_options)
                VALUES (%d, %s, %s, %d, %s)';

        $this->executeSQLQuery('query', $this->wpdb->prepare($sql, array(
                        $list->getCreated(),
                        $title,
                        $slug,
                        $max_length,
                        serialize( $list->getOptions() )
                    )));

        // Add list id
        $list->setId($this->wpdb->insert_id);

        // Remove slug cache
        $cache = Arlima_CacheManager::loadInstance();
        $cache->delete('arlima_list_slugs');

        return $list;
    }

    /**
     * Will update name, slug and options of given list
     * @param Arlima_List $list
     * @throws Exception
     */
    public function updateListProperties($list)
    {
        $update_data = array(
            $list->getTitle(),
            $list->getSlug(),
            $list->getMaxlength(),
            serialize( self::sanitizeListOptions($list->getOptions()) ),
            (int)$list->getId()
        );

        $sql = 'UPDATE ' . $this->dbTable() . '
                    SET al_title = %s, al_slug = %s, al_maxlength=%d, al_options = %s
                    WHERE al_id = %d ';

        $this->executeSQLQuery('query', $this->wpdb->prepare($sql, $update_data));

        // remove cache
        $this->cache->delete('arlima_list_props_'.$list->getId());
        $this->cache->delete('arlima_list_slugs');
    }

    /**
     * @param Arlima_List $list
     */
    public function deleteList($list)
    {
        // Get versions
        $version_data = $this->executeSQLQuery('get_results',
            'SELECT alv_id FROM '.$this->dbTable('_version').' WHERE alv_al_id='.intval($list->getId()));

        // Remove articles
        if( !empty($version_data) ) {
            $versions = array();
            foreach($version_data as $data) {
                $versions[] = $data->alv_id;
            }
            $this->executeSQLQuery('query', sprintf(
                    "DELETE FROM ".$this->dbTable('_article')." WHERE ala_alv_id in (%s)",
                    implode(',', $versions)
                ));
        }

        // Remove list properties
        $this->executeSQLQuery('query', 'DELETE FROM '.$this->dbTable().' WHERE al_id='.$list->getId());

        // Remove versions
        $this->executeSQLQuery('query', 'DELETE FROM '.$this->dbTable('_version').' WHERE alv_al_id='.$list->getId() );

        // remove cache
        $this->cache->delete('arlima_list_props_'.$list->getId());
        $this->cache->delete('arlima_list_articles_data_'.$list->getId());
        $this->cache->delete('arlima_list_slugs');
    }

    /**
     * Deletes a version of a list
     * @param int $version_id
     * @param int $list_id
     */
    public function deleteListVersion($version_id) {

        $list = $this->loadListByVersionId($version_id);

        if($list && $list->exists()) {

            // Delete arlima article relations for version
            $this->executeSQLQuery('query', sprintf(
                "DELETE FROM ".$this->dbTable('_article')." WHERE ala_alv_id in (%s)",
                $version_id)
            );

            // Delete version from version table
            $this->executeSQLQuery('query', sprintf(
                "DELETE FROM ".$this->dbTable('_version')." WHERE alv_id = %d",
                intval($version_id)
            ));

            // Remove cache
            $this->cache->delete('arlima_list_articles_data_'.$list->getId());
        }

    }

    /**
     * @param int $id
     * @param array $data
     * @throws Exception
     */
    public function updateArticle($id, $data)
    {
        $sql = $this->wpdb->prepare('SELECT * FROM '.$this->dbTable('_article').' WHERE ala_id=%d', $id);
        $cols = $this->executeSQLQuery('get_row', $sql, 'ala_');

        if( !empty($cols) ) {

            $sql = 'UPDATE '.$this->dbTable('_article').' SET ';
            foreach($data as $col => $val) {
                if( !isset($cols[$col]) )
                    throw new Exception('Trying to update article using unknown column "'.$col.'"');
                if($col == 'options') {
                    $val = array_merge(unserialize($cols['options']), $val);
                    $val = self::sanitizeListOptions($val);
                }
                if($col == 'options' || $col == 'image')
                    $val = serialize( $val );

                $sql .= " ala_$col = '".esc_sql(stripslashes($val))."', ";
            }

            $sql = rtrim($sql, ', ') . ' WHERE ala_id = '.intval($id);

            $this->executeSQLQuery('query', $sql);

            // Update cache
            $list = $this->loadListByVersionId($cols['alv_id']);
            if($list !== null) {
                $this->doSaveListAction($list);
                $this->cache->delete('arlima_list_articles_data_'.$list->getId());
            }
        }
    }

    /**
     * @param string $type
     * @return string
     */
    public function dbTable($type='')
    {
        return $this->dbTablePrefix.'articlelist'.$type;
    }

    /**
     * @param Arlima_List $list
     */
    private function doSaveListAction($list)
    {
        do_action('arlima_save_list', $list);
    }

    /**
     * @param $version
     * @return Arlima_List|null
     */
    private function loadListByVersionId($version)
    {
        $id = $this->executeSQLQuery('get_var', 'SELECT alv_al_id FROM '.$this->dbTable('_version').' WHERE alv_id='.intval($version));
        return $id ? $this->loadList($id, false, true) : null;
    }

    /**
     * @param Arlima_List $list
     * @param $articles
     * @param $user_id
     * @param int $schedule_time
     * @param bool $preview
     * @return int
     * @throws Exception
     */
    public function saveNewListVersion($list, $articles, $user_id, $schedule_time=0, $preview = false)
    {
        if(!$list->exists())
            throw new Exception('You can not create a new version of a list that does not exist');
        if($list->isImported())
            throw new Exception('You can not save a new version of a list that is imported');

        $this->removeOldVersions($list);

        $status = $preview ? Arlima_List::STATUS_PREVIEW : Arlima_List::STATUS_PUBLISHED;
        $status = $schedule_time ? Arlima_List::STATUS_SCHEDULED : $status;

        // Create the new version
        $sql = $this->wpdb->prepare(
            "INSERT INTO " . $this->dbTable('_version') . "
                (alv_created, alv_al_id, alv_status, alv_user_id, alv_scheduled)
                VALUES (%d, %s, %d, %d, %d)",
            time(),
            $list->getId(),
            $status,
            $user_id,
            $schedule_time
        );

        $this->executeSQLQuery('query', $sql);
        $version_id = $this->wpdb->insert_id;

        // If scheduled, register event to publish future list
        if($schedule_time) {
            wp_schedule_single_event( $schedule_time, 'arlima_publish_scheduled_list', array( $list->getId(), $version_id ) );
        }

        // Update possibly changed published date
        foreach( $articles as $i => $article ) {
            if( !empty($article['post']) && $connected_post = get_post($article['post']) ) {
                $articles[$i]['published'] = Arlima_Utils::getPostTimeStamp($connected_post);
            }
        }

        $count = 0;
        foreach( $articles as $sort => $article ) {
            $this->saveArticle($version_id, $article, $sort, -1, $count);
            $count++;
            if( $count >= $list->getMaxlength() )
                break;
        }

        // Reload list
        $list = $this->loadList($list->getId(), $version_id, true);

        if( !$preview && !$schedule_time ) {
            $this->cache->delete('arlima_list_articles_data_'.$list->getId());
            $this->doSaveListAction($list);
        }

        return $version_id;
    }

    /**
     * @param Arlima_List $list
     * @param array $articles
     * @param int $version_id
     */
    public function updateListVersion($list, $articles, $version_id)
    {
        if(!$list->exists())
            throw new Exception('You can not save a version of a list that does not exist');
        if($list->isImported())
            throw new Exception('You can not save a version of a list that is imported');

        $list = $this->saveArticlesForVersion($list, $articles, $version_id);

        // Reload list
        $list = $this->loadList($list->getId(), $version_id, true);
    }

    /**
     * @param int $version_id
     * @param array $article
     * @param mixed $sort,
     * @param int $parent[optional=-1]
     * @param int $offset
     */
    private function saveArticle($version_id, $article, $sort, $parent=-1, $offset)
    {
        foreach($article as $key => $val) {
            if( is_array($val) ) {
                foreach($article[$key] as $sub_key => $sub_val) {
                    $article[$key][$sub_key] = str_replace('\\', '', $sub_val);
                }
            } else {
                $article[$key] = str_replace('\\', '', $val);
            }
        }

        if( !isset($article['options']) || !is_array($article['options']) )
            $article['options'] = array();
        if( !isset($article['image']) || !is_array($article['image']) )
            $article['image'] = array();

        $options = serialize( self::cleanArticleOptions($article['options']) );
        $image_options = serialize( self::cleanImageData($article['image']) );

        $sql = $this->wpdb->prepare(
            "INSERT INTO " . $this->dbTable('_article') . "
                    (ala_created, ala_published, ala_alv_id, ala_post, ala_title,
                    ala_content, ala_sort, ala_size, ala_options,
                    ala_image, ala_parent)
                    VALUES (%d, %d, %d, %d, %s, %s, %d, %d, %s, %s, %d)",
            empty($article['created']) ? Arlima_Utils::timeStamp():(int)$article['created'],
            empty($article['published']) ? Arlima_Utils::timeStamp():(int)$article['published'],
            $version_id,
            isset($article['post']) ? (int)$article['post']:0,
            $article['title'],
            $article['content'],
            (int)$sort,
            (int)$article['size'],
            $options,
            $image_options,
            (int)$parent
        );

        $this->executeSQLQuery('query', $sql);

        if( !empty($article['children']) && is_array($article['children']) ) {
            foreach( $article['children'] as $sort => $child ) {
                $this->saveArticle($version_id, $child, $sort, $offset, false );
            }
        }
    }

    /**
     * @param array $img
     * @return array
     */
    protected static function cleanImageData($img)
    {
        // legacy fixes
        if( !empty($img['attach_id']) ) {
            $img['attachment'] = $img['attach_id'];
            unset($img['attach_id']);
        }
        if( isset($img['alignment']) && $img['alignment'] == 'aligncenter' ) {
            $img['alignment'] = 'alignleft';
        }
        return $img;
    }

    /**
     * Removes all preview versions created for this list and all old
     * published versions starting from $num_num_version_to_keep
     *
     * @param Arlima_List $list
     * @param int $num_versions_to_keep
     * @return false|null|string
     */
    public function removeOldVersions($list, $num_versions_to_keep=10)
    {
        $old_versions = array();
        if ( $list->getStatus() == Arlima_List::STATUS_PUBLISHED ) {

            //fetch all versions older than the last 10
            $sql = $this->wpdb->prepare(
                "SELECT alv_id FROM " . $this->dbTable('_version') . "
                        WHERE alv_al_id = %d AND alv_status = %d
                        ORDER BY alv_id DESC LIMIT %d, 10",
                $list->getId(),
                Arlima_List::STATUS_PUBLISHED,
                $num_versions_to_keep
            );

            $old_versions = $this->executeSQLQuery('get_col', $sql);
        }

        // fetch all old previews
        $sql = $this->wpdb->prepare(
            "SELECT alv_id FROM " . $this->dbTable('_version') . "
                    WHERE alv_al_id = %d AND alv_status = %d",
            $list->getId(),
            Arlima_List::STATUS_PREVIEW
        );

        $old_previews = $this->executeSQLQuery('get_col', $sql);
        $versions_to_remove = array_merge($old_versions, $old_previews);

        // We have versions to remove
        if ( !empty($versions_to_remove) ) {

            // Remove articles belonging to versions that will be removed
            $this->executeSQLQuery(
                'query',
                sprintf(
                    "DELETE FROM " . $this->dbTable('_article') . "
                        WHERE ala_alv_id IN (%s)",
                    implode(',', $versions_to_remove)
                )
            );

            // Delete the versions
            $this->executeSQLQuery(
                'query',
                sprintf(
                    "DELETE FROM " . $this->dbTable('_version') . "
                            WHERE alv_id IN (%s)",
                    implode(',', $versions_to_remove)
                )
            );
            return $sql;
        }
        return $sql;
    }


    /**
     * Future posts will always be included in the list if you're loading a specific version of
     * the list. Otherwise you can use the argument $include_future_posts to control if the list
     * should contain future posts as well. Setting $include_future_posts to true will how ever
     * disable the caching of the article data
     *
     * @param int|string $id Either list id, list slug or URL to external list or RSS-feed
     * @param mixed $version Omit this argument, or set it to false, if you want to load the latest published version of the list. This argument won't have any effect if you're loading an external list/feed
     * @param bool $include_future_posts Whether or not the list should include future posts. This argument won't have any effect if you're loading an external list/feed
     * @return Arlima_List
     */
    public function loadList($id, $version=false, $include_future_posts=false)
    {
        if( !is_numeric( $id ) && substr( $id, 0, 7 ) == 'http://' ) {
            // Import list
            $cache_key = 'external_list_'.$id;
            if( $list = wp_cache_get($cache_key, 'arlima') ) {
                return $list;
            } else {
                $import_manager = new Arlima_ImportManager(new Arlima_Plugin());
                $list = $import_manager->loadList( $id );
                wp_cache_set($cache_key, $list, 'arlima', 180);
                return $list;
            }
        }
        elseif( !is_numeric($id) ) {
            // Get id by slug
            $id = $this->getListId($id);
            if( !$id ) {
                // Not found, return empty list
                return new Arlima_List();
            }
        }

        $list = $this->queryList($id);
        if( !$list->exists() )
            return $list;

        // Get latest published version
        if( !$version ) {
            $article_data = false;

            if (!$include_future_posts) {
                $article_data = $this->cache->get('arlima_list_articles_data_'.$id);
            }


            if( !$article_data || $include_future_posts ) {

                $article_data = array();
                $version_data = $this->queryVersionData($id, false);
                $article_data['version'] = $version_data[0];
                $article_data['version_list'] = $version_data[1];
                $article_data['scheduled_version_list'] = $version_data[2];
                if( !empty($article_data['version']) ) {
                    $article_data['articles'] = $this->queryListArticles($article_data['version']['id'], $include_future_posts);
                }

                if( !$include_future_posts )
                    $this->cache->set('arlima_list_articles_data_'.$id, $article_data);
            }

            if( !empty($article_data['version']) ) {
                $list->setStatus( Arlima_List::STATUS_PUBLISHED );
                $list->setArticles($article_data['articles']);
                $list->setVersions( $article_data['version_list'] );
                $list->setScheduledVersions( $article_data['scheduled_version_list'] );
                $list->setVersion( $article_data['version'] );
            }
        }

        // Preview version or specific version (no cache)
        else {
            list($version_data, $version_list, $scheduled_version_list) = $this->queryVersionData($id, $version);
            if( !empty($version_data) ) {
                $list->setVersion($version_data);
                $list->setVersions($version_list);
                $list->setScheduledVersions($scheduled_version_list);
                $list->setArticles( $this->queryListArticles($version_data['id'], true) );
                if ($version_data && $version_data['status'] == Arlima_List::STATUS_SCHEDULED) {
                    $list->setStatus(Arlima_List::STATUS_SCHEDULED);
                } else {
                    $list->setStatus( $version === 'preview' ? Arlima_List::STATUS_PREVIEW : Arlima_List::STATUS_PUBLISHED);
                }
            }
        }

        return $list;
    }

    /**
     * Calls a method on $wpdb and throws Exception if mysql error occurs
     * @param string $method
     * @param string $sql
     * @param bool $remove_prefix
     * @param bool $preserve_obj
     * @throws Exception
     * @return mixed
     */
    private function executeSQLQuery($method, $sql, $remove_prefix = false, $preserve_obj=false)
    {
        if( $sql instanceof WP_Error) {
            throw new Exception($sql->get_error_message());
        }
        elseif( !$sql ) {
            throw new Exception('Empty SQL, last error from wpdb: '.$this->wpdb->last_error);
        }

        $obj = call_user_func(array($this->wpdb, $method), $sql);
        if( is_wp_error($obj) || $this->wpdb->last_error )
            throw new Exception($this->wpdb->last_error);

        return $remove_prefix !== false ? self::removePrefix($obj, $remove_prefix, $preserve_obj) : $obj;
    }

    /**
     * @param $list_id
     * @param $version
     * @param $get_scheduled
     * @return array
     */
    private function queryVersionData($list_id, $version)
    {
        $version_data_sql = "SELECT alv_id, alv_created, alv_scheduled, alv_status, alv_user_id FROM ".$this->dbTable('_version').' WHERE alv_al_id='.(int)$list_id;

        // version list
        if ($version === 'preview') {
            $version_list_sql = $version_data_sql.' AND alv_status = '.Arlima_List::STATUS_PREVIEW;
        } else {
            $version_list_sql = $version_data_sql.' AND alv_status = '.Arlima_List::STATUS_PUBLISHED;
        }

        $version_list_data = $this->executeSQLQuery('get_results', $version_list_sql.' ORDER BY alv_id DESC LIMIT 0,10', 'alv_');

        // scheduled list
        $scheduled_list_sql = $version_data_sql.' AND alv_status='.Arlima_List::STATUS_SCHEDULED;

        $scheduled = $this->executeSQLQuery('get_results', $scheduled_list_sql.' ORDER BY alv_scheduled ASC LIMIT 0,10', 'alv_');

        if (!$version || $version == 'preview') {
            $latest = $version_list_data ? $version_list_data[0] : null;
        }
        else {
            // FIXME previously list id (alv_al_id) was omitted, so that a version from another list
            // could be returned. Be aware of lists with conflicting slug names and loadList
            //  (i.e. alv_id is wrong for $version)
            $single_version_sql = $version_data_sql.' AND alv_id='.(int)$version;
            $latest = $this->executeSQLQuery('get_row', $single_version_sql, 'alv_');
        }

        $versions = array();
        foreach ($version_list_data as $row) {
            $user_data = get_userdata($row['user_id']);
            $row['saved_by'] = $user_data ? $user_data->display_name : __('Unknown', 'arlima');
            $versions[] = $row;
        }

        return array(
            $latest,
            $versions,
            array_reverse($scheduled),
        );

    }

    /**
     * @param int $id
     * @throws Exception
     * @return Arlima_List
     */
    private function queryList($id)
    {
        $list = $this->cache->get('arlima_list_props_'.$id);
        if( !$list ) {
            $sql = $this->wpdb->prepare(
                "SELECT * FROM " . $this->dbTable() . " WHERE al_id = %d",
                (int)$id
            );

            $list_data = $this->executeSQLQuery('get_row', $sql, 'al_');

            if ( empty($list_data) ) {
                $list = new Arlima_List(false);
            } else {
                $list = new Arlima_List(true, $id);
                $list->setCreated($list_data['created']);
                $list->setTitle($list_data['title']);
                $list->setSlug($list_data['slug']);
                $list->setMaxlength($list_data['maxlength']);
                $list->setOptions( self::sanitizeListOptions(unserialize($list_data['options'])) );
                $this->cache->set('arlima_list_props_'.$id, $list);
            }
        }

        return $list;
    }

    /**
     * Load latest preview version of article list with given id.
     * @param int $id
     * @return Arlima_List
     */
    public function loadLatestPreview($id)
    {
        return $this->loadList($id, 'preview');
    }

    /**
     * @param $version
     * @param bool $include_future_posts
     * @param bool $get_scheduled
     * @return array
     */
    private function queryListArticles($version, $include_future_posts)
    {
        $sql = "SELECT ala_id, ala_created, ala_published, ala_post, ala_title, ala_content,
                ala_size, ala_options, ala_image, ala_parent, ala_sort
                FROM " . $this->dbTable('_article') . " %s ORDER BY ala_parent, ala_sort";

        $where = '';
        if($version)
            $where .= ' WHERE ala_alv_id = '.intval($version);

        $articles = array();
        foreach($this->executeSQLQuery('get_results', sprintf($sql, $where) ) as $row) {

            if( !empty($row->ala_options) ) { // once upon a time this variable could be an empty string
                $row->ala_options = unserialize( $row->ala_options );
            } else {
                $row->ala_options = array();
            }

            if( !empty($row->ala_image) ) {
                $row->ala_image = unserialize( $row->ala_image );
            } else {
                $row->ala_image = array();
            }

            $row->children = array();
            $article = self::legacyFix(self::removePrefix($row, 'ala_'));

            if( $row->ala_parent == -1 ) {
                $articles[] = $article;
            } else {
                $articles[ $row->ala_parent ]['children'][] = $article;
            }
        }

        // Remove future posts
        if( !$include_future_posts ) {

            foreach( $articles as $i => $article ) {
                if( $article['published'] && ( $article['published'] > Arlima_Utils::timeStamp() ) ) {
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
     * @return array
     */
    public function loadListSlugs()
    {
        $data = $this->cache->get('arlima_list_slugs');
        if(!is_array($data)) {
            $sql = 'SELECT al_id, al_title, al_slug
                    FROM ' . $this->dbTable() . '
                    ORDER BY al_title ASC';

            $data = $this->executeSQLQuery('get_results', $sql, 'al_', true);
            $this->cache->set('arlima_list_slugs', $data);
        }

        return $data;
    }

    /**
     * @param $slug
     * @return int|bool
     */
    public function getListId($slug)
    {
        foreach($this->loadListSlugs() as $data) {
            if($data->slug == $slug)
                return $data->id;
        }

        return false;
    }

    /**
     * Loads an array with objects containing list id and options that have teasers that are linked to the post with $post_id
     * @todo rename to loadListsByPostID
     * @param  int $post_id
     * @return array
     */
    public function loadListsByArticleId($post_id) {
        $sql = sprintf("
                select distinct(al_id), al_options
                from %s al
                inner join (
                    select max(alv_id) as latestversion, alv_al_id
                    from %s
                    where alv_status = 1
                    group by alv_al_id
                ) alv on al.al_id = alv.alv_al_id
                INNER JOIN %s ala ON ala.ala_alv_id = alv.latestversion
                WHERE ala.ala_post = %d",
                $this->dbTable(),
                $this->dbTable('_version'),
                $this->dbTable('_article'),
                $post_id
                );
        return $this->executeSQLQuery('get_results', $sql);
    }

    /**
     * Get latest article teaser created that is related to given post
     * @param $post_id
     * @return array
     */
    public function getLatestArticle($post_id)
    {
        $sql = sprintf(
                "SELECT * FROM %s WHERE ala_post=%d ORDER BY ala_id DESC LIMIT 0,1",
                $this->dbTable('_article'),
                (int)$post_id
            );

        return $this->executeSQLQuery('get_results', $sql);
    }

    /* * * * * * * * * * * * * * * * * INSTALL / UNINSTALL  * * * * * * * * * * * * * * * * * */



    /**
     * Database installer for this plugin.
     * @static
     */
    public function install()
    {
        $version_suffix = (defined('ARLIMA_DB_PREFIX') ? ARLIMA_DB_PREFIX:'');
        $table_name = $this->dbTable();
        if($this->wpdb->get_var("show tables like '$table_name'") != $table_name) {
            $this->createDatabaseTables();
            add_option('arlima_db_version'.$version_suffix, self::DB_VERSION);
        }

        $installed_ver = get_option( 'arlima_db_version'.$version_suffix );

        if( $installed_ver != self::DB_VERSION ) {
            $this->databaseUpdates($installed_ver);
            update_option('arlima_db_version'.$version_suffix, self::DB_VERSION );
        }
    }

    /**
     * Executes SQL queries that creates or updates the database tables
     * needed by this plugin
     */
    private function createDatabaseTables()
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $this->dbTable();

        $sql = "CREATE TABLE " . $table_name . " (
        al_id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
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

        $table_name = $this->dbTable('_version');

        $sql = "CREATE TABLE " . $table_name . " (
        alv_id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        alv_created bigint(11) DEFAULT '0' NOT NULL,
        alv_scheduled bigint(11) DEFAULT '0' NOT NULL,
        alv_al_id bigint(11) NOT NULL,
        alv_status tinyint(1) DEFAULT '1' NOT NULL,
        alv_user_id bigint(11) NOT NULL,
        UNIQUE KEY id (alv_id),
        KEY created (alv_created),
        KEY alid (alv_al_id),
        KEY alid_created (alv_al_id, alv_created)
        );";

        dbDelta($sql);

        $table_name = $this->dbTable('_article');

        $sql = "CREATE TABLE " . $table_name . " (
        ala_id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        ala_created bigint(11) DEFAULT '0' NOT NULL,
        ala_published bigint(11) DEFAULT '0' NOT NULL,
        ala_alv_id bigint(11) NOT NULL,
        ala_post bigint(11) DEFAULT '-1' NOT NULL,
        ala_title varchar(255),
        ala_content text,
        ala_sort bigint(11) DEFAULT '100' NOT NULL,
        ala_size tinyint(2) DEFAULT '24' NOT NULL,
        ala_options text,
        ala_image text,
        ala_parent bigint(11) DEFAULT '-1' NOT NULL,
        UNIQUE KEY id (ala_id),
        KEY created (ala_created),
        KEY alvid (ala_alv_id),
        KEY alvid_created (ala_alv_id, ala_created),
        KEY alvid_sort (ala_alv_id, ala_sort),
        KEY alvid_sort_created (ala_alv_id, ala_sort, ala_created),
        KEY postid (ala_post),
        KEY postpublishdate (ala_published)
        );";

        dbDelta($sql);

    }

    public static function databaseUpdates($version)
    {
        /* @var wpdb $wpdb */
        global $wpdb;
        $wpdb->suppress_errors(true);
        $factory = new self($wpdb);
        $article_tbl_name = $factory->dbTable('_article');
        $version_tbl_name = $factory->dbTable('_version');

        if($version < 2.2) {
            $wpdb->query('ALTER TABLE '.$article_tbl_name.' ADD ala_publish_date bigint(11) NOT NULL DEFAULT \'0\'');
            $wpdb->query('ALTER TABLE '.$article_tbl_name.' ADD INDEX `postpublishdate` (ala_publish_date)');
        }
        elseif($version < 2.5) {
            $wpdb->query('ALTER TABLE '.$article_tbl_name.' DROP ala_status');
            $wpdb->query('ALTER TABLE '.$factory->dbTable().' DROP al_status');
        }
        elseif($version < 2.6) {
            $wpdb->query('ALTER TABLE '.$factory->dbTable().' al_id al_id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY');
            $wpdb->query('ALTER TABLE '.$factory->dbTable('_version').' alv_id alv_id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY');
            $wpdb->query('ALTER TABLE '.$factory->dbTable('_version').' alv_al_id alv_al_id bigint(11)');
            $wpdb->query('ALTER TABLE '.$article_tbl_name.' ala_id ala_id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY');
            $wpdb->query('ALTER TABLE '.$article_tbl_name.' ala_alv_id ala_alv_id bigint(11)');
        }
        elseif($version < 3.0) {

            $row = $wpdb->get_row('SELECT * FROM '.$article_tbl_name.' LIMIT 0,1');

            if( empty($row) || isset($row->ala_image_options) ) {
                $wpdb->query('ALTER TABLE '.$article_tbl_name.' CHANGE ala_image ala_image_depr varchar(250)');
                $wpdb->query('ALTER TABLE '.$article_tbl_name.' CHANGE ala_publish_date ala_published bigint(11)');
                $wpdb->query('ALTER TABLE '.$article_tbl_name.' CHANGE ala_post_id ala_post bigint(11)');
                $wpdb->query('ALTER TABLE '.$article_tbl_name.' CHANGE ala_title_fontsize ala_size tinyint(2)');
                $wpdb->query('ALTER TABLE '.$article_tbl_name.' CHANGE ala_image_options ala_image text');
                $wpdb->query('ALTER TABLE '.$article_tbl_name.' CHANGE ala_text ala_content text');
                wp_cache_flush();
            }
        }
        elseif($version < 3.1) {
            $wpdb->query('ALTER TABLE '.$version_tbl_name.' ADD alv_scheduled bigint(11) NOT NULL DEFAULT 0');
        }
    }

    /**
     * Removes the database tables created when plugin was installed
     * @static
     */
    public function uninstall()
    {
        $this->wpdb->query('DROP TABLE IF EXISTS '.$this->dbTable());
        $this->wpdb->query('DROP TABLE IF EXISTS '.$this->dbTable('_version'));
        $this->wpdb->query('DROP TABLE IF EXISTS '.$this->dbTable('_article'));
    }

    /**
     * Updates publish date for all arlima articles related to given post and clears the cache
     * of the lists where they appear
     * @param stdClass|WP_Post $post
     */
    public function updateArticlePublishDate($post)
    {
        if($post && $post->post_type == 'post') {

            /* @var wpdb $wpdb */
            global $wpdb;

            $date = Arlima_Utils::getPostTimeStamp($post);
            $prep_statement = $wpdb->prepare(
                'UPDATE '.$this->dbTable('_article').'
                SET ala_published=%d
                WHERE ala_post=%d AND ala_published != %d',
                $date,
                (int)$post->ID,
                $date
            );

            $wpdb->query($prep_statement);

            // Clear list cache
            if($wpdb->rows_affected > 0) {

                /* Get id of lists that has this post, could probably be done in a better way... */
                $sql = 'SELECT DISTINCT(alv_al_id)
                        FROM '.$this->dbTable('_version').'
                        WHERE alv_id IN (
                                SELECT DISTINCT(ala_alv_id)
                                FROM '.$this->dbTable('_article').'
                                WHERE ala_post=%d
                            )';

                $ids = $wpdb->get_results( $wpdb->prepare($sql, (int)$post->ID) );
                foreach($ids as $id) {
                    $cache_id = 'arlima_articles_id_'.$id->alv_al_id;
                    $this->cache->delete($cache_id);
                }
            }
        }
    }


    /* * * * * * * * * * * * * * * * * STATIC UTILITY FUNCTIONS  * * * * * * * * * * * * * * * * * */




    /**
     * Removes redundant information from options array
     * @param array $options
     * @return array
     */
    private static function cleanArticleOptions($options)
    {
        $has_streamer = !empty($options['streamerType']);
        if( $has_streamer && $options['streamerType'] == 'extra' && $options['streamerContent'] != 'Extra') {
            $options['streamerContent'] = 'Extra';
        }

        if( !$has_streamer || (empty($options['streamerContent']) && empty($options['streamerColor'])) ) {
            unset($options['streamerType']);
            unset($options['streamerContent']);
            unset($options['streamerColor']);
        }

        if( empty($options['scheduled']) ) {
            unset($options['scheduledInterval']);
        }


        $new_opts = array();
        foreach($options as $key=>$val) {
            if( $val !== '' ) {
                $new_opts[$key] = str_replace('\\', '', $val);
            }
        }

        return $new_opts;
    }

    /**
     * @param $art_data
     * @return array
     */
    private static function legacyFixForOptions($art_data)
    {
        $options = array(
            'hiderelated' => 'hideRelated',
            'pre_title' => 'preTitle',
            'overriding_url' => 'overridingURL',
            'streamer_content' => 'streamerContent',
            'streamer_color' => 'streamerColor',
            'streamer_type' => 'streamerType',
            'streamer_image' => 'streamerImage',
            'sticky_interval' => 'scheduledInterval',
            'sticky' => 'scheduled',
            'section_divider' => 'sectionDivider',
            'file_include' => 'fileInclude',
            'file_args' => 'fileArgs'
        );
        foreach($options as $old => $new) {
            if( isset($art_data['options'][$old]) ) {
                $art_data['options'][$new] = $art_data['options'][$old];
                unset($art_data['options'][$old]);
            }
        }
        return $art_data;
    }

    /**
     * Fixes old database data
     * @param array $art_data
     * @return mixed
     */
    protected static function legacyFix($art_data)
    {
        if (isset($art_data['title_fontsize'])) {
            $fix = array(
                'title_fontsize' => 'size',
                'post_id' => 'post',
                'publish_date' => 'published',
                'image_options' => 'image',
                'text' => 'content'
            );
            foreach ($fix as $old => $new) {
                if( isset($art_data[$old]) ) {
                    $art_data[$new] = $art_data[$old];
                    unset($art_data[$old]);
                }
            }

            # Fix options
            $art_data = self::legacyFixForOptions($art_data);

            # Fix streamer...
            if( !empty($art_data['options']['streamerType']) && $art_data['options']['streamerType'] == 'image' ) {
                $art_data['options']['streamerContent'] = $art_data['options']['streamerImage'];
                unset($art_data['options']['streamerImage']);
            }

            # Fix image
            if( !empty($art_data['image']) && $art_data['image']['alignment'] == 'aligncenter')
                $art_data['image']['alignment'] = 'alignleft';

        } elseif( isset($art_data['options']['pre_title']) || isset($art_data['options']['section_divider']) || isset($art_data['options']['file_include'])) {
            // Only fix options
            $art_data = self::legacyFixForOptions($art_data);
        }

        if( !empty($art_data['image']) && !empty($art_data['image']['attach_id']) ) {
            $art_data['image']['attachment'] = $art_data['image']['attach_id'];
            unset($art_data['image']['attach_id']);
            unset($art_data['image']['html']);
        }

        return $art_data;
    }

    /**
     * @static
     * @param Arlima_List $list
     * @return array
     */
    protected static function sanitizeList( &$list )
    {
        $list->setTitle( stripslashes($list->getTitle()) );
        $list->setSlug( sanitize_title(stripslashes($list->getSlug())) );
        $list->setOptions( array_map( 'stripslashes_deep', self::sanitizeListOptions( $list->getOptions() )) );

        if( !is_numeric($list->getMaxlength()) )
            $list->setMaxlength( 50 );
    }

    /**
     * @return array
     */
    private static function getDefaultListOptions()
    {
        static $opts = null;
        if( $opts === null ) {
            $empty_list = new Arlima_List();
            $opts = $empty_list->getOptions();
        }
        return $opts;
    }

    /**
     * @static
     * @param array $options
     * @return array
     */
    protected static function sanitizeListOptions($options)
    {
        $default_options = self::getDefaultListOptions();

        // Override default options
        foreach($default_options as $name => $val) {
            if( !isset($options[$name]) )
                $options[$name] = $val;
        }

        return $options;
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
    public static function createArticleDataArray($override=array())
    {
        $article_options = array(
            'preTitle' => '',
            'streamerColor' => '',
            'streamerContent' => '',
            'streamerImage' => '',
            'streamerType' => '',
            'template' => '',
            'format' => '',
            'sectionDivider' => '',
            'overridingURL' => '',
            'target' => ''
        );

        $data = array(
            'children' => array(),
            'id' => 0,
            'image' => array(),
            'options' => $article_options,
            'post' => 0,
            'status' => 1,
            'content' => '',
            'title' => 'Unknown',
            'size' => 24,
            'created' => 0,
            'published' => 0,
            'parent' => -1
        );

        // legacy fix...
        $override = self::legacyFix($override);

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
                        $article_options[$opt_key] = $opt_val;
                    }
                }
                $val = $article_options;
            }

            $data[$key] = $val;
        }

        //return new Arlima_Article($data);
        return $data;
    }

    /**
     * Takes a post and returns an Arlima article object
     * @param $post
     * @param string|null $text
     * @param array $override
     * @return array
     */
    public static function postToArlimaArticle($post, $text = null, $override=array())
    {
        if( $text === null ) {
            $text = !empty($post->post_excerpt) ? $post->post_excerpt : Arlima_Utils::getExcerptByPostId($post->ID);
            if ( stristr($text, '<p>') === false ) {
                $text = '<p>' . $text . '</p>';
            }
        }
        $art_data = array_merge(array(
            'post' => $post->ID,
            'title' => $post->post_title,
            'content' => $text,
            'size' => 24,
            'created' => Arlima_Utils::timeStamp(),
            'published' => Arlima_Utils::getPostTimeStamp($post)
        ), $override);

        if (self::hasPostThumbNailSupport() && has_post_thumbnail($post->ID)) {
            $attach_id = get_post_thumbnail_id($post->ID);
            $art_data['image'] = array(
                'url' => wp_get_attachment_url($attach_id),
                'attachment' => $attach_id,
                'size' => 'full',
                'alignment' => '',
                'connected' => true
            );
        }
        return Arlima_ListFactory::createArticleDataArray($art_data);
    }

    /**
     * @var null
     */
    protected static $has_post_thumb_func = null;

    /**
     * @return bool
     */
    protected static function hasPostThumbNailSupport()
    {
        if( self::$has_post_thumb_func === null )
            self::$has_post_thumb_func = function_exists('has_post_thumbnail');
        return self::$has_post_thumb_func;
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
    protected static function removePrefix($array = array(), $prefix, $preserve_std_objects=false)
    {
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
     * @param $articles
     * @param $version_id
     * @return mixed
     */
    protected function saveArticlesForVersion($list, $articles, $version_id)
    {
        self::sanitizeList($list);

        // Update possibly changed published date
        foreach ($articles as $i => $article) {
            if (!empty($article['post']) && $connected_post = get_post($article['post'])) {
                $articles[$i]['published'] = Arlima_Utils::getPostTimeStamp($connected_post);
            }
        }

        // Remove all old articles
        $sql = $this->wpdb->prepare("DELETE FROM " . $this->dbTable('_article') . " WHERE ala_alv_id=%d", $version_id);
        $this->executeSQLQuery('query', $sql);

        // Add new articles
        $count = 0;
        foreach ($articles as $sort => $article) {
            $this->saveArticle($version_id, $article, $sort, -1, $count);
            $count++;
            if ($count >= $list->getMaxlength())
                break;
        }
        return $list;
    }

}
