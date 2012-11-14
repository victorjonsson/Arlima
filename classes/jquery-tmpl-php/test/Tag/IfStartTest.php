<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_IfStartTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_IfStart();
    }

    public function testShouldFindTag()
    {
        $str = 'Foo bar {{if myVar}}.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '{{if myVar}}',
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldFindTagSpanningLines()
    {
        $str = "Foo bar {{if\nmyVar}}.";

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        "{{if\nmyVar}}",
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '{{ifmyVar1}} and {{If myVar2 }}';

        $this->assertEquals
        (
            array
            (
                'total' => 2,
                'match' => array
                (
                    array
                    (
                        '{{ifmyVar1}}',
                        0
                    ),
                    array
                    (
                        '{{If myVar2 }}',
                        17
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
            $this->_cut->parseTag('{{if myVar}}')
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
            $this->_cut->parseTag("{{if\nmyVar.foo['bar baz'].length }}")
        );
    }
}

