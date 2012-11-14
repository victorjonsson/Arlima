<?php

require_once 'test/Element/TestCase.php';

class jQueryTmpl_Element_IfTest extends jQueryTmpl_Element_TestCase
{
    public function testShouldReturnNothingWhenCondIsFalse()
    {
        $element = $this->_elementFactory->createBlock
        (
            'If',
            array
            (
                new jQueryTmpl_Token_IfStart(0, array('name'=>'key5.child3'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST'),
                new jQueryTmpl_Token_IfEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            '',
            $element->setData($this->_data)->render()
        );
    }

    public function testShouldReturnWhenCondIsTrue()
    {
        $element = $this->_elementFactory->createBlock
        (
            'If',
            array
            (
                new jQueryTmpl_Token_IfStart(0, array('name'=>'key3'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST'),
                new jQueryTmpl_Token_Else(0, array(), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST 2'),
                new jQueryTmpl_Token_IfEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            'TEST',
            $element->setData($this->_data)->render()
        );
    }

    public function testShouldReturnElseWhenCondIsFalse()
    {
        $element = $this->_elementFactory->createBlock
        (
            'If',
            array
            (
                new jQueryTmpl_Token_IfStart(0, array('name'=>'key5.child3'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST 1'),
                new jQueryTmpl_Token_Else(0, array('name'=>'key5.child3'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST 2'),
                new jQueryTmpl_Token_Else(0, array(), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST 3'),
                new jQueryTmpl_Token_IfEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            'TEST 3',
            $element->setData($this->_data)->render()
        );
    }

    public function testShouldReturnElseIfWhenCondIsTrue()
    {
        $element = $this->_elementFactory->createBlock
        (
            'If',
            array
            (
                new jQueryTmpl_Token_IfStart(0, array('name'=>'key5.child3'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST 1'),
                new jQueryTmpl_Token_Else(0, array('name'=>'key3'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST 2'),
                new jQueryTmpl_Token_Else(0, array(), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST 3'),
                new jQueryTmpl_Token_IfEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            'TEST 2',
            $element->setData($this->_data)->render()
        );
    }
}

