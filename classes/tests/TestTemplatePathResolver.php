<?php

require_once __DIR__ .'/setup.php';


class TestTemplatePathResolver extends PHPUnit_Framework_TestCase {

    /**
     * @var Arlima_TemplatePathResolver
     */
    private $path_resolver;

    function setUp() {
        $this->path_resolver = new Arlima_TemplatePathResolver(null, false);
    }

    function testDetermineIfTemplateFile() {
        $this->assertFalse( Arlima_TemplatePathResolver::isTemplateFile( __DIR__.'/does-not-exist.tmpl')  );
        $this->assertFalse( Arlima_TemplatePathResolver::isTemplateFile( __DIR__.'/does-not-exist.php')  );
        $this->assertFalse( Arlima_TemplatePathResolver::isTemplateFile(__FILE__)  );
        $this->assertTrue( Arlima_TemplatePathResolver::isTemplateFile(__DIR__.'/test-templates/some-template.tmpl')  );
    }

    private function stripRootPath($file) {
        return current( array_slice(explode('wp-content/plugins/', $file), 1));
    }

    function testFindDefaultTemplates() {

        $files = $this->path_resolver->getTemplateFiles();
        $this->assertEquals(array('article', 'giant'), array_keys($files));

        // Strip away root path from templates
        foreach($files as $key => $file) {
            $files[$key] = $this->stripRootPath($file);
        }

        $this->assertEquals(array(
               'article'=> 'arlima/templates/article.tmpl',
               'giant'=> 'arlima/templates/giant.tmpl'
            ), $files);

        $this->assertEquals('arlima/templates/article.tmpl', $this->stripRootPath($this->path_resolver->getDefaultTemplate()) );
    }

    function testFindTemplates() {
        $this->path_resolver = new Arlima_TemplatePathResolver(array(__DIR__.'/test-templates/'), false);

        $files = $this->path_resolver->getTemplateFiles();
        $this->assertEquals(array('some-template', 'article', 'giant'), array_keys($files));

        // Strip away root path from templates
        foreach($files as $key => $file) {
            $files[$key] = $this->stripRootPath($file);
        }

        $this->assertEquals(array(
                'some-template'=> 'arlima/classes/tests/test-templates/some-template.tmpl',
                'article'=> 'arlima/templates/article.tmpl',
                'giant'=> 'arlima/templates/giant.tmpl'
            ), $files);

        $this->assertEquals('arlima/templates/article.tmpl', $this->stripRootPath($this->path_resolver->getDefaultTemplate()) );
    }

}
