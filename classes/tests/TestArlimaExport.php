<?php

require_once __DIR__ . '/setup.php';
require_once __DIR__ . '/ExportImportBase.php';


/**
 * @todo: Test that you can set lists as exportable
 */
class TestArlimaExport extends ExportImportBase {


    /**
     * @var Arlima_ExportManager
     */
    private static $exporter;

    function setUp() {
        self::$exporter = new Arlima_ExportManager(new Private_ArlimaPluginDummy());
    }

    function testExportRSS() {

        $now = time();
        $list = $this->createList();
        $list->setCreated($now);
        $rss = self::$exporter->convertList($list, Arlima_ExportManager::FORMAT_RSS);

        $xml = simplexml_load_string($rss);

        $this->assertEquals('Title (Slug)', (string)$xml->channel->title);
        $this->assertEquals('Arlima v'.Arlima_Plugin::VERSION.' (wordpress plugin)', (string)$xml->channel->generator);
        $this->assertEquals($now, strtotime( (string)$xml->channel->pubDate));
        $this->assertTrue( !empty($xml->channel->link) );
        $this->assertTrue( is_numeric( strtotime( (string)$xml->channel->lastBuildDate) ) );
        $this->assertEquals(1, (int)$xml->channel->ttl);

        $this->assertEquals(1, count($xml->channel->item));
        $this->assertEquals(get_permalink(self::$some_post_id), (string)$xml->channel->item[0]->link);
    }

    function testExportJSON() {

        $now = time();
        $list = $this->createList();
        $list->setCreated($now);
        $json = self::$exporter->convertList($list, Arlima_ExportManager::FORMAT_JSON);


        $json_data = json_decode($json, true);
        $this->assertEquals(1, count($json_data['articles']));
        $this->assertEquals(get_permalink(self::$some_post_id), @$json_data['articles'][0]['external_url']);

        $compare = array(
            'title' => 'Title',
            'slug' => 'Slug',
        );

        foreach($compare as $key => $val) {
            $this->assertEquals($val, $json_data[$key], 'Json for key '.$key.' was incorrect');
        }

    }

}

if( !class_exists('Private_ArlimaPluginDummy') ) {
    class Private_ArlimaPluginDummy {
        function loadSettings() { return array(); }
        function saveSettings() {}
    }
}