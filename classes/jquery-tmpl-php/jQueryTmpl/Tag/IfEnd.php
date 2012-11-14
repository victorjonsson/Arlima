<?php

class jQueryTmpl_Tag_IfEnd implements jQueryTmpl_Tag
{
    public function getTokenType()
    {
        return 'IfEnd';
    }

    public function getRegex()
    {
        return '/{{\/if}}/i';
    }

    public function getNestingValue()
    {
        return array(-1,0);
    }

    public function parseTag($rawTagString)
    {
        return array();
    }
}

