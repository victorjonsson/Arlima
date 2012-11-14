<?php

class jQueryTmpl_Factory
{
    public function create()
    {
        $tFactory = new jQueryTmpl_Tokenizer_Factory();
        $pFactory = new jQueryTmpl_Parser_Factory();

        return new jQueryTmpl
        (
            $tFactory->create(),
            $pFactory->create()
        );
    }
}

