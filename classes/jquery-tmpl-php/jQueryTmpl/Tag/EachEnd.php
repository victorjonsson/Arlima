<?php

class jQueryTmpl_Tag_EachEnd implements jQueryTmpl_Tag
{
    public function getTokenType()
    {
        return 'EachEnd';
    }

    public function getRegex()
    {
        return '/{{\/each}}/i';
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

