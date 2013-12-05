<?php

require_once __DIR__ . '/setup.php';
require_once __DIR__ . '/ExportImportBase.php';


class TestArlimaImport extends ExportImportBase {

    /**
     * @var Arlima_ExportManager
     */
    private static $exporter;

    /**
     * @var Arlima_ImportManager
     */
    private static $importer;

    function setUp() {
        self::$exporter = new Arlima_ExportManager(new Private_ArlimaPluginDummy());
        self::$importer = new Arlima_ImportManager(new Private_ArlimaPluginDummy());
    }


    private function generateServerResponse($body, $content_type, $code = 200)  {
        return array(
            'body' => $body,
            'response' => array('code' => $code),
            'headers' => array( 'content-type' => $content_type )
        );
    }

    function testImportJSON() {

        $now = time();
        $list = $this->createList();
        $list->setCreated($now);
        $json = self::$exporter->convertList($list, Arlima_ExportManager::FORMAT_JSON);

        $server_response = $this->generateServerResponse($json, 'application/json');
        $imported = self::$importer->serverResponseToArlimaList($server_response, 'http://google.se/export/');


        $this->assertTrue( $imported->exists() );
        $this->assertTrue( $imported->isImported() );
        $this->assertEquals('[google.se] Title', $imported->getTitle());
        $this->assertEquals('http://google.se/export/', $imported->id());
        $this->assertEquals(1, $imported->numArticles());

        $article = current($imported->getArticles());
        var_dump($article);
        $this->assertEquals(self::$some_post_id, $article['external_post_id']);
        $this->assertTrue( isset($article['post_id']) && empty($article['post_id']) );

        $this->assertEquals($article['options']['overriding_url'], get_permalink(self::$some_post_id));
    }

    function testImportRSS() {

        $now = time();
        $list = $this->createList();
        $list->setCreated($now);
        $rss = self::$exporter->convertList($list, Arlima_ExportManager::FORMAT_RSS);

        $server_response = $this->generateServerResponse($rss, 'text/xml');
        $imported = self::$importer->serverResponseToArlimaList($server_response, 'http://google.se/export/');

        $this->assertTrue( $imported->exists() );
        $this->assertTrue( $imported->isImported() );
        $this->assertEquals('[google.se] Title (Slug)', $imported->getTitle());
        $this->assertEquals('http://google.se/export/', $imported->id());
    }

    /**
     * @expectedException Exception
     */
    function testInvalidResponseCode() {
        self::$importer->serverResponseToArlimaList( $this->generateServerResponse('', 'application/json', 404), '' );
    }


    /**
     * @expectedException Exception
     */
    function testInvalidContentType() {
        self::$importer->serverResponseToArlimaList( $this->generateServerResponse('', 'text/html'), '' );
    }

    /**
     * @expectedException Exception
     */
    function testInvalidContent() {
        self::$importer->serverResponseToArlimaList( $this->generateServerResponse('<xml', 'application/json'), '' );
    }
}

if( !class_exists('Private_ArlimaPluginDummy') ) {
    class Private_ArlimaPluginDummy {
        function loadSettings() { return array(); }
        function saveSettings() {}
    }
}