<?php

require_once 'test/Tag/TestCase.php';

class jQueryTmpl_Tag_IfEndTest extends jQueryTmpl_Tag_TestCase
{
    private $_cut;

    public function setUp()
    {
        $this->_cut = new jQueryTmpl_Tag_IfEnd();
    }

    public function testShouldFindTag()
    {
        $str = 'Foo bar {{/if}}.';

        $this->assertEquals
        (
            array
            (
                'total' => 1,
                'match' => array
                (
                    array
                    (
                        '{{/if}}',
                        8
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }

    public function testShouldGetAllTags()
    {
        $str = '{{/if}} and {{/If}}';

        $this->assertEquals
        (
            array
            (
                'total' => 2,
                'match' => array
                (
                    array
                    (
                        '{{/if}}',
                        0
                    ),
                    array
                    (
                        '{{/If}}',
                        12
                    )
                )
            ),
            $this->_evalRegex($this->_cut->getRegex(), $str)
        );
    }
}

