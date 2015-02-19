<?php

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
        self::$exporter = new Arlima_ExportManager();
        self::$importer = new Arlima_ImportManager();
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

       # var_dump($list->toArray());

        $json = self::$exporter->convertList($list, Arlima_ExportManager::FORMAT_JSON);
        $server_response = $this->generateServerResponse($json, 'application/json');

#        echo PHP_EOL .' ---- '.PHP_EOL;

        $imported = self::$importer->serverResponseToArlimaList($server_response, 'http://google.se/export/');

        $this->assertTrue( $imported->exists() );
        $this->assertTrue( $imported->isImported() );
        $this->assertEquals('[google.se] Title', $imported->getTitle());
        $this->assertEquals('http://google.se/export/', $imported->getId());
        $this->assertEquals(1, $imported->numArticles());

        $article = current($imported->getArticles());
        $this->assertEquals(self::$some_post_id, $article['externalPost']);
        $this->assertTrue( isset($article['post']) && empty($article['post']) );

        $this->assertEquals($article['options']['overridingURL'], get_permalink(self::$some_post_id));
    }

    function ssssstestImportRSS() {

        $now = time();
        $list = $this->createList();
        $list->setCreated($now);
        $rss = self::$exporter->convertList($list, Arlima_ExportManager::FORMAT_RSS);

        $server_response = $this->generateServerResponse($rss, 'text/xml');
        $imported = self::$importer->serverResponseToArlimaList($server_response, 'http://google.se/export/');

        $this->assertTrue( $imported->exists() );
        $this->assertTrue( $imported->isImported() );
        $this->assertEquals('[google.se] Title', $imported->getTitle());
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