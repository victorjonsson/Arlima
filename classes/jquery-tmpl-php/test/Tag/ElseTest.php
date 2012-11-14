<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_ElseTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_Else();
    }

    public function testShouldFindTag()
    {
        $str = 'Foo bar {{else myVar}}.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '{{else myVar}}',
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldFindTagSpanningLines()
    {
        $str = "Foo bar {{else\nmyVar}}.";

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        "{{else\nmyVar}}",
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '{{elsemyVar1}} and {{ElSe myVar2 }} and {{else}}';

        $this->assertEquals
        (
            array
            (
                'total' => 3,
                'match' => array
                (
                    array
                    (
                        '{{elsemyVar1}}',
                        0
                    ),
                    array
                    (
                        '{{ElSe myVar2 }}',
                        19
                    ),
                    array
                    (
                        '{{else}}',
                        40
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
            $this->_cut->parseTag('{{else myVar}}')
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
            $this->_cut->parseTag("{{else\nmyVar.foo['bar baz'].length }}")
        );
    }

    public function testShouldExtractNothingForFinalElse()
    {
        $this->assertEquals
        (
            array
            (
            ),
            $this->_cut->parseTag('{{else}}')
        );

        $this->assertEquals
        (
            array
            (
            ),
            $this->_cut->parseTag('{{else }}')
        );
    }
}

