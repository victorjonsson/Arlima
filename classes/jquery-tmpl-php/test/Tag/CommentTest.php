<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_CommentTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_Comment();
    }

    public function testShouldFindTag()
    {
        $str = 'Some text{{! Some Comment}} and more text.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '{{! Some Comment}}',
                        9
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldFindTagSpanningLines()
    {
        $str = "Some text{{!Some{\n}Comment{{!}} and more text.";

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        "{{!Some{\n}Comment{{!}}",
                        9
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '{{!First Comment}} and {{! Second !}}';

        $this->assertEquals
        (
            array
            (
                'total' => 2,
                'match' => array
                (
                    array
                    (
                        '{{!First Comment}}',
                        0
                    ),
                    array
                    (
                        '{{! Second !}}',
                        23
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }
}

