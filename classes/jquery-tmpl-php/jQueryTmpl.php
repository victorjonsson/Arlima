<?php

class jQueryTmpl
{
    private $_tokenizer;
    private $_parser;

    private $_compiledTemplates = array();
    private $_outputBuffer;

    public function __construct(jQueryTmpl_Tokenizer $tokenizer, jQueryTmpl_Parser $parser)
    {
        $this->_tokenizer = $tokenizer;
        $this->_parser = $parser;

        $this->_outputBuffer = '';
    }

    public function getHtml()
    {
        $ob = $this->_outputBuffer;
        $this->_outputBuffer = '';
        return $ob;
    }

    public function renderHtml()
    {
        echo $this->getHtml();
        return $this;
    }

    public function template($name, jQueryTmpl_Markup $markup)
    {
        $this->_compiledTemplates[$name] = $this->_compileTemplate($markup);
        return $this;
    }

    public function tmpl($nameOrMarkup, jQueryTmpl_Data $data)
    {
        if ($nameOrMarkup instanceof jQueryTmpl_Markup)
        {
            $elements = $this->_compileTemplate($nameOrMarkup);
        }
        else
        {
            $elements = $this->_compiledTemplates[$nameOrMarkup];
        }

        if (!empty($elements))
        {
            $this->_renderElements
            (
                $elements,
                $data
            );
        }
        return $this;
    }

    private function _compileTemplate(jQueryTmpl_Markup $markup)
    {
        return $this->_parser->parse
        (
            $this->_tokenizer->tokenize
            (
                $markup->getTemplate()
            )
        );
    }

    private function _renderElements(array $elements, jQueryTmpl_Data $data)
    {
        /* @var jQueryTmpl_Element_NoOp $element */
        foreach ($elements as $element)
        {
            $this->_outputBuffer .= $element->setData($data)->setCompiledTemplates($this->_compiledTemplates)->render();
        }
    }
}

