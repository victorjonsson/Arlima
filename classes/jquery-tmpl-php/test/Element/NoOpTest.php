<?php

require_once 'test/Element/TestCase.php';

class jQueryTmpl_Element_NoOpTest extends jQueryTmpl_Element_TestCase
{
    public function testShouldReturnNothing()
    {
        $str = 'Some random <strong>string</strong> and {{not a tag}}.';

        $element = $this->_elementFactory->createInline
        (
            'NoOp',
            new jQueryTmpl_Token_NoOp(0, array(), $str)
        );

        $this->assertEquals
        (
            $str,
            $element->setData($this->_data)->render()
        );
    }
}

