<?php

class jQueryTmpl_Element_ValueNotEscaped extends jQueryTmpl_Element_TypeInline implements jQueryTmpl_Element_TypeRenderable
{
    private $_token;

    public function parseToken(jQueryTmpl_Token $token)
    {
        $this->_token = $token;
    }

    public function render()
    {
        $options = $this->_token->getOptions();
        return $this->_data->getValueOf($options['name']);
    }
}

