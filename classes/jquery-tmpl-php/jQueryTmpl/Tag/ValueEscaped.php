<?php

class jQueryTmpl_Tag_ValueEscaped implements jQueryTmpl_Tag
{
    public function getTokenType()
    {
        return 'ValueEscaped';
    }

    public function getRegex()
    {
        return '/{{=.*?}}/is';
    }

    public function getNestingValue()
    {
        return array(0,0);
    }

    public function parseTag($rawTagString)
    {
        $matches = array();
        preg_match('/^{{=(.*)}}$/is', $rawTagString, $matches);

        return array
        (
            'name' => trim($matches[1])
        );
    }
}

