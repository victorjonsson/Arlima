<?php

class jQueryTmpl_Element_Else extends jQueryTmpl_Element_TypeControl
{
    private $_token;

    public function parseToken(jQueryTmpl_Token $token)
    {
        $this->_token = $token;
    }

    public function displayNextBlock()
    {
        $options = $this->_token->getOptions();

        if ($options == array())
        {
            // Final {{else}} tag
            return true;
        }

        return (bool)$this->_data->getValueOf($options['name']);
    }
}

