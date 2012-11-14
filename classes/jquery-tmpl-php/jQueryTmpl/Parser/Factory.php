<?php

class jQueryTmpl_Parser_Factory
{
    public function create()
    {
        return new jQueryTmpl_Parser
        (
            new jQueryTmpl_Element_Factory()
        );
    }
}

