<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_ValueEscapedShorthandTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_ValueEscapedShorthand();
    }

    public function testShouldFindTag()
    {
        $str = 'Foo bar ${myVar}.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '${myVar}',
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldFindTagSpanningLines()
    {
        $str = "Foo bar \${\nmyVar }.";

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        "\${\nmyVar }",
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '${myVar1} not {{= myVar2 }} and ${ myVar3 }';

        $this->assertEquals
        (
            array
            (
                'total' => 2,
                'match' => array
                (
                    array
                    (
                        '${myVar1}',
                        0
                    ),
                    array
                    (
                        '${ myVar3 }',
                        32
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldExtractSimpleVarName()
    {
        $this->assertEquals
        (
            array
            (
                'name' => 'myVar'
            ),
            $this->_cut->parseTag('${myVar}')
        );
    }

    public function testShouldExtractAdvancedVarName()
    {
        $this->assertEquals
        (
            array
            (
                'name' => "myVar.foo['bar baz'].length"
            ),
            $this->_cut->parseTag("\${\nmyVar.foo['bar baz'].length }")
        );
    }
}

