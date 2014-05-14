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

        $this->assertEquals('http://google.se', $template_obj['url']);
        $this->assertEquals('Howdy', $template_obj['title']);
        $this->assertEquals('image', $template_obj['html_image']);
        $this->assertEquals('content', $template_obj['html_content']);
        $this->assertEquals('related', $template_obj['related']);
        $this->assertEquals('begin', $template_obj['article_begin']);
        $this->assertEquals('end', $template_obj['article_end']);
    }

    function testUnderScoreToBreakInTitle()
    {
        $article = Arlima_ListFactory::createArticleDataArray(array('url'=>'http://google.se', 'title'=>'Howdy__there', 'id'=>99));
        $obj_creator = new Arlima_TemplateObjectCreator();
        $tmpl_object = $obj_creator->create($article, false, new stdClass(), 1);
        $this->assertEquals('<h2 class="fsize-24"><a href="http://google.se">Howdy<br />there</a></h2>', $tmpl_object['html_title'], 'Could not convert double underscore to break tag');
    }

    function testNotCrashingWithoutCallbacks() {
        $obj_creator = new Arlima_TemplateObjectCreator();
        $article = Arlima_ListFactory::createArticleDataArray();
        $template_obj = $obj_creator->create($article, false, new stdClass(), 1);
        $this->assertEquals('<h2 class="fsize-24">Unknown</h2>', $template_obj['html_title']);
    }
}
