<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_EachStartTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_EachStart();
    }

    public function testShouldFindTag()
    {
        $str = 'Foo bar {{each myVar}}.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '{{each myVar}}',
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldFindTagSpanningLines()
    {
        $str = "Foo bar {{each\nmyVar}}.";

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        "{{each\nmyVar}}",
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '{{eachmyVar1}} and {{EaCh myVar2 }}';

        $this->assertEquals
        (
            array
            (
                'total' => 2,
                'match' => array
                (
                    array
                    (
                        '{{eachmyVar1}}',
                        0
                    ),
                    array
                    (
                        '{{EaCh myVar2 }}',
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
            $this->_cut->parseTag('{{each myVar}}')
        );
    }

    public function testShouldExtractAdvancedVarName()
    {
        $this->assertEquals
        (
            array
            (
                'name' => "myVar.foo['bar baz'].length",
                'index' => 'idx',
                'value' =>'$val'
            ),
            $this->_cut->parseTag("{{each(idx, \$val)\nmyVar.foo['bar baz'].length }}")
        );
    }
}

