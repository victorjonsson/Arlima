<?php

require_once __DIR__ .'/setup.php';


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
        $test_list_content($this, self::$factory->loadListBySlug('test'));
    }

    function testUpdateListProps() {
        $list = $this->createList();
        $list->setMaxlength(1);
        $list->setOption('google', 'test1');
        $list->setOption('pagestopurge', 'test2');
        $list->setSlug('hejpa');
        $list->setTitle('Jodå');

        self::$factory->updateListProperties($list);

        $reloaded_list = self::$factory->loadList($list->id());
        $this->assertEquals(1, $reloaded_list->getMaxlength());
        $this->assertEquals('Jodå', $reloaded_list->getTitle());
        $this->assertEquals('hejpa', $reloaded_list->getSlug());
        $this->assertEquals('test2', $reloaded_list->getOption('pagestopurge'));
        $this->assertEquals('test1', $reloaded_list->getOption('google'));
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
        $this->assertFalse( self::$factory->loadListBySlug('yoyo')->exists() );
        $this->assertFalse( self::$factory->loadList(12, 193)->exists() );
    }

    function testVersionManagement() {

        $list = $this->createList();
        $article = Arlima_ListFactory::createArticleDataArray();

        self::$factory->saveNewListVersion($list, array( $article ), 99);

        $reloaded_list = self::$factory->loadList($list->id());

        var_dump($reloaded_list);

    }
}