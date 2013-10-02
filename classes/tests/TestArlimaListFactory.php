<?php

require_once __DIR__ . '/setup.php';


class TestArlimaListFactory extends PHPUnit_Framework_TestCase {

    /**
     * @var Arlima_ListFactory
     */
    private static $factory;


    /**
     * Create database tables
     */
    public static function setUpBeforeClass()
    {
        global $wpdb;
        $wpdb->prefix = '_arlima_wp_test_';
        $wpdb->suppress_errors = false;
        self::$factory = new Arlima_ListFactory(clone $wpdb);
        self::$factory->install();
    }

    /**
     * Remove database tables
     */
    public static function tearDownAfterClass()
    {
        self::$factory->uninstall();
    }

    /**
     */
    function cleanTables() {
        global $wpdb;
        $wpdb->prefix = '_arlima_wp_test_';
        $tables = array('arlima_articlelist', 'arlima_articlelist_version', 'arlima_articlelist_article');
        foreach($tables as $table) {
            $wpdb->query('TRUNCATE TABLE '.$wpdb->prefix.$table);
        }
    }

    private function createList($name='Test list', $slug='test', $opts=array('before_title'=>'<h5 class="argh">', 'after_title'=>'</h5>'), $limit=25) {
        return self::$factory->createList($name, $slug, $opts, $limit);
    }

    function testCreateAndLoadAndUpdate()
    {
        /**
         * @param PHPUnit_Framework_TestCase $test
         * @param Arlima_List $list
         */
        $test_list_content = function($test, $list) {
            $test->assertEquals(1, $list->id());
            $test->assertEquals(true, $list->exists());
            $test->assertEquals('test', $list->getSlug());
            $test->assertEquals('Test list', $list->getTitle());
            $test->assertEquals('h5', $list->getTitleElement());
            $test->assertEquals(25, $list->getMaxlength());
            $test->assertEquals(Arlima_List::STATUS_EMPTY, $list->getStatus());
            $test->assertEquals(array(), $list->getVersions());
            $test->assertFalse($list->isPreview());
            $test->assertFalse($list->isImported());
            $test->assertTrue($list->isLatestPublishedVersion());
            $test->assertEquals('</h5>', $list->getOption('after_title'));
            $test->assertEquals('', $list->getOption('whateva'));
        };

        $this->cleanTables();

        $test_list_content($this, $this->createList());
        $test_list_content($this, self::$factory->loadList(1));
        $test_list_content($this, self::$factory->loadList('test'));
    }

    function testUpdateListProps() {
        $list = $this->createList();
        $list->setMaxlength(1);
        $list->setOption('google', 'test1');
        $list->setOption('pages_to_purge', 'test2');
        $list->setSlug('hejpa');
        $list->setTitle('Jodå');

        self::$factory->updateListProperties($list);

        $reloaded_list = self::$factory->loadList($list->id());
        $this->assertEquals(1, $reloaded_list->getMaxlength());
        $this->assertEquals('Jodå', $reloaded_list->getTitle());
        $this->assertEquals('hejpa', $reloaded_list->getSlug());
        $this->assertEquals('test2', $reloaded_list->getOption('pages_to_purge'));
        $this->assertEquals(null, $reloaded_list->getOption('google')); // Is not an option defined in Arlima_List::getDefaultOptions() and is therefor removed
        $this->assertEquals(Arlima_List::STATUS_EMPTY, $reloaded_list->getStatus());
        $this->assertEquals(array(), $reloaded_list->getVersions());
        $this->assertFalse($reloaded_list->isPreview());
        $this->assertFalse($reloaded_list->isImported());
        $this->assertTrue($reloaded_list->isLatestPublishedVersion());
        $this->assertEquals('</h5>', $reloaded_list->getOption('after_title'));
        $this->assertEquals('', $reloaded_list->getOption('whateva'));
    }

    function testLoadMissingList() {
        $this->assertFalse( self::$factory->loadList(12)->exists() );
        $this->assertFalse( self::$factory->loadList('yoyo')->exists() );
        $this->assertFalse( self::$factory->loadList(12, 193)->exists() );
    }

    function testVersionManagement() {

        $list = $this->createList('Test', 'testing', array(), 2);
        $article = Arlima_ListFactory::createArticleDataArray();

        self::$factory->saveNewListVersion($list, array( $article ), 99);
        $reloaded_list = self::$factory->loadList($list->id());

        $ver_id = $reloaded_list->getVersionAttribute('id');
        $this->assertEquals(Arlima_List::STATUS_PUBLISHED, $reloaded_list->getStatus());
        $this->assertEquals(99, $reloaded_list->getVersionAttribute('user_id'));
        $this->assertTrue( is_numeric($ver_id) );

        self::$factory->saveNewListVersion($list, array( $article, $article, $article ), 98);
        $reloaded_list = self::$factory->loadList($list->id());

        $this->assertEquals(2, count( $reloaded_list->getVersions() ));
        $this->assertFalse( $reloaded_list->isPreview() );
        $this->assertTrue( $reloaded_list->isLatestPublishedVersion() );
        $this->assertEquals(98, (int)$reloaded_list->getVersionAttribute('user_id'));
        $this->assertEquals(2, count($reloaded_list->getArticles())); // Limit was set to two

        self::$factory->saveNewListVersion($list, array( $article, $article ), 97);

        $old_version = self::$factory->loadList($list->id(), $ver_id);

        $this->assertFalse( $old_version->isLatestPublishedVersion() );
        $this->assertEquals($ver_id, $old_version->getVersionAttribute('id'));
        $this->assertEquals(3, count($old_version->getVersions()));
        $this->assertEquals(1, count($old_version->getArticles()));

        self::$factory->removeOldVersions($old_version, 1);

        $latest_version = self::$factory->loadList($list->id());
        $this->assertTrue( $latest_version->getVersionAttribute('id') > $ver_id );
        $this->assertEquals(1, count($latest_version->getVersions()));
    }

    function testVersionCleanUp() {
        $list = $this->createList();
        for($i=1; $i < 15; $i++) {
            self::$factory->saveNewListVersion($list, array(), $i);
        }

        $latest_ver = self::$factory->loadList($list->id());
        $this->assertEquals(14, $latest_ver->getVersionAttribute('user_id'));
        $this->assertEquals(10, count($latest_ver->getVersions()));

        $oldest_ver = self::$factory->loadList($list->getSlug(), array_slice($latest_ver->getVersions(), -1));
        $this->assertEquals(5, $oldest_ver->getVersionAttribute('user_id'));
    }

    function testPreviewVersions() {

        $list = $this->createList();
        self::$factory->saveNewListVersion($list, array(), 5);
        self::$factory->saveNewListVersion($list, array( Arlima_ListFactory::createArticleDataArray() ), 9, true);

        $latest_version = self::$factory->loadList($list->id());
        $this->assertEquals(5, $latest_version->getVersionAttribute('user_id'));

        $preview = self::$factory->loadLatestPreview($list->id());
        $this->assertEquals(1, count($preview->getVersions()));
        $this->assertTrue( $preview->isPreview() );
        $this->assertEquals(1, count($preview->getArticles()));
        $this->assertEquals(9, $preview->getVersionAttribute('user_id'));

        self::$factory->saveNewListVersion($list, array(), 5);
        $newest = self::$factory->loadList($list->id());

        $this->assertEquals(2, count($newest->getVersions()));
        $this->assertEquals(0, count(self::$factory->loadLatestPreview($list->id())->getArticles()) );
    }

    function testDeleteLists() {

        $list = $this->createList();
        $id = $list->id();

        self::$factory->deleteList($list);

        $this->assertFalse( self::$factory->loadList($id)->exists() );
    }

    function testDeprecatedFunctions() {

        $list = $this->createList();

        $this->assertEquals($list->id(), $list->id);
        $this->assertEquals(true, $list->exists);
        $this->assertEquals('test', $list->slug);
        $this->assertEquals('Test list', $list->title);

    }

    function testUpdateArticle() {
        $list = $this->createList();
        self::$factory->saveNewListVersion($list, array(Arlima_ListFactory::createArticleDataArray()), 1);
        $latest_version = self::$factory->loadList($list->id());
        $article = current($latest_version->getArticles());
        self::$factory->updateArticle($article['id'], array('text'=>'Some text', 'title'=>'A title', 'title_fontsize'=>33));
        $reloaded_version = self::$factory->loadList($list->id());
        $article = current($reloaded_version->getArticles());
        $this->assertEquals($article['text'], 'Some text');
        $this->assertEquals($article['title'], 'A title');
        $this->assertEquals($article['title_fontsize'], 33);
    }

    function testCache() {
        $file_cache = new Private_ArlimaFileCache(sys_get_temp_dir());
        self::$factory->setCacheManager( $file_cache );

        $list_id = $this->createList('Cached list', 'cached')->id();
        $list = self::$factory->loadList($list_id);

        $this->assertEquals(array('arlima_list_props_'.$list_id, 'arlima_list_articles_data_'.$list_id), $file_cache->log['get']);
        $file_cache->resetLog();

        self::$factory->saveNewListVersion($list, array( Arlima_ListFactory::createArticleDataArray() ), 1);
        $list = self::$factory->loadList($list_id);

        $this->assertEquals(array('arlima_list_articles_data_'.$list_id), $file_cache->log['set']);
        $this->assertEquals(array('arlima_list_articles_data_'.$list_id), $file_cache->log['delete']);
        $file_cache->resetLog();

        $this->assertEquals(1, count( $list->getArticles() ));

        self::$factory->saveNewListVersion($list, array( Arlima_ListFactory::createArticleDataArray(),Arlima_ListFactory::createArticleDataArray() ), 1);
        $list = self::$factory->loadList($list_id);

        $file_cache->resetLog();

        $list = self::$factory->loadList($list_id);

        $this->assertEquals(array('arlima_list_props_'.$list_id, 'arlima_list_articles_data_'.$list_id), $file_cache->log['get']);

        $this->assertEquals(2, count( $list->getArticles() ));
    }
}


class Private_ArlimaFileCache {

    private $path;

    public $log;

    function __construct($p) {
        $this->path = $p;
        $this->resetLog();
    }

    function resetLog() {
        $this->log = array(
                'get' => array(),
                'set' => array(),
                'delete' => array()
            );
    }

    function get($id) {
        $this->log['get'][] = $id;
        $file = $this->generateFileName($id);
        if( stream_resolve_include_path($file) !== false) {
            return unserialize(file_get_contents($file));
        }
        return false;
    }

    function set($id, $content) {
        $this->log['set'][] = $id;
        file_put_contents($this->generateFileName($id), serialize($content));
    }

    function delete($id) {
        $this->log['delete'][] = $id;
        $file = $this->generateFileName($id);
        if( stream_resolve_include_path($file) !== false)
            @unlink($file);
    }

    private function generateFileName($id) {
        return $this->path .'/'. $id .'.cache';
    }
}