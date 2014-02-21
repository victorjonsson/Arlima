<?php

require_once __DIR__ . '/setup.php';


/**
 * @todo: Write tests for images
 * @todo: Write tests for streamers
 */
class TestTemplateObjectCreator extends PHPUnit_Framework_TestCase {

    function testCreator() {

        $obj_creator = new Arlima_TemplateObjectCreator();

        $obj_creator->setArticleBeginCallback(function() {
                return 'begin';
            });

        $obj_creator->setArticleEndCallback(function() {
                return 'end';
            });

        $obj_creator->setBeforeTitleHtml('<i>');
        $obj_creator->setAfterTitleHtml('</i>');

        $obj_creator->setContentCallback(function() {
                return 'content';
            });

        $obj_creator->setImageCallback(function() {
                return 'image';
            });

        $obj_creator->setRelatedCallback(function() {
                return 'related';
            });


        $article = Arlima_ListFactory::createArticleDataArray(array('url'=>'http://google.se', 'title'=>'Howdy', 'id'=>99));

        $template_obj = $obj_creator->create($article, false, new stdClass(), 1);

        $this->assertEquals(array(
                "title"=> "Howdy",
                "url"=>"http://google.se",
                "html_title" => '<i class="fsize-24"><a href="http://google.se">Howdy</a></i>',
                "html_text" => "content",
                "publish_date" => 0,
                'post' => 0,
                "html_content" => "content"
            ), $template_obj['article']);

        $this->assertEquals('image', $template_obj['image']['html']);
        $this->assertEquals('content', $template_obj['article']['html_content']);
        $this->assertEquals('related', $template_obj['related']);
        $this->assertEquals('begin', $template_obj['article_begin']);
        $this->assertEquals('end', $template_obj['article_end']);
    }

    function testUnderScoreToBreakInTitle()
    {
        $article = Arlima_ListFactory::createArticleDataArray(array('url'=>'http://google.se', 'title'=>'Howdy__there', 'id'=>99));
        $obj_creator = new Arlima_TemplateObjectCreator();
        $tmpl_object = $obj_creator->create($article, false, new stdClass(), 1);
        $this->assertEquals('<h2 class="fsize-24"><a href="http://google.se">Howdy<br />there</a></h2>', $tmpl_object['article']['html_title'], 'Could not convert double underscore to break tag');
    }

    function testNotCrashingWithoutCallbacks() {
        $obj_creator = new Arlima_TemplateObjectCreator();
        $article = Arlima_ListFactory::createArticleDataArray();
        $template_obj = $obj_creator->create($article, false, new stdClass(), 1);
        $this->assertEquals('<h2 class="fsize-24">Unknown</h2>', $template_obj['article']['html_title']);
    }

    function testTemplateObject() {
        $data = array(
            'a' => '1',
            'b' => array(
                'c' => (object)array('d' => 'hej', 'e' => array('f'=>'GOOGLE'))
            )
        );

        $obj = Arlima_TemplateObject::create($data);

        $this->assertFalse($obj->unknown);
        $this->assertTrue($obj->b instanceof Arlima_TemplateObject);
        $this->assertTrue($obj->b->c instanceof Arlima_TemplateObject);
        $this->assertTrue($obj->b->c->e instanceof Arlima_TemplateObject);
        $this->assertFalse($obj->b->c->a);
        $this->assertEquals('hej', $obj->b->c->d);
    }
}
