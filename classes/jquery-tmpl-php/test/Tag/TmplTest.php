<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_TmplTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_Tmpl();
    }

    public function testShouldFindTag()
    {
        $str = 'Foo bar {{tmpl "#myTemplate"}}.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '{{tmpl "#myTemplate"}}',
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldFindTagSpanningLines()
    {
        $str = "Foo bar {{tmpl\n'#myTemplate'}}.";

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        "{{tmpl\n'#myTemplate'}}",
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '{{tmpl "#myTemplate1"}} and {{TmPl "#myTemplate2" }}';

        $this->assertEquals
        (
            array
            (
                'total' => 2,
                'match' => array
                (
                    array
                    (
                        '{{tmpl "#myTemplate1"}}',
                        0
                    ),
                    array
                    (
                        '{{TmPl "#myTemplate2" }}',
                        28
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
                'template' => 'myTemplate'
            ),
            $this->_cut->parseTag('{{tmpl "#myTemplate"}}')
        );
    }

    public function testShouldExtractAdvancedVarName()
    {
        $this->assertEquals
        (
            array
            (
                'template' => "some-template-name",
                'data' => 'data'
            ),
            $this->_cut->parseTag
            (
                "{{tmpl(data) '#some-template-name'}}"
            )
        );

        $this->assertEquals
        (
            array
            (
                'template' => "some-template-name",
                'data' => 'myVar.foo["bar baz"]'
            ),
            $this->_cut->parseTag
            (
                '{{tmpl(myVar.foo["bar baz"]) "#some-template-name"}}'
            )
        );

        $this->assertEquals
        (
            array
            (
                'template' => "some-template-name",
                'data' => 'data',
                'options' =>'options'
            ),
            $this->_cut->parseTag
            (
                "{{tmpl(data, options) '#some-template-name'}}"
            )
        );

        $this->assertEquals
        (
            array
            (
                'template' => "some-template-name",
                'data' => 'myVar.foo["bar baz"]',
                'options' =>'options'
            ),
            $this->_cut->parseTag
            (
                '{{tmpl(myVar.foo["bar baz"], options) "#some-template-name"}}'
            )
        );
    }
}

