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

    private function createList($name='Test', $slug='text', $opts=array('pages_to_purge'=>'/'), $limit=25) {
        return self::$factory->createList('Test', 'test', array('pages_to_purge'=>'/'), 25);
    }

    function testCreateAndLoad()
    {
        $list = $this->createList();

        $test_list_content = function($test) use($list) {
            $test->assertEquals(1, $list->id());
            $test->assertEquals(true, $list->exists());
        };

        $test_list_content($this);

        $list = self::$factory->loadList(1);

        $test_list_content($this);


        $list = self::$factory->loadListBySlug('test');

        $test_list_content($this);
    }

    function testLoadMissingList() {
        $this->assertFalse( self::$factory->loadList(12)->exists() );
    }
}