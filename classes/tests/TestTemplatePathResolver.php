<?php

require_once __DIR__ . '/setup.php';


class TestTemplatePathResolver extends PHPUnit_Framework_TestCase {

    /**
     * @var Arlima_TemplatePathResolver
     */
    private $path_resolver;

    private $base_dir;

    function setUp() {
        $this->path_resolver = new Arlima_TemplatePathResolver(null, false);
        $this->base_dir = basename(dirname(dirname(dirname(__FILE__))));
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

    function testTemplateFileToURL() {
        $url = $this->path_resolver->fileToUrl(ARLIMA_PLUGIN_PATH.'/templates/article.tmpl');
        $this->assertEquals(ARLIMA_PLUGIN_URL.'templates/article.tmpl', $url);
    }

    function testFindDefaultTemplates() {

        $files = $this->path_resolver->getTemplateFiles();
        $this->assertEquals(array('article', 'giant', 'widget'), array_keys($files));

        $this->stripRootPathsFromFiles($files);

        $this->assertEquals(array(
               'article'=> array('file'=>$this->base_dir.'/templates/article.tmpl', 'url' => $this->base_dir.'/templates/article.tmpl', 'label' => 'Article', 'name'=>'article'),
               'giant'=> array('file'=>$this->base_dir.'/templates/giant.tmpl', 'url' => $this->base_dir.'/templates/giant.tmpl', 'label' => 'Giant', 'name'=>'giant'),
               'widget'=> array('file'=>$this->base_dir.'/templates/widget.tmpl', 'url' => $this->base_dir.'/templates/widget.tmpl', 'label' => 'Widget', 'name'=>'widget')
            ), $files);

        $this->assertEquals($this->base_dir.'/templates/article.tmpl', $this->stripRootPath($this->path_resolver->getDefaultTemplate()) );
    }

    function testFindTemplates() {
        $this->path_resolver = new Arlima_TemplatePathResolver(array(__DIR__.'/test-templates/'), false);

        $files = $this->path_resolver->getTemplateFiles();
        $this->assertEquals(array('deep-include', 'some-template', 'article', 'giant', 'widget'), array_keys($files));

        $this->stripRootPathsFromFiles($files);

        $this->assertEquals(array(
                'deep-include'=> array('file'=>$this->base_dir.'/classes/tests/test-templates/deep-include.tmpl', 'url' => $this->base_dir.'/classes/tests/test-templates/deep-include.tmpl', 'label' => 'Deep include', 'name'=>'deep-include'),
                'some-template'=> array('file'=>$this->base_dir.'/classes/tests/test-templates/some-template.tmpl', 'url' => $this->base_dir.'/classes/tests/test-templates/some-template.tmpl', 'label' => 'Some template', 'name'=>'some-template'),
                'article'=> array('file'=>$this->base_dir.'/templates/article.tmpl', 'url' => $this->base_dir.'/templates/article.tmpl', 'label' => 'Article', 'name'=>'article'),
                'giant'=> array('file'=>$this->base_dir.'/templates/giant.tmpl', 'url' => $this->base_dir.'/templates/giant.tmpl', 'label' => 'Giant', 'name'=>'giant'),
                'widget'=> array('file'=>$this->base_dir.'/templates/widget.tmpl', 'url' => $this->base_dir.'/templates/widget.tmpl', 'label' => 'Widget', 'name'=>'widget'),
            ), $files);

        $this->assertEquals($this->base_dir.'/templates/article.tmpl', $this->stripRootPath($this->path_resolver->getDefaultTemplate()) );
    }

    function testLabeling() {
        add_filter('arlima_template_labels', array($this, 'templateLabels'));
        $this->path_resolver = new Arlima_TemplatePathResolver(array(__DIR__.'/test-templates/'), false);
        $files = $this->path_resolver->getTemplateFiles();

        $this->stripRootPathsFromFiles($files);

        $this->assertEquals(array(
                'deep-include'=> array('file'=>$this->base_dir.'/classes/tests/test-templates/deep-include.tmpl', 'url' => $this->base_dir.'/classes/tests/test-templates/deep-include.tmpl', 'label' => 'Deep include', 'name'=>'deep-include'),
                'some-template'=> array('file'=>$this->base_dir.'/classes/tests/test-templates/some-template.tmpl', 'url' => $this->base_dir.'/classes/tests/test-templates/some-template.tmpl', 'label' => 'APA', 'name'=>'some-template'),
                'article'=> array('file'=>$this->base_dir.'/templates/article.tmpl', 'url' => $this->base_dir.'/templates/article.tmpl', 'label' => 'Article', 'name'=>'article'),
                'giant'=> array('file'=>$this->base_dir.'/templates/giant.tmpl', 'url' => $this->base_dir.'/templates/giant.tmpl', 'label' => 'Giant', 'name'=>'giant'),
                'widget'=> array('file'=>$this->base_dir.'/templates/widget.tmpl', 'url' => $this->base_dir.'/templates/widget.tmpl', 'label' => 'GRODA', 'name'=>'widget')
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
        $labels['widget'] = 'GRODA';
        return $labels;
    }
}
