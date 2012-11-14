<?php

require_once 'jQueryTmpl.php';

class jQueryTmpl_TokenizerTest extends PHPUnit_Framework_TestCase
{
    private $_cut;

    public function setUp()
    {
        $tokenFactory = $this->getMock('jQueryTmpl_Token_Factory', array('create'));
        $tokenFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnCallback('jQueryTmpl_TokenizerTest__FactoryCallback'));

        $tag1 = $this->getMock('jQueryTmpl_Tag', array('getTokenType', 'getRegex', 'getNestingValue', 'parseTag'));
        $tag1
            ->expects($this->any())
            ->method('getTokenType')
            ->will($this->returnValue('>'));
        $tag1
            ->expects($this->any())
            ->method('getRegex')
            ->will($this->returnValue('/>/'));
        $tag1
            ->expects($this->any())
            ->method('getNestingValue')
            ->will($this->returnValue(array(0,1)));
        $tag1
            ->expects($this->any())
            ->method('parseTag')
            ->will($this->returnValue(array()));

        $tag2 = $this->getMock('jQueryTmpl_Tag', array('getTokenType', 'getRegex', 'getNestingValue', 'parseTag'));
        $tag2
            ->expects($this->any())
            ->method('getTokenType')
            ->will($this->returnValue('<'));
        $tag2
            ->expects($this->any())
            ->method('getRegex')
            ->will($this->returnValue('/</'));
        $tag2
            ->expects($this->any())
            ->method('getNestingValue')
            ->will($this->returnValue(array(-1,0)));
        $tag2
            ->expects($this->any())
            ->method('parseTag')
            ->will($this->returnValue(array()));

        $tag3 = $this->getMock('jQueryTmpl_Tag', array('getTokenType', 'getRegex', 'getNestingValue', 'parseTag'));
        $tag3
            ->expects($this->any())
            ->method('getTokenType')
            ->will($this->returnValue('='));
        $tag3
            ->expects($this->any())
            ->method('getRegex')
            ->will($this->returnValue('/=/'));
        $tag3
            ->expects($this->any())
            ->method('getNestingValue')
            ->will($this->returnValue(array(0,0)));
        $tag3
            ->expects($this->any())
            ->method('parseTag')
            ->will($this->returnValue(array()));

        $this->_cut = new jQueryTmpl_Tokenizer($tokenFactory);
        $this->_cut
            ->addTag($tag1)
            ->addTag($tag2)
            ->addTag($tag3);
    }

    public function testShouldTokenizeSingleTag()
    {
        $tokens = $this->_cut->tokenize('=');

        $this->assertEquals
        (
            array
            (
                array('=', 0, array(), '=')
            ),
            $tokens
        );
    }

    public function testShouldTokenizeAllTags()
    {
        $tokens = $this->_cut->tokenize('123=456=789');

        $this->assertEquals
        (
            array
            (
                array('NoOp', 0, array(), '123'),
                array('=', 0, array(), '='),
                array('NoOp', 0, array(), '456'),
                array('=', 0, array(), '='),
                array('NoOp', 0, array(), '789')
            ),
            $tokens
        );
    }

    public function testShouldNestTokens()
    {
        $tokens = $this->_cut->tokenize('12=34>56=78<90=');

        $this->assertEquals
        (
            array
            (
                array('NoOp', 0, array(), '12'),
                array('=', 0, array(), '='),
                array('NoOp', 0, array(), '34'),
                array('>', 0, array(), '>'),
                array('NoOp', 1, array(), '56'),
                array('=', 1, array(), '='),
                array('NoOp', 1, array(), '78'),
                array('<', 0, array(), '<'),
                array('NoOp', 0, array(), '90'),
                array('=', 0, array(), '=')
            ),
            $tokens
        );
    }

    /**
     * @expectedException jQueryTmpl_Tokenizer_Exception
     */
    public function testShouldThrowExceptionWithNonMatchedBlocks()
    {
        $this->_cut->tokenize('12=34>56=78=90=');
    }
}

function jQueryTmpl_TokenizerTest__FactoryCallback()
{
    return func_get_args();
}

