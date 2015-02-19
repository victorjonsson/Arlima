<?php


/**
 * @todo: Write tests for images
 * @todo: Write tests for streamers
 */
class TestTemplateObjectCreator extends PHPUnit_Framework_TestCase {

    function testCreator() {

        remove_all_filters('arlima_template_object');

        $obj_creator = new Arlima_TemplateObjectCreator();
        $obj_creator->setList(new Arlima_List());

        add_filter('arlima_article_begin', function($data) {
            $data['content'] = 'begin';
            return $data;
        });
        add_filter('arlima_article_end', function($data) {
            $data['content'] = 'end';
            return $data;
        });
        add_filter('arlima_article_content', function($data) {
            $data['content'] = 'content';
            return $data;
        });
        add_filter('arlima_article_image', function($data) {
            $data['content'] = 'image';
            return $data;
        });
        add_filter('arlima_article_related_content', function($data) {
            $data['content'] = 'related';
            return $data;
        });

        $article = Arlima_ListVersionRepository::createArticle(array('options'=>array('overridingURL'=>'http://google.se'), 'title'=>'Howdy', 'id'=>99));
        $template_obj = $obj_creator->create($article, false, false, 1);

        $this->assertEquals('http://google.se', $template_obj['url']);
        $this->assertEquals('Howdy', $template_obj['title']);
        $this->assertEquals('<a href="http://google.se">image</a>', $template_obj['html_image']);
        $this->assertEquals('content', $template_obj['html_content']);
        $this->assertEquals('related', $template_obj['related']);
        $this->assertEquals('begin', $template_obj['article_begin']);
        $this->assertEquals('end', $template_obj['article_end']);
    }

    function testUnderScoreToBreakInTitle()
    {
        $article = Arlima_ListFactory::createArticleDataArray(array('options'=>array('overridingURL'=>'http://google.se'), 'title'=>'Howdy__there', 'id'=>99));
        $obj_creator = new Arlima_TemplateObjectCreator();
        $obj_creator->setList(new Arlima_List());
        $tmpl_object = $obj_creator->create($article, false, false, 1);
        $this->assertEquals('<h2 class="fsize-24"><a href="http://google.se">Howdy<br />there</a></h2>', $tmpl_object['html_title'], 'Could not convert double underscore to break tag');
    }

    function testNotCrashingWithoutCallbacks() {
        $obj_creator = new Arlima_TemplateObjectCreator();
        $obj_creator->setList(new Arlima_List());
        $article = Arlima_ListFactory::createArticleDataArray();
        $template_obj = $obj_creator->create($article, false, false, 1);
        $this->assertEquals('<h2 class="fsize-24">Unknown</h2>', $template_obj['html_title']);
    }
}
