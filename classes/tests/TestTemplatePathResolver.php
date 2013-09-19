<?php

require_once __DIR__ . '/setup.php';


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
        $this->assertEquals(array('article', 'giant', 'widget'), array_keys($files));

        $this->stripRootPathsFromFiles($files);

        $this->assertEquals(array(
               'article'=> array('file'=>'arlima/templates/article.tmpl', 'url' => 'arlima/templates/article.tmpl', 'label' => 'Article', 'name'=>'article'),
               'giant'=> array('file'=>'arlima/templates/giant.tmpl', 'url' => 'arlima/templates/giant.tmpl', 'label' => 'Giant', 'name'=>'giant'),
               'widget'=> array('file'=>'arlima/templates/widget.tmpl', 'url' => 'arlima/templates/widget.tmpl', 'label' => 'Widget', 'name'=>'widget')
            ), $files);

        $this->assertEquals('arlima/templates/article.tmpl', $this->stripRootPath($this->path_resolver->getDefaultTemplate()) );
    }

    function testFindTemplates() {
        $this->path_resolver = new Arlima_TemplatePathResolver(array(__DIR__.'/test-templates/'), false);

        $files = $this->path_resolver->getTemplateFiles();
        $this->assertEquals(array('deep-include', 'some-template', 'article', 'giant', 'widget'), array_keys($files));

        $this->stripRootPathsFromFiles($files);

        $this->assertEquals(array(
                'deep-include'=> array('file'=>'arlima/classes/tests/test-templates/deep-include.tmpl', 'url' => 'arlima/classes/tests/test-templates/deep-include.tmpl', 'label' => 'Deep include', 'name'=>'deep-include'),
                'some-template'=> array('file'=>'arlima/classes/tests/test-templates/some-template.tmpl', 'url' => 'arlima/classes/tests/test-templates/some-template.tmpl', 'label' => 'Some template', 'name'=>'some-template'),
                'article'=> array('file'=>'arlima/templates/article.tmpl', 'url' => 'arlima/templates/article.tmpl', 'label' => 'Article', 'name'=>'article'),
                'giant'=> array('file'=>'arlima/templates/giant.tmpl', 'url' => 'arlima/templates/giant.tmpl', 'label' => 'Giant', 'name'=>'giant'),
                'widget'=> array('file'=>'arlima/templates/widget.tmpl', 'url' => 'arlima/templates/widget.tmpl', 'label' => 'Widget', 'name'=>'widget'),
            ), $files);

        $this->assertEquals('arlima/templates/article.tmpl', $this->stripRootPath($this->path_resolver->getDefaultTemplate()) );
    }

    function testLabeling() {
        add_filter('arlima_template_labels', array($this, 'templateLabels'));
        $this->path_resolver = new Arlima_TemplatePathResolver(array(__DIR__.'/test-templates/'), false);
        $files = $this->path_resolver->getTemplateFiles();

        $this->stripRootPathsFromFiles($files);

        $this->assertEquals(array(
                'deep-include'=> array('file'=>'arlima/classes/tests/test-templates/deep-include.tmpl', 'url' => 'arlima/classes/tests/test-templates/deep-include.tmpl', 'label' => 'Deep include', 'name'=>'deep-include'),
                'some-template'=> array('file'=>'arlima/classes/tests/test-templates/some-template.tmpl', 'url' => 'arlima/classes/tests/test-templates/some-template.tmpl', 'label' => 'APA', 'name'=>'some-template'),
                'article'=> array('file'=>'arlima/templates/article.tmpl', 'url' => 'arlima/templates/article.tmpl', 'label' => 'Article', 'name'=>'article'),
                'giant'=> array('file'=>'arlima/templates/giant.tmpl', 'url' => 'arlima/templates/giant.tmpl', 'label' => 'Giant', 'name'=>'giant'),
                'widget'=> array('file'=>'arlima/templates/widget.tmpl', 'url' => 'arlima/templates/widget.tmpl', 'label' => 'HÄST', 'name'=>'widget')
            ), $files);
    }

    /**
     * @param $files
     */
    private function stripRootPathsFromFiles(&$files)
    {
        foreach ($files as $key => $file) {
            $files[$key] = $file;
            $files[$key]['file'] = $this->stripRootPath($file['file']);
            $files[$key]['url'] = $this->stripRootPath($file['url']);
        }
    }

    function templateLabels($labels) {
        $labels['some-template'] = 'APA';
        $labels['widget'] = 'HÄST';
        return $labels;
    }
}
