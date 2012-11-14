<?php

class jQueryTmpl_Element_Tmpl extends jQueryTmpl_Element_TypeInline implements jQueryTmpl_Element_TypeRenderable
{
    private $_token;

    public function parseToken(jQueryTmpl_Token $token)
    {
        $this->_token = $token;
    }

    public function render()
    {
        $options = $this->_token->getOptions();

        $elements = $this->_compiledTemplates[$options['template']];
        $localData = $this->_data->getDataSice($options['data']);

        if (empty($elements) || empty($localData))
        {
            return '';
        }

        $rendered = '';

        foreach ($elements as $element)
        {
            $rendered .= $element
                ->setData($localData)
                ->setCompiledTemplates($this->_compiledTemplates)
                ->render();
        }

        return $rendered;
    }
}

