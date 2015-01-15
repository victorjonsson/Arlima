<?php

require_once __DIR__ . '/setup.php';


class TestArlimaRepos extends PHPUnit_Framework_TestCase {

    /**
     * @var Arlima_ListRepository
     */
    private static $list_repo;

    /**
     * @var Arlima_ListVersionRepository
     */
    private static $ver_repo;

    /**
     * @var Arlima_CMSFacade
     */
    private static $sys;

    private static $has_created_tables = false;


    /**
     * Create database tables
     */
    public static function setUpBeforeClass()
    {
        global $wpdb;
        $wpdb->prefix = '_arlima_wp_test_';
        $wpdb->suppress_errors = false;

        self::$sys = Arlima_CMSFacade::load(clone $wpdb);
        self::$list_repo = new Arlima_ListRepository(self::$sys);
        self::$ver_repo = new Arlima_ListVersionRepository(self::$sys);

        self::$list_repo->createDatabaseTables();
        self::$ver_repo->createDatabaseTables();
        self::$has_created_tables = true;
    }

    static function builder()
    {
        return new Arlima_ListBuilder(self::$list_repo, self::$ver_repo);
    }

    static function cleanTables() {
        foreach(array(self::$list_repo, self::$ver_repo) as $repo) {
            foreach($repo->getDatabaseTables() as $table) {
                self::$sys->runSQLQuery('TRUNCATE TABLE '.$table);
            }
        }
    }

    static function dropTables() {
        foreach(array(self::$list_repo, self::$ver_repo) as $repo) {
            foreach($repo->getDatabaseTables() as $table) {
                self::$sys->runSQLQuery('DROP TABLE IF EXISTS '.$table);
            }
        }
    }

    public function __destruct()
    {
        // In case exception isn't caught
        if( self::$has_created_tables ) {
            self::tearDownAfterClass();
        }
    }

    /**
     * Remove database tables
     */
    public static function tearDownAfterClass()
    {
        self::dropTables();
        self::$has_created_tables = false;
    }


    public function setup() {
        self::cleanTables();
    }


    public function testCreateAndUpdate()
    {
        $list = self::$list_repo->create('Title', 'some-slug', array('test'=>123), 20);
        
        $test_list = function($self, $list, $slug='some-slug') {
            $self->assertEquals('Title', $list->getTitle());
            $self->assertEquals(Arlima_List::STATUS_EMPTY, $list->getStatus());
            $self->assertEquals($slug, $list->getSlug());
            $self->assertEmpty($list->getPublishedVersions());
            $self->assertEmpty($list->getScheduledVersions());
            $self->assertEmpty( $list->getContainingPosts() );
            $self->assertFalse( $list->containsPost(99) );
            $self->assertTrue( $list->exists() );
            $self->assertEmpty( $list->getArticles() );
            $self->assertNotEmpty( $list->getCreated() );
            $self->assertEquals(20, $list->getMaxlength());
            $self->assertEmpty( $list->getOption('lala') );
            $self->assertEquals(123, $list->getOption('test'));            
        };


        $test_list($this, $list);
        $test_list($this, self::$list_repo->load($list->getId()));
        $test_list($this, self::$list_repo->load('some-slug'));
        $test_list($this, self::builder()->id($list->getId())->build());
        $test_list($this, self::builder()->slug('some-slug')->build());

        $this->assertEquals($list->getId(), self::$list_repo->getListId($list->getSlug()));

        $list->setSlug('other-slug');
        self::$list_repo->update($list);
        $this->assertFalse( self::$list_repo->load('some-slug')->exists() );
        $this->assertFalse( self::builder()->slug('some-slug')->build()->exists() );

        $test_list($this, self::$list_repo->load($list->getId()), 'other-slug');
        $test_list($this, self::$list_repo->load('other-slug'), 'other-slug');
        $test_list($this, self::builder()->id($list->getId())->build(), 'other-slug');
        $test_list($this, self::builder()->slug('other-slug')->build(), 'other-slug');
        $this->assertEquals($list->getId(), self::$list_repo->getListId($list->getSlug()));

        $list->setTitle('Other title');
        $list->setOption('test', 99);
        $list->setMaxlength(12);
        self::$list_repo->update($list);

        $list = self::$list_repo->load($list->getId());
        $this->assertEquals('Other title', $list->getTitle());
        $this->assertEquals(12, $list->getMaxlength());
        $this->assertEquals(99, $list->getOption('test'));
    }

    function testVersions()
    {
        $list = self::$list_repo->create('The list', 'superduper', array(), 10);
        $articles = array();
        for($i=0; $i<30; $i++) {
            $articles[] = Arlima_ListVersionRepository::createArticle(array('title'=>'article '.$i));
        }

        self::$ver_repo->create($list, $articles, 666);

        // Load latest published
        $list = self::builder()->id($list->getId())->build();
        $list_arts = $list->getArticles();

        $this->assertEquals(Arlima_List::STATUS_PUBLISHED, $list->getStatus());
        $this->assertNotEmpty($list->getPublishedVersions());
        $this->assertEmpty($list->getScheduledVersions());
        $this->assertEmpty( $list->getContainingPosts() );
        $this->assertFalse( $list->containsPost(99) );
        $this->assertTrue( $list->exists() );
        $this->assertEquals(10, count($list_arts));
        $this->assertEquals('article 0', $list_arts[0]['title']);
        $this->assertEquals('article 9', $list_arts[count($list_arts)-1]['title']);

        // Add some more versions
        for($i=0; $i<5; $i++) {
            self::$ver_repo->create($list, $articles, 900 + $i);
        }

        // Load latest published
        $list = self::builder()->id($list->getId())->build();
        $published = $list->getPublishedVersions();

        $this->assertEquals(Arlima_List::STATUS_PUBLISHED, $list->getStatus());
        $this->assertNotEmpty($list->getPublishedVersions());
        $this->assertEmpty($list->getScheduledVersions());
        $this->assertEmpty( $list->getContainingPosts() );
        $this->assertEquals(6, $list->getVersionAttribute('id'));
        $this->assertEquals(904, $list->getVersionAttribute('user_id'));
        $this->assertEquals(6, count($published));

        // Add even more versions
        for($i=0; $i<6; $i++) {
            self::$ver_repo->create($list, $articles, 1000 + $i);
        }

        // Load latest published
        $list = self::builder()->id($list->getId())->build();
        $published = $list->getPublishedVersions();
        $this->assertEquals(10, count($published)); // The oldest should be removed
        $this->assertEquals(12, $list->getVersionAttribute('id'));
        $this->assertEquals(1005, $list->getVersionAttribute('user_id'));

        // Load a specific version
        $list = self::builder()->id($list->getId())->version(10)->build();
        $published = $list->getPublishedVersions();
        $this->assertEquals(10, count($published));
        $this->assertEquals(10, $list->getVersionAttribute('id'));
        $this->assertEquals(1003, $list->getVersionAttribute('user_id'));

        try {
            self::builder()->id($list->getId())->loadPreview()->build();
            throw new LogicException('Loading a preview that does not exist should not be possible');
        } catch(LogicException $e) {
            throw $e; // something is wrong, this exception should not have been thrown
        } catch( Exception $e ) {
            // This is expected
        }

    }

    function testArticleStuff()
    {
        $list = self::$list_repo->create('A list', 'some-list');
        self::$ver_repo->create($list, array(Arlima_ListVersionRepository::createArticle(array('title'=>'Heloo'))), 10);
        $list = self::builder()->id($list->getId())->build();
        $arts = $list->getArticles();
        $this->assertEquals(1, count($arts));

        $future_article = Arlima_ListVersionRepository::createArticle(array('published' => time() + 100000));

        self::$ver_repo->update($list, array(
            Arlima_ListVersionRepository::createArticle(array('post'=>99, 'options'=>array('test'=>'a'))),
            Arlima_ListVersionRepository::createArticle(array('post'=>98)),
            Arlima_ListVersionRepository::createArticle(array('post'=>99, 'children' => array(
                Arlima_ListVersionRepository::createArticle(array('post' => 100))
            ))),
            $future_article,  // future post
        ), $list->getVersionAttribute('id'));

        $list = self::builder()->id($list->getId())->build();
        $this->assertEquals(3, count($list->getArticles())); // excluding the future post
        $this->assertEquals(array(99, 98, 100), $list->getContainingPosts());
        $this->assertTrue( $list->containsPost(99) );
        $this->assertFalse( $list->containsPost(990) );

        $articles = $list->getArticles();
        self::$ver_repo->updateArticle($articles[0]['id'], array('title'=> 'yes...', 'options'=>array('test2'=>'b')));
        $list = self::builder()->id($list->getId())->build();
        $article = current($list->getArticles());
        $this->assertEquals('yes...', $article['title']);
        $this->assertEquals(array('test'=>'a','test2'=>'b'), $article['options']);

        $list = self::builder()->id($list->getId())->includeFuturePosts()->build();
        $this->assertEquals(4, $list->numArticles()); // including the future post

        $list_data = self::$ver_repo->findListsByPostId(100);
        $this->assertEquals(1, count($list_data));
        $this->assertEquals($list->getId(), $list_data[0]['id']);

    }

    function testPublishingFutureArticles()
    {
        $post_id = -666; // must use post id that does not exist
        $listA = self::$list_repo->create('First list', 'first');
        $listB = self::$list_repo->create('Seconf list', 'second');

        // Make future articles published using post id
        $future_article = Arlima_ListVersionRepository::createArticle(array('post'=>$post_id, 'published' => time() + 100000));

        self::$ver_repo->create($listA, array($future_article), 12);
        self::$ver_repo->create($listB, array($future_article), 22);

        $this->assertEquals(0, self::builder()->slug('second')->build()->numArticles());
        $this->assertEquals(0, self::builder()->slug('first')->build()->numArticles());

        self::$ver_repo->updateArticlePublishDate(time()-1, $post_id);

        $this->assertEquals(1, self::builder()->id($listA->getId())->build()->numArticles());
        $this->assertEquals(1, self::builder()->id($listB->getId())->build()->numArticles());
    }

    function testScheduledVersions()
    {

    }

    function testPreviewVersion()
    {
        $list = self::$list_repo->create('test', 'test');
        $pre_version = self::$ver_repo->create($list, array(), 1, true);
        $list = self::builder()->loadPreview()->id($list->getId())->build();
        $this->assertEquals(Arlima_List::STATUS_PREVIEW, $list->getStatus());
        $this->assertEquals($pre_version, $list->getVersionAttribute('id'));
        $this->assertEquals(1, $list->getVersionAttribute('user_id'));
        $this->assertTrue( $list->isPreview() );
        $this->assertFalse( $list->isPublished() );
        $this->assertFalse( $list->isScheduled() );
        $this->assertFalse( $list->isImported() );

        $pre_version = self::$ver_repo->create($list, array(), 22, true);
        $list = self::builder()->loadPreview()->id($list->getId())->build();
        $this->assertEquals($pre_version, $list->getVersionAttribute('id'));
        $this->assertEquals(22, $list->getVersionAttribute('user_id'));
        $this->assertEquals(0, count($list->getPublishedVersions()));
        $this->assertEquals(0, count($list->getScheduledVersions()));

        $all_versions = self::$ver_repo->loadListVersions($list);
        $this->assertEquals(1, count($all_versions['preview']));

        // Creating a new version should delete old previews
        self::$ver_repo->create($list, array(), 12);
        $all_versions = self::$ver_repo->loadListVersions($list);
        $this->assertEquals(0, count($all_versions['preview']));
    }

    function testDelete()
    {
        self::$list_repo->create('list', 'list1');
        self::$list_repo->create('list', 'list2');
        self::$list_repo->create('list', 'list3');

        $slugs = self::$list_repo->loadListSlugs();
        $this->assertEquals(3, count($slugs));
        $this->assertEquals(1, $slugs[0]->id);
        $this->assertEquals(2, $slugs[1]->id);
        $this->assertEquals(3, $slugs[2]->id);

        $second_list = self::$list_repo->load(2);
        self::$list_repo->delete($second_list);

        $slugs = self::$list_repo->loadListSlugs();
        $this->assertEquals(2, count($slugs));
        $this->assertEquals(1, $slugs[0]->id);
        $this->assertEquals(3, $slugs[1]->id);

        // Test deleting versions
        $list = self::$list_repo->load(3);
        self::$ver_repo->create($list, array(), 1);
        self::$ver_repo->create($list, array(), 1);
        self::$ver_repo->create($list, array(), 1);
        self::$ver_repo->create($list, array(), 1);
        $id = self::$ver_repo->create($list, array(Arlima_ListVersionRepository::createArticle()), 1);

        var_export( $id );

        $all_versions = self::$ver_repo->loadListVersions($list);
        $this->assertEquals(5, count($all_versions['published']));
        $this->assertEquals(1, self::builder()->id($id)->build()->numArticles());

        self::$ver_repo->clear($id);
        $this->assertEquals(0, self::builder()->id($id)->build()->numArticles());

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
