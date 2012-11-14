<?php

require_once 'jQueryTmpl.php';

abstract class jQueryTmpl_Element_TestCase extends PHPUnit_Framework_TestCase
{
    protected $_data;
    protected $_elementFactory;

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
    },
    "array" : ["av1","av2","av3"],
    "object" :
    {
        "person1" :
        {
            "name" : "Sophia",
            "gender" : "F"
        },
        "person2" :
        {
            "name" : "Zack",
            "gender" : "M"
        },
        "person3" :
        {
            "name" : "Zoe",
            "gender" : "F"
        },
        "person4" :
        {
            "name" : "Morgan"
        }
    },
    "htmlKey" : "<span>Some Text & marks \"'\".</span>",
    "\$wtfKey" : "It works!",
    "attn" : "!"
}
EOF;

        $this->_data = new jQueryTmpl_Data
        (
            json_decode($testData),
            new jQueryTmpl_Data_Factory()
        );

        $this->_elementFactory = new jQueryTmpl_Element_Factory();
    }
}

