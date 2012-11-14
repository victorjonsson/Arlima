<?php

class jQueryTmpl_Tag_Comment implements jQueryTmpl_Tag
{
    public function getTokenType()
    {
        return 'Comment';
    }

    public function getRegex()
    {
        return '/{{!.*?}}/s';
    }

    public function getNestingValue()
    {
        return array(0,0);
    }

    public function parseTag($rawTagString)
    {
        return array();
    }
}

