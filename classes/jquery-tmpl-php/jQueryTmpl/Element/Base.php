<?php

abstract class jQueryTmpl_Element_Base implements jQueryTmpl_Element
{
    protected $_data;
    protected $_compiledTemplates = array();

    public function setData(jQueryTmpl_Data $data)
    {
        $this->_data = $data;
        return $this;
    }

    public function setCompiledTemplates(array $compiledTemplates)
    {
        $this->_compiledTemplates = $compiledTemplates;
        return $this;
    }
}

