<?php

class jQueryTmpl_Tag_Tmpl implements jQueryTmpl_Tag
{
    public function getTokenType()
    {
        return 'Tmpl';
    }

    public function getRegex()
    {
        return '/{{tmpl.*?}}/is';
    }

    public function getNestingValue()
    {
        return array(0,1);
    }

    public function parseTag($rawTagString)
    {
        $matches = array();
        preg_match('/^{{tmpl(\((.*?),(.*)\)(.*)|\((.*?)\)(.*)|(.*))}}$/is', $rawTagString, $matches);

        if (count($matches) == 8)
        {
            return array
            (
                'template' => $this->_extractId($matches[1])
            );
        }

        if (count($matches) == 7)
        {
            return array
            (
                'template' => $this->_extractId($matches[6]),
                'data' => trim($matches[5]),
            );
        }

        // Matched optional params as well
        return array
        (
            'template' => $this->_extractId($matches[4]),
            'data' => trim($matches[2]),
            'options' => trim($matches[3])
        );
    }

    private function _extractId($str)
    {
        return trim($str, "'\"# \t\n\r\0\x0B");
    }
}

