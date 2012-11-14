<?php

require_once 'test/Element/TestCase.php';

class jQueryTmpl_Element_ValueEscapedTest extends jQueryTmpl_Element_TestCase
{
    public function testShouldReturnTagReplacedWithValue()
    {
        $element = $this->_elementFactory->createInline
        (
            'ValueEscaped',
            new jQueryTmpl_Token_ValueEscaped(0, array('name'=>'key1'), '')
        );

        $this->assertEquals
        (
            123.45,
            $element->setData($this->_data)->render()
        );
    }

    public function testShouldReturnTagReplacedWithValueWithUncommonKey()
    {
        $element = $this->_elementFactory->createInline
        (
            'ValueEscaped',
            new jQueryTmpl_Token_ValueEscaped(0, array('name'=>'$wtfKey'), '')
        );

        $this->assertEquals
        (
            'It works!',
            $element->setData($this->_data)->render()
        );
    }

    public function testShouldReturnNonexistantTagReplacedWithEmptyString()
    {
        $element = $this->_elementFactory->createInline
        (
            'ValueEscaped',
            new jQueryTmpl_Token_ValueEscaped(0, array('name'=>'dneKey'), '')
        );

        $this->assertEquals
        (
            '',
            $element->setData($this->_data)->render()
        );
    }

    public function testShouldReturnTagReplacedWithEscapedValue()
    {
        $element = $this->_elementFactory->createInline
        (
            'ValueEscaped',
            new jQueryTmpl_Token_ValueEscaped(0, array('name'=>'htmlKey'), '')
        );

        $this->assertEquals
        (
            "&lt;span&gt;Some Text &amp; marks &quot;'&quot;.&lt;/span&gt;",
            $element->setData($this->_data)->render()
        );
    }
}

