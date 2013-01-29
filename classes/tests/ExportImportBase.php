<?php


class ExportImportBase extends PHPUnit_Framework_TestCase {

    protected static $some_post_id;

    static function setUpBeforeClass() {
        $posts = get_posts(array('numberposts'=>1));
        if( empty($posts) ) {
            throw new Exception('The wp installation has to have atleast one post in order to run this test');
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
        $list->setArticles( array(Arlima_ListFactory::createArticleDataArray(array('post_id'=>self::$some_post_id))) );
        return $list;
    }

}