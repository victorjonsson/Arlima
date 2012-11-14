<?php

require_once 'jQueryTmpl.php';

class jQueryTmpl_TokenTest extends PHPUnit_Framework_TestCase
{
    public function testShouldCreateEveryAvaliableToken()
    {
        $tokens = array();

        $tokens[] = new jQueryTmpl_Token_Comment(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_EachStart(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_EachEnd(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_IfStart(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_Else(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_IfEnd(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_NoOp(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_ValueEscaped(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_ValueNotEscaped(0, array(), '');
        $tokens[] = new jQueryTmpl_Token_Tmpl(0, array(), '');

        $this->assertContainsOnly
        (
            'jQueryTmpl_Token',
            $tokens
        );
    }
}

