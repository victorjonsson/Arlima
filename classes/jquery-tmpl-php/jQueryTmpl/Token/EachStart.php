<?php

class jQueryTmpl_Token_EachStart extends jQueryTmpl_Token_TypeBlock
{
    public function getElementType()
    {
        return 'Each';
    }

    public function isBlockStart()
    {
        return true;
    }

    public function getBlockEndToken()
    {
        return 'EachEnd';
    }
}

