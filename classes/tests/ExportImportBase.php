<?php


class ExportImportBase extends PHPUnit_Framework_TestCase {

    protected static $some_post_id;

    static function setUpBeforeClass() {
        $posts = get_posts(array('numberposts'=>1));
        if( empty($posts) ) {
            throw new Exception('The wp installation has to have at least one post in order to run this test');
        }
        self::$some_post_id = $posts[0]->ID;
    }

    /**
     * @return Arlima_List
     */
    function createList() {
        $list = new Arlima_List(true, 99);
        $list->setSlug('Slug');
        $list->setTitle('Title');
        $list->setVersion(array(
            'status' => Arlima_List::STATUS_PUBLISHED,
            'user_id' => 1,
            'created' => time(),
            'id' => 99
        ));
        $list->setArticles( array(Arlima_ListVersionRepository::createArticle(array('post'=>self::$some_post_id))) );
        return $list;
    }

    /**
     * This test is here only so that phpunit won't break
     */
    function testNothing()
    {

    }
}