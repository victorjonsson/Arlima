<?php

class jQueryTmpl_Markup_String implements jQueryTmpl_Markup
{
    private $_template;

    public function __construct($template)
    {
        $this->_template = (string)$template;
    }

    public function getTemplate()
    {
        return $this->_template;
    }
}

