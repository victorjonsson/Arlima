<?php

require_once 'jQueryTmpl.php';

class jQueryTmpl_Element_TmplTest extends PHPUnit_Framework_TestCase
{
    private $_data;
    private $_elementFactory;
    private $_compiledTemplates = array();

    public function setUp()
    {
        $testData = <<<EOF
{
    "name" : "Sophia",
    "location" : "Denver, CO",
    "child" :
    {
        "name" : "Zack",
        "location" : "Portland, OR",
        "child" :
        {
            "name" : "Zoe",
            "location" : "Boston, MA"
        }
    }
}
EOF;

        $this->_data = new jQueryTmpl_Data
        (
            json_decode($testData),
            new jQueryTmpl_Data_Factory()
        );

        $this->_elementFactory = new jQueryTmpl_Element_Factory();

        $this->_compiledTemplates['person'] = array
        (
            $this->_elementFactory->createInline
            (
                'NoOp',
                new jQueryTmpl_Token_NoOp(0, array(), 'Name: ')
            ),
            $this->_elementFactory->createInline
            (
                'ValueEscaped',
                new jQueryTmpl_Token_ValueEscaped(0, array('name'=>'name'), '')
            ),
            $this->_elementFactory->createInline
            (
                'NoOp',
                new jQueryTmpl_Token_NoOp(0, array(), ' (')
            ),
            $this->_elementFactory->createInline
            (
                'ValueEscaped',
                new jQueryTmpl_Token_ValueEscaped(0, array('name'=>'location'), '')
            ),
            $this->_elementFactory->createInline
            (
                'NoOp',
                new jQueryTmpl_Token_NoOp(0, array(), ')')
            )
        );

        $this->_compiledTemplates['tree'] = array
        (
            $this->_elementFactory->createInline
            (
                'NoOp',
                new jQueryTmpl_Token_NoOp(0, array(), '<li>')
            ),
            $this->_elementFactory->createInline
            (
                'ValueEscaped',
                new jQueryTmpl_Token_ValueEscaped(0, array('name'=>'name'), '')
            ),
            $this->_elementFactory->createInline
            (
                'Tmpl',
                new jQueryTmpl_Token_Tmpl(0, array('template'=>'tree', 'data'=>'child'), '')
            ),
            $this->_elementFactory->createInline
            (
                'NoOp',
                new jQueryTmpl_Token_NoOp(0, array(), '</li>')
            )
        );
    }

    public function testShouldNotPrintNonExistantTemplate()
    {
        $element = $this->_elementFactory->createInline
        (
            'Tmpl',
            new jQueryTmpl_Token_Tmpl(0, array('template'=>'dne'), '')
        );

        $this->assertEquals
        (
            '',
            $element
                ->setData($this->_data)
                ->setCompiledTemplates($this->_compiledTemplates)
                ->render()
        );
    }

    public function testShouldPrintTemplate()
    {
        $element = $this->_elementFactory->createInline
        (
            'Tmpl',
            new jQueryTmpl_Token_Tmpl(0, array('template'=>'person'), '')
        );

        $this->assertEquals
        (
            'Name: Sophia (Denver, CO)',
            $element
                ->setData($this->_data)
                ->setCompiledTemplates($this->_compiledTemplates)
                ->render()
        );
    }

    public function testShouldPrintNestedTemplates()
    {
        $element = $this->_elementFactory->createInline
        (
            'Tmpl',
            new jQueryTmpl_Token_Tmpl(0, array('template'=>'person', 'data' => 'child'), '')
        );

        $this->assertEquals
        (
            'Name: Zack (Portland, OR)',
            $element
                ->setData($this->_data)
                ->setCompiledTemplates($this->_compiledTemplates)
                ->render()
        );

        $element = $this->_elementFactory->createInline
        (
            'Tmpl',
            new jQueryTmpl_Token_Tmpl(0, array('template'=>'person', 'data' => 'child.child'), '')
        );

        $this->assertEquals
        (
            'Name: Zoe (Boston, MA)',
            $element
                ->setData($this->_data)
                ->setCompiledTemplates($this->_compiledTemplates)
                ->render()
        );
    }

    public function testShouldPrintTemplateAndSliceData()
    {
        $element = $this->_elementFactory->createInline
        (
            'Tmpl',
            new jQueryTmpl_Token_Tmpl(0, array('template'=>'tree'), '')
        );

        $this->assertEquals
        (
            '<li>Sophia<li>Zack<li>Zoe</li></li></li>',
            $element
                ->setData($this->_data)
                ->setCompiledTemplates($this->_compiledTemplates)
                ->render()
        );
    }
}

