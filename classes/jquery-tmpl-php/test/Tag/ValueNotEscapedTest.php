<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_ValueNotEscapedTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_ValueNotEscaped();
    }

    public function testShouldFindTag()
    {
        $str = 'Foo bar {{html myVar}}.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '{{html myVar}}',
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldFindTagSpanningLines()
    {
        $str = "Foo bar {{html\nmyVar}}.";

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        "{{html\nmyVar}}",
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '{{htmlmyVar1}} and {{HtMl myVar2 }}';

        $this->assertEquals
        (
            array
            (
                'total' => 2,
                'match' => array
                (
                    array
                    (
                        '{{htmlmyVar1}}',
                        0
                    ),
                    array
                    (
                        '{{HtMl myVar2 }}',
                        19
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
            $this->_cut->parseTag('{{htmlmyVar}}')
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
            $this->_cut->parseTag("{{html\nmyVar.foo['bar baz'].length }}")
        );
    }
}

