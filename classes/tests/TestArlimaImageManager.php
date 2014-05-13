<?php

require_once __DIR__ . '/setup.php';
require_once __DIR__ . '/ExportImportBase.php';


class TestArlimaImageManager extends ExportImportBase {

    private static $img_base64;


    public static function setUpBeforeClass()
    {
        self::$img_base64 = base64_encode(file_get_contents(ARLIMA_PLUGIN_PATH.'/classes/tests/test-img.png'));
    }


    function testCreateImage()
    {
        $attach_id = Arlima_Plugin::saveImageAsAttachment(self::$img_base64, 'test-img.png');
        $this->assertTrue( !is_numeric($attach_id) );
    }

}