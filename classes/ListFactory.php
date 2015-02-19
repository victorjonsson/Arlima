<?php

/**
 * Class with all the know-how about creation of article lists and how the data is stored in the database.
 * All direct use of wpdb in the Arlima plugin should be placed in this class, at least as long as
 * the database communication is about getting data related to article lists.
 *
 * @deprecated This class is deprecated
 *
 * @see Arlima_ListBuilder
 * @see Arlima_ListRepository
 * @see Arlima_ListVersionReposityr
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_ListFactory {


    /**
     * @var Arlima_ListRepository
     */
    private $list_repo;

    /**
     * @var Arlima_ListVersionRepository
     */
    private $version_repo;

    /**
     * @var Arlima_CMSFacade
     */
    private $cms;

    /**
     * @param wpdb $db
     * @param null $cache
     */
    public function __construct($db = null, $cache = null)
    {
        $this->cms = Arlima_CMSFacade::load($db);
        $this->list_repo = new Arlima_ListRepository($this->cms, $cache);
        $this->version_repo = new Arlima_ListVersionRepository($this->cms, $cache);
    }
    
    /**
     *
     * Creates a new article list
     *
     * @deprecated
     * @see Arlima_ListRepository::create()
     *
     * @param $title
     * @param $slug
     * @param array $options
     * @param int $max_length
     * @throws Exception
     * @return Arlima_List
     */
    public function createList($title, $slug, $options=array(), $max_length=50)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListRepository::create()');
        return $this->list_repo->create($title, $slug, $options, $max_length);
    }

    /**
     * Will update name, slug and options of given list in the database
     *
     * @deprecated
     * @see Arlima_ListRepository::update()
     *
     * @param Arlima_List $list
     * @throws Exception
     */
    public function updateListProperties($list)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListRepository::update()');
        $this->list_repo->update($list);
    }

    /**
     *
     * @deprecated
     *
     * @param Arlima_List $list
     */
    public function deleteList($list)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListRepository::delete() and Arlima_ListVersionRepository::deleteListVersions()');
        $this->list_repo->delete($list);
        $this->version_repo->deleteListVersions($list);
    }

    /**
     * @deprecated
     * @see Arlima_ListVersionRepository::delete()
     *
     * @param $version_id
     */
    public function deleteListVersion($version_id) {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListVersionRepository::delete()');
        $this->version_repo->delete($version_id);
    }

    /**
     *
     * @deprecated
     * @see Arlima_ListVersionRepository::updateArticle()
     *
     * @param int $id
     * @param array $data
     * @throws Exception
     */
    public function updateArticle($id, $data)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListVersionRepository::updateArticle()');
        $this->version_repo->updateArticle($id, $data);
    }


    /**
     *
     * @deprecated
     * @see Arlima_ListVersionRepository::create()
     * @see createScheduledVersion::createScheduleVersion()
     *
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
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListVersionRepository::save() and Arlima_ListVersionRepository::scheduleVersion()');

        if( $schedule_time ) {
            $version_id = $this->version_repo->createScheduledVersion($list, $articles, $user_id, $schedule_time);
        } else {
            $version_id = $this->version_repo->create($list, $articles, $user_id, $preview);
        }

        // Reload list
        $list = $this->loadList($list->getId(), $version_id, true);

        return $version_id;
    }

    /**
     * @deprecated
     * @see Arlima_ListVersionRepository::update()
     *
     * @param Arlima_List $list
     * @param array $articles
     * @param int $version_id
     */
    public function updateListVersion($list, $articles, $version_id)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListVersionRepository::changeArticles()');

        $this->version_repo->update($list, $articles, $version_id);

        // Reload list
        $list = $this->loadList($list->getId(), $version_id, true);
    }

    /**
     * Future posts will always be included in the list if you're loading a specific version of
     * the list. Otherwise you can use the argument $include_future_posts to control if the list
     * should contain future posts as well. Setting $include_future_posts to true will how ever
     * disable the caching of the article data
     *
     * @deprecated
     * @see Arlima_ListBuilder
     *
     * @param int|string $id Either list id, list slug or URL to external list or RSS-feed
     * @param mixed $version Omit this argument, or set it to false, if you want to load the latest published version of the list. This argument won't have any effect if you're loading an external list/feed
     * @param bool $include_future_posts Whether or not the list should include future posts. This argument won't have any effect if you're loading an external list/feed
     * @return Arlima_List
     */
    public function loadList($id, $version=false, $include_future_posts=false)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListBuilder');

        $cache_list = false;
        $cache_key = 'external_list_'.$id;

        $builder = new Arlima_ListBuilder($this->list_repo, $this->version_repo);

        if( filter_var($id, FILTER_VALIDATE_URL) !== false ) {

            if( $list = wp_cache_get($cache_key, 'arlima') ) {
                return $list;
            }

            $builder->import($id);
            $cache_list = true;
        }
        else {

            $builder->id($id);

            if( $version == 'preview' ) {
                $builder->loadPreview();
            } else {
                $builder->version($version);
            }

            if( $include_future_posts )
                $builder->includeFutureArticles();
        }

        $list = $builder->build();

        if( $cache_list )
            wp_cache_set($cache_key, $list, 'arlima', 180);

        return $list;
    }

    /**
     * Load latest preview version of article list with given id.
     *
     * @deprecated
     * @see Arlima_ListBuilder
     *
     * @param int $id
     * @return Arlima_List
     */
    public function loadLatestPreview($id)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListBuilder');
        return $this->loadList($id, 'preview');
    }


    /**
     * will return an array looking like array( stdClass(id => ... title => ... slug => ...) )
     *
     * @deprecated
     * @see Arlima_ListRepository::loadListSlugs()
     *
     * @return array
     */
    public function loadListSlugs()
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListRepository::loadListSlugs()');
        return $this->list_repo->loadListSlugs();
    }

    /**
     *
     * @deprecated
     * @see Arlima_ListRepository::getListId()
     *
     * @param $slug
     * @return int|bool
     */
    public function getListId($slug)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListRepository::getListId()');
        return $this->list_repo->getListId($slug);
    }

    /**
     * Loads an array with objects containing list id and options that have teasers that are linked to the post with $post_id
     *
     * @deprecated
     * @see Arlima_ListVersionRepository::findListsByPostId()
     *
     * @param  int $post_id
     * @return array
     */
    public function loadListsByArticleId($post_id)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListVersionRepository::findListsByPostID()');
        return $this->version_repo->findListsByPostId($post_id);
    }

    /**
     * Get latest article teaser created that is related to given post
     *
     * @deprecated
     * @see Arlima_ListVersionRepository::getLatestArticle()
     *
     * @param $post_id
     * @return array
     */
    public function getLatestArticle($post_id)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListVersionRepository::getLatestArticle()');
        return $this->version_repo->getLatestArticle($post_id);
    }



    /* * * * * * * * * * * * * * * * * INSTALL / UNINSTALL  * * * * * * * * * * * * * * * * * */



    /**
     * Database installer for this plugin.
     * @static
     */
    public function install()
    {
        $this->version_repo->createDatabaseTables();
        $this->list_repo->createDatabaseTables();
    }

    public static function databaseUpdates($version)
    {

    }

    /**
     * Removes the database tables created when plugin was installed
     * @static
     */
    public function uninstall()
    {
        $tables = array_merge($this->version_repo->getDatabaseTables(), $this->list_repo->getDatabaseTables());
        foreach($tables as $t) {
            $this->cms->runSQLQuery('DROP TABLE IF EXISTS '.$t);
        }
    }

    /**
     * Updates publish date for all arlima articles related to given post and clears the cache
     * of the lists where they appear
     *
     * @deprecated
     * @see Arlima_ListVersionRepository::updateArticlePublishDate()
     *
     * @param stdClass|WP_Post $post
     */
    public function updateArticlePublishDate($post)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListVersionRepository::updateArticlePublishDate()');
        if( $post && $post->post_type == 'post' ) {
            $sys = Arlima_CMSFacade::load();
            $this->version_repo->updateArticlePublishDate($sys->getPostTimeStamp($post), $post->ID);
        }
    }



    /* * * * * * * * * * * * * * * * * STATIC UTILITY FUNCTIONS  * * * * * * * * * * * * * * * * * */


    /**
     * The article data is in fact created with javascript in front-end so you can't
     * see this function as the sole creator of article objects. For that reason it might be
     * good to take look at this function once in a while, making sure it generates a similar object
     * as generated with javascript in front-end.
     *
     * @deprecated
     * @see Arlima_ListVersionRepository::createArticle()
     *
     * @static
     * @param array $override[optional=array()]
     * @return array|Arlima_Article
     */
    public static function createArticleDataArray($override=array())
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_ListVersionRepository::createArticle()');
        return Arlima_ListVersionRepository::createArticle($override);
    }

    /**
     * Takes a post and returns an Arlima article object
     *
     * @deprecated
     * @see Arlima_CMSInterface::postToArlimaArticle
     *
     * @param $post
     * @param string|null $text
     * @param array $override
     * @return array
     */
    public static function postToArlimaArticle($post, $text = null, $override=array())
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_CMSInterface::postToArlimaArticle');
        if( $text ) {
            $override['content'] = $text;
        }
        return Arlima_CMSFacade::load()->postToArlimaArticle($post, $override);
    }
}
