<?php

require_once 'test/Element/TestCase.php';

class jQueryTmpl_Element_EachTest extends jQueryTmpl_Element_TestCase
{
    public function testShouldReturnNothingWhenCannotIterate()
    {
        $element = $this->_elementFactory->createBlock
        (
            'Each',
            array
            (
                new jQueryTmpl_Token_EachStart(0, array('name'=>'key3'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), 'TEST'),
                new jQueryTmpl_Token_EachEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            '',
            $element->setData($this->_data)->render()
        );
    }

    public function testShoudLoopThroughArray()
    {
        $element = $this->_elementFactory->createBlock
        (
            'Each',
            array
            (
                new jQueryTmpl_Token_EachStart(0, array('name'=>'array'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '<li>'),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'$index'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), ': '),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'$value'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '</li>'),
                new jQueryTmpl_Token_EachEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            '<li>0: av1</li><li>1: av2</li><li>2: av3</li>',
            $element->setData($this->_data)->render()
        );
    }

    public function testShoudLoopThroughArrayWithCustomIndex()
    {
        $element = $this->_elementFactory->createBlock
        (
            'Each',
            array
            (
                new jQueryTmpl_Token_EachStart(0, array('name'=>'array','index'=>'idx','value'=>'val'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '<li>'),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'idx'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), ': '),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'val'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '</li>'),
                new jQueryTmpl_Token_EachEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            '<li>0: av1</li><li>1: av2</li><li>2: av3</li>',
            $element->setData($this->_data)->render()
        );
    }

    public function testShoudLoopThroughNestedEach()
    {
        $element = $this->_elementFactory->createBlock
        (
            'Each',
            array
            (
                new jQueryTmpl_Token_EachStart(0, array('name'=>'array'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '<ul title="'),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'$value'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '">'),
                    new jQueryTmpl_Token_EachStart(1, array('name'=>'key5.child5'), ''),
                        new jQueryTmpl_Token_NoOp(2, array(), '<li>'),
                        new jQueryTmpl_Token_ValueEscaped(2, array('name'=>'$value'), ''),
                        new jQueryTmpl_Token_NoOp(2, array(), '</li>'),
                    new jQueryTmpl_Token_EachEnd(1, array(), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '</ul>'),
                new jQueryTmpl_Token_EachEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            '<ul title="av1"><li>Rachel</li><li>Naomi</li><li>Cathey</li></ul><ul title="av2"><li>Rachel</li><li>Naomi</li><li>Cathey</li></ul><ul title="av3"><li>Rachel</li><li>Naomi</li><li>Cathey</li></ul>',
            $element->setData($this->_data)->render()
        );
    }

    public function testShoudLoopThroughObject()
    {
        $element = $this->_elementFactory->createBlock
        (
            'Each',
            array
            (
                new jQueryTmpl_Token_EachStart(0, array('name'=>'object'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '<li id="'),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'$index'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '">'),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'$value.gender'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), ': '),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'this.name'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), '('),
                    new jQueryTmpl_Token_ValueEscaped(1, array('name'=>'attn'), ''),
                    new jQueryTmpl_Token_NoOp(1, array(), ')</li>'),
                new jQueryTmpl_Token_EachEnd(0, array(), '')
            )
        );

        $this->assertEquals
        (
            '<li id="person1">F: Sophia(!)</li><li id="person2">M: Zack(!)</li><li id="person3">F: Zoe(!)</li><li id="person4">: Morgan(!)</li>',
            $element->setData($this->_data)->render()
        );
    }
}

