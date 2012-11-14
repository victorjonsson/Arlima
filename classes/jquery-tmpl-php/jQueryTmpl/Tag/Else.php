<?php

class jQueryTmpl_Tag_Else implements jQueryTmpl_Tag
{
    public function getTokenType()
    {
        return 'Else';
    }

    public function getRegex()
    {
        return '/{{else.*?}}/is';
    }

    public function getNestingValue()
    {
        return array(-1,1);
    }

    public function parseTag($rawTagString)
    {
        $matches = array();
        preg_match('/^{{else(.*)}}$/is', $rawTagString, $matches);

        $name = trim($matches[1]);

        if ($name == '')
        {
            return array();
        }

        return array
        (
            'name' => trim($matches[1])
        );
    }
}

