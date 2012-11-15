<?php

require_once __DIR__ .'/setup.php';

/**
 * @todo: Test that articles gets exported as expected
 * @todo: Test that you can set lists as exportable
 */
class TestArlimaExport extends PHPUnit_Framework_TestCase {

    /**
     * @var Arlima_ExportManager
     */
    private static $exporter;

    function setUp() {
        self::$exporter = new Arlima_ExportManager(new Private_ArlimaPluginDummy());
    }

    /**
     * @return Arlima_List
     */
    function createList() {
        $list = new Arlima_List(true, 99);
        $list->setSlug('Slug');
        $list->setTitle('Title');
        return $list;
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

    }

    function testExportJSON() {

        $now = time();
        $list = $this->createList();
        $list->setCreated($now);
        $json = self::$exporter->convertList($list, Arlima_ExportManager::FORMAT_JSON);

        $compare = array(
            'title' => 'Title',
            'slug' => 'Slug',
            'articles' => array()
        );

        $json_data = json_decode($json, true);

        foreach($compare as $key => $val) {
            $this->assertEquals($val, $json_data[$key], 'Json for key '.$key.' was incorrect');
        }

    }

}


class Private_ArlimaPluginDummy {
    function loadSettings() { return array(); }
    function saveSettings() {}
}