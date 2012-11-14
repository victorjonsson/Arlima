<?php

class jQueryTmpl_Markup_File implements jQueryTmpl_Markup
{
    private $_template;

    public function __construct($filename)
    {
        $this->_getFileContents($filename);
    }

    public function getTemplate()
    {
        return $this->_template;
    }

    private function _getFileContents($filename)
    {
        try
        {
            $this->_template = file_get_contents($filename);
        }
        catch (Exception $e)
        {
            throw new jQueryTmpl_Markup_Exception($e->getMessage());
        }

        if ($this->_template == FALSE)
        {
            throw new jQueryTmpl_Markup_Exception("Could not open markup file '$filename'.");
        }
    }
}

