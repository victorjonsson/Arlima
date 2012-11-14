<?php

class jQueryTmpl_Token_EachEnd extends jQueryTmpl_Token_TypeBlock
{
    public function getElementType()
    {
        return '';
    }

    public function isBlockStart()
    {
        return false;
    }

    public function getBlockEndToken()
    {
        return '';
    }
}

