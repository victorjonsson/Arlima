<?php

class jQueryTmpl_Markup_Factory
{
    public function createFromString($str)
    {
        return new jQueryTmpl_Markup_String($str);
    }

    public function createFromFile($filename)
    {
        return new jQueryTmpl_Markup_File($filename);
    }
}

