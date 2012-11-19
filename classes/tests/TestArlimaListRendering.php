<?php

require_once __DIR__ .'/setup.php';

class TestArlimaListRendering extends PHPUnit_Framework_TestCase {

    /**
     * @return Arlima_List
     */
    private function createList($num_articles = 3) {
        $article_collection = array();
        for($i = 1; $i <= $num_articles; $i++) {
            $article_collection[] = Arlima_ListFactory::createArticleDataArray(array('title' => 'article'.$i));
        }

        $list = new Arlima_List(false, 99);
        $list->setArticles( $article_collection );
        return $list;
    }

    function testOffsetAndLimit() {

        $renderers = array(
            'Arlima_ListTemplateRenderer' => 'Failed using Arlima_ListTemplateRenderer',
            'Arlima_SimpleListRenderer' => 'Failed using Arlima_SimpleListRenderer'
        );

        /** @var Arlima_AbstractListRenderingManager|Arlima_SimpleListRenderer $renderer */
        foreach($renderers as $class => $message) {

            $renderer = new $class($this->createList());

            $GLOBALS['title'] = '';
            $GLOBALS['count'] = 0;

            $hook = $class == 'Arlima_SimpleListRenderer' ? 'setDisplayPostCallback':'setContentCallback';
            $renderer->$hook(function($article_counter, $art, $post, $list) {
                    $GLOBALS['title'] = $art['title'];
                    $GLOBALS['count'] = $article_counter;
                });


            $renderer->renderList(false);

            $this->assertEquals(2, $GLOBALS['count'], $message);
            $this->assertEquals('article3', $GLOBALS['title'], $message);

            $GLOBALS['title'] = '';
            $GLOBALS['count'] = 0;

            $renderer->setLimit(1);

            $renderer->renderList(false);

            $this->assertEquals(0, $GLOBALS['count'], $message);
            $this->assertEquals('article1', $GLOBALS['title'], $message);

            $GLOBALS['title'] = '';
            $GLOBALS['count'] = 0;

            $renderer->setLimit(1);
            $renderer->setOffset(1);

            $this->assertTrue( $renderer->havePosts(), $message );

            $renderer->renderList(false);

            $this->assertEquals(0, $GLOBALS['count'], $message);
            $this->assertEquals('article2', $GLOBALS['title'], $message);

            $renderer->setOffset(10);

            $this->assertFalse( $renderer->havePosts() , $message);

        }
    }

}