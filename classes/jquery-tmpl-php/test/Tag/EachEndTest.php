<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_EachEndTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_EachEnd();
    }

    public function testShouldFindTag()
    {
        $str = 'Foo bar {{/each}}.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '{{/each}}',
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '{{/each}} and {{/EaCh}}';

        $this->assertEquals
        (
            array
            (
                'total' => 2,
                'match' => array
                (
                    array
                    (
                        '{{/each}}',
                        0
                    ),
                    array
                    (
                        '{{/EaCh}}',
                        14
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }
}

