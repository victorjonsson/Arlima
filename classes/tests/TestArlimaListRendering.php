<?php

require_once __DIR__ . '/setup.php';

class TestArlimaListRendering extends PHPUnit_Framework_TestCase {

    /**
     * @param int $num_articles
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

    function testDeepTemplateInclusion() {
        $list = $this->createList(1);
        $list->setOption('template', 'deep-include');
        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');
        $list_content = $renderer->renderList(false);
        $this->assertEquals('root -> include1 -> include2 -> article1', $list_content);
    }

    function testArticleTemplateOverridingListTemplate() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');

        $articles = $list->getArticles();
        $articles[0]['options']['template'] = 'deep-include';
        $list->setArticles($articles);

        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');
        $list_content = $renderer->renderList(false);
        $this->assertEquals('root -> include1 -> include2 -> article1', $list_content);
    }

    function testSectionDividers() {
        $list = $this->createList(11);
        $list->setOption('supports_sections', 1);
        $articles = $list->getArticles();

        $articles[0]['options']['section_divider'] = 1;
        $articles[3]['options']['section_divider'] = 1;
        $articles[3]['title'] = 'secundo';
        $articles[9]['options']['section_divider'] = 1;
        $list->setArticles($articles);

        $renderer = new Arlima_ListTemplateRenderer($list);

        $renderer->setSection(0);
        $articles = $renderer->getArticlesToRender();

        $this->assertEquals(2, count($articles));
        $this->assertEquals('article2', $articles[0]['title']);
        $this->assertEquals('article3', $articles[1]['title']);

        $renderer->setSection(1);
        $articles = $renderer->getArticlesToRender();

        $this->assertEquals(5, count($articles));
        $this->assertEquals('article5', $articles[0]['title']);
        $this->assertEquals('article6', $articles[1]['title']);
        $this->assertEquals('article7', $articles[2]['title']);

        $renderer->setSection('secundo');
        $articles = $renderer->getArticlesToRender();
        $this->assertEquals(5, count($articles));
        $this->assertEquals('article5', $articles[0]['title']);
        $this->assertEquals('article6', $articles[1]['title']);
        $this->assertEquals('article7', $articles[2]['title']);

        $renderer->setSection('>=1');
        $articles = $renderer->getArticlesToRender();

        $this->assertEquals(6, count($articles));  // Minus the section divider
        $this->assertEquals('article5', $articles[0]['title']);
        $this->assertEquals('article6', $articles[1]['title']);
        $this->assertEquals('article11', $articles[ count($articles)-1 ]['title']);

        $renderer->setSection('>=secundo');
        $articles = $renderer->getArticlesToRender();

        $this->assertEquals(6, count($articles));
        $this->assertEquals('article5', $articles[0]['title']);
        $this->assertEquals('article6', $articles[1]['title']);
        $this->assertEquals('article11', $articles[ count($articles)-1 ]['title']);
    }

    function testOffsetAndLimit() {

        $renderers = array(
           // 'Arlima_ListTemplateRenderer' => 'Failed using Arlima_ListTemplateRenderer',
            'Arlima_SimpleListRenderer' => 'Failed using Arlima_SimpleListRenderer'
        );

        /** @var Arlima_AbstractListRenderingManager|Arlima_SimpleListRenderer $renderer */
        foreach($renderers as $class => $message) {

            $renderer = new $class($this->createList());

            $GLOBALS['title'] = '';
            $GLOBALS['count'] = 0;

            if( $class == 'Arlima_SimpleListRenderer') {
                $renderer->setDisplayArticleCallback(function($article_counter, $article, $post, $list) {
                    $GLOBALS['title'] = $article['title'];
                    $GLOBALS['count'] = $article_counter;
                });
            } else {
                $renderer->setContentCallback(function($article, $deprecated, $post, $article_counter) {
                        $GLOBALS['title'] = $article['title'];
                        $GLOBALS['count'] = $article_counter;
                    });
            }


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