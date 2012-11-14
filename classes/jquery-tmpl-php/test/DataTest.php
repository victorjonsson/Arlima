<?php

require_once 'jQueryTmpl.php';

class jQueryTmpl_DataTest extends PHPUnit_Framework_TestCase
{
    private $_cut;
    private $_cutPart;

    public function setUp()
    {
        $testData = <<<EOF
{
    "key1" : 123.45,
    "key2" : "a string",
    "key3" : true,
    "key4" : ["item1", "item2", "item3"],
    "key5" :
    {
        "child1" : 543.21,
        "child2" : "another string",
        "child3" : false,
        "child4" : ["item4", "item5", "item6"],
        "child5" :
        {
            "Grand Child 1" : "Rachel",
            "Grand Child 2" : "Naomi",
            "Grand Child 3" : "Cathey"
        }
    }
}
EOF;

        $testDataPart = <<<EOF
{
    "child1" : 543.21,
    "child2" : "another string",
    "child3" : false,
    "child4" : ["item4", "item5", "item6"],
    "child5" :
    {
        "Grand Child 1" : "Rachel",
        "Grand Child 2" : "Naomi",
        "Grand Child 3" : "Cathey"
    }
}
EOF;

        $this->_cut = new jQueryTmpl_Data
        (
            json_decode($testData),
            new jQueryTmpl_Data_Factory()
        );

        $this->_cutPart = new jQueryTmpl_Data
        (
            json_decode($testDataPart),
            new jQueryTmpl_Data_Factory()
        );
    }

    public function testShouldAddDataToObject()
    {
        $this->_cut
            ->addDataPair('addedKey1', 'value 1')
            ->addDataPair('addedKey2', 'value 2');

        $this->assertEquals
        (
            'a string',
            $this->_cut->getValueOf('key2')
        );

        $this->assertEquals
        (
            'value 2',
            $this->_cut->getValueOf('addedKey2')
        );
    }

    /**
     * @expectedException jQueryTmpl_Data_Exception
     */
    public function testShouldNotParseExpresion()
    {
        $this->_cut->getValueOf('someFunc()');
    }

    public function testShouldReturnEmptyStringForDne()
    {
        $this->assertEquals
        (
            '',
            $this->_cut->getValueOf('dne')
        );

        $this->assertEquals
        (
            '',
            $this->_cut->getValueOf('key5.dne')
        );

        $this->assertEquals
        (
            '',
            $this->_cut->getValueOf('key1[0]')
        );
    }

    public function testShouldGetFromFirstLevel()
    {
        $this->assertEquals
        (
            123.45,
            $this->_cut->getValueOf('key1')
        );

        $this->assertEquals
        (
            "a string",
            $this->_cut->getValueOf('key2')
        );

        $this->assertEquals
        (
            true,
            $this->_cut->getValueOf('key3')
        );

        $this->assertEquals
        (
            array('item1','item2','item3'),
            $this->_cut->getValueOf('key4')
        );
    }

    public function testShouldGetFromSecondLevel()
    {
        $this->assertEquals
        (
            543.21,
            $this->_cut->getValueOf('key5.child1')
        );

        $this->assertEquals
        (
            "another string",
            $this->_cut->getValueOf('key5.child2')
        );

        $this->assertEquals
        (
            false,
            $this->_cut->getValueOf('key5.child3')
        );

        $this->assertEquals
        (
            array('item4','item5','item6'),
            $this->_cut->getValueOf('key5.child4')
        );
    }

    public function testShouldGetArrayElements()
    {
        $this->assertEquals
        (
            'item2',
            $this->_cut->getValueOf('key4[1]')
        );

        $this->assertEquals
        (
            'item4',
            $this->_cut->getValueOf('key5.child4[0]')
        );
    }

    public function testShouldGetHashElements()
    {
        $this->assertEquals
        (
            'Naomi',
            $this->_cut->getValueOf('key5.child5["Grand Child 2"]')
        );

        $this->assertEquals
        (
            'Rachel',
            $this->_cut->getValueOf('key5[child5]["Grand Child 1"]')
        );
    }

    public function testShouldGetCharInString()
    {
        $this->assertEquals
        (
            "e",
            $this->_cut->getValueOf('key5.child2[5]')
        );
    }

    public function testShouldGetLengths()
    {
        // Array length
        $this->assertEquals
        (
            3,
            $this->_cut->getValueOf('key5.child4.length')
        );

        // Object length
        $this->assertEquals
        (
            3,
            $this->_cut->getValueOf('key5.child5.length')
        );

        // String length
        $this->assertEquals
        (
            5,
            $this->_cut->getValueOf('key5.child5["Grand Child 2"].length')
        );
    }

    public function testShouldReturnFullObjectIfNothingToSlice()
    {
        // Property not specified
        $this->assertEquals
        (
            $this->_cut,
            $this->_cut->getDataSice('')
        );
    }

    public function testShouldReturnNullIfSliceNotObject()
    {
        // Property is string
        $this->assertEquals
        (
            null,
            $this->_cut->getDataSice('key2')
        );

        // Property is array
        $this->assertEquals
        (
            null,
            $this->_cut->getDataSice('key4')
        );
    }

    public function testShouldSliceOutPartOfData()
    {
        $this->assertEquals
        (
            $this->_cutPart,
            $this->_cut->getDataSice('key5')
        );
    }
}

