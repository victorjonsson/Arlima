<?php


class TestActions extends PHPUnit_Framework_TestCase {

    function setUp() {
        remove_all_filters('arlima_article_begin');
        remove_all_filters('arlima_article_end');
        remove_all_filters('arlima_article_content');
        remove_all_filters('arlima_article_image');
        remove_all_filters('arlima_template_object');
        remove_all_filters('arlima_article_future_post');
        $this->executed_actions = 0;
    }

    /**
     * @return Arlima_List
     */
    private function createList($num_articles = 3) {
        $article_collection = array();
        for($i = 1; $i <= $num_articles; $i++) {
            $article_collection[] = Arlima_ListVersionRepository::createArticle(array('title' => 'article'.$i));
        }

        $list = new Arlima_List(true, 99);
        $list->setArticles( $article_collection );
        return $list;
    }

    /**
     * @var Arlima_List
     */
    private $list;

    private $executed_actions=0;

    function testActionArguments() {
        $this->list = $this->createList(1);
        add_filter('arlima_article_begin', array($this, 'checkArgs'));
        add_filter('arlima_article_end', array($this, 'checkArgs'));
        add_filter('arlima_article_content', array($this, 'checkArgs'));
        add_filter('arlima_article_image', array($this, 'checkArgs'));
        arlima_render_list($this->list, array('echo' => false));
        $this->assertEquals(4, $this->executed_actions);
    }

    function testActionArgumentsForCustomActions() {
        $this->list = $this->createList(1);
        $my = 'my-actions';
        add_filter('arlima_article_begin-'.$my, array($this, 'checkArgs'));
        add_filter('arlima_article_end-'.$my, array($this, 'checkArgs'));
        add_filter('arlima_article_content-'.$my, array($this, 'checkArgs'));
        add_filter('arlima_article_image-'.$my, array($this, 'checkArgs'));
        arlima_render_list($this->list, array('echo' => false, 'filter_suffix'=>$my));
        $this->assertEquals(4, $this->executed_actions);
    }

    function checkArgs($args) {
        $this->executed_actions++;
        $this->assertEquals($this->list, $args['list']);
        $this->assertEquals(0, $args['count']);
        $this->assertTrue( isset($args['post']) );
        $this->assertTrue( !empty($args['article']) );
    }

    function testArticleCount() {
        $list = $this->createList(3);
        add_filter('arlima_article_begin', array($this, 'checkArticleCount'));
        arlima_render_list($list, array('echo'=>false));
    }

    function checkArticleCount($args) {
        $this->assertEquals($this->executed_actions, $args['count']);
        $this->executed_actions++;
    }

    function testSomeFilters() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');
        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');

        add_filter('arlima_article_begin', function($data) {
            $data['content'] = 'BEGIN';
            return $data;
        });

        add_filter('arlima_article_end', function($data) {
            $data['content'] = 'END';
            return $data;
        });

        add_filter('arlima_article_content', function($data) {
            $data['content'] = 'CONTENT';
            return $data;
        });

        $content = arlima_render_list($renderer, array('echo'=>false));
        $this->assertEquals('hello-BEGIN-CONTENT-END---', $content);
    }

    function testSomeFiltersSettingContentToFalse() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');
        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');

        add_filter('arlima_article_begin', function($data) {
                $data['content'] = false;
                return $data;
            });

        add_filter('arlima_article_end', function($data) {
                $data['content'] = false;
                return $data;
            });

        add_filter('arlima_article_content', function($data) {
                $data['content'] = false;
                return $data;
            });

        $content = arlima_render_list($renderer, array('echo'=>false));
        $this->assertEquals('hello------', $content);
    }

    function testSomeFiltersReturningFalse() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');
        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');

        add_filter('arlima_article_begin', function($data) {
                return false;
            });

        add_filter('arlima_article_end', function($data) {
                return false;
            });

        add_filter('arlima_article_content', function($data) {
                return false;
            });

        $content = arlima_render_list($renderer, array('echo'=>false));
        $this->assertEquals('hello------', $content);
    }

    function testSpecifiedFilters() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');
        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');

        // Add default filters

        add_filter('arlima_article_begin', function($data) {
                $data['content'] = 'BEGIN';
                return $data;
            });

        add_filter('arlima_article_end', function($data) {
                $data['content'] = 'END';
                return $data;
            });

        add_filter('arlima_article_content', function($data) {
                $data['content'] = 'CONTENT';
                return $data;
            });

        // Add specific filters

        $filter_suffix = 'my-filter';

        add_filter('arlima_article_begin-'.$filter_suffix, function($data) {
                $data['content'] = 'BEGIN-filtered';
                return $data;
            });

        add_filter('arlima_article_end-'.$filter_suffix, function($data) {
                $data['content'] = 'END-filtered';
                return $data;
            });

        add_filter('arlima_article_content-'.$filter_suffix, function($data) {
                $data['content'] = 'CONTENT-filtered';
                return $data;
            });

        $content = arlima_render_list($renderer, array('echo'=>false, 'filter_suffix'=>$filter_suffix));
        $this->assertEquals('hello-BEGIN-filtered-CONTENT-filtered-END-filtered---', $content);
    }

    public function testChildArticles() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');

        $articles = $list->getArticles();
        $articles[0]->addChild(Arlima_ListVersionRepository::createArticle(array('title' => 'childA', 'parent'=>0))->toArray());
        $articles[0]->addChild(Arlima_ListVersionRepository::createArticle(array('title' => 'childB', 'parent'=>0))->toArray());

        $list->setArticles($articles);

        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');
        $content = arlima_render_list($renderer, array('echo'=>false));
        $expected = 'hello------hello-----childA---hello-----childB---';

        $this->assertEquals($expected, $content);
    }

    public function testFloatingChildArticles() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');

        $articles = $list->getArticles();
        $articles[0]->addChild(Arlima_ListVersionRepository::createArticle(array('title' => 'childA', 'parent'=>0, 'options'=>array('floating'=>true)))->toArray());
        $articles[0]->addChild(Arlima_ListVersionRepository::createArticle(array('title' => 'childB', 'parent'=>0, 'options'=>array('floating'=>true, 'inlineWithChild'=>0)))->toArray());

        $list->setArticles($articles);

        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');
        $content = arlima_render_list($renderer, array('echo'=>false));
        $expected = 'hello------<div class="arlima child-wrapper child-wrapper-2">hello-----childA--_IS_SPLIT_-hello-----childB--_IS_SPLIT_-</div>';

        $this->assertEquals($expected, $content);
    }

    public function testOneChildArticle() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');

        $articles = $list->getArticles();
        $articles[0]->addChild(Arlima_ListFactory::createArticleDataArray(array('title' => 'childA', 'parent'=>0))->toArray());

        $list->setArticles($articles);

        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');
        $content = arlima_render_list($renderer, array('echo'=>false));
        $expected = 'hello------hello-----childA---';

        $this->assertEquals($expected, $content);
    }

    public function testObjectFilter() {
        $list = $this->createList(1);
        $list->setOption('template', 'some-template');


        add_filter('arlima_template_object', 'TestActions::templateObjectFilter');

        $renderer = new Arlima_ListTemplateRenderer($list, __DIR__.'/test-templates/');
        $content = arlima_render_list($renderer, array('echo'=>false));
        $expected = 'hello--Changed in filter----';

        $this->assertEquals($expected, $content);
    }

    public static function templateObjectFilter($obj) {
        $obj['html_content'] = 'Changed in filter';
        return $obj;
    }
}
