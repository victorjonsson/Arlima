<?php

class jQueryTmpl_Token_IfStart extends jQueryTmpl_Token_TypeBlock
{
    public function getElementType()
    {
        return 'If';
    }

    public function isBlockStart()
    {
        return true;
    }

    public function getBlockEndToken()
    {
        return 'IfEnd';
    }
}

