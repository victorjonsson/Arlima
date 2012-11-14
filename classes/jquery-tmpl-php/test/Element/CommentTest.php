<?php

require_once 'test/Element/TestCase.php';

class jQueryTmpl_Element_CommentTest extends jQueryTmpl_Element_TestCase
{
    public function testShouldReturnNothing()
    {
        $element = $this->_elementFactory->createInline
        (
            'Comment',
            new jQueryTmpl_Token_Comment(0, array(), '{{! Some Comment !}}')
        );

        $this->assertEquals
        (
            '',
            $element->setData($this->_data)->render()
        );
    }
}

