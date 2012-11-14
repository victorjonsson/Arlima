<?php

class jQueryTmpl_Element_NoOp extends jQueryTmpl_Element_TypeInline implements jQueryTmpl_Element_TypeRenderable
{
    private $_token;

    public function parseToken(jQueryTmpl_Token $token)
    {
        $this->_token = $token;
    }

    public function render()
    {
        return $this->_token->getRawContent();
    }
}

