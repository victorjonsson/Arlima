<?php

require_once 'jQueryTmpl.php';

abstract class jQueryTmpl_Tag_TestCase extends PHPUnit_Framework_TestCase
{
    protected function _evalRegex($regex, $string)
    {
        $matches = array();

        return array
        (
            'total' => preg_match_all($regex, $string, $matches, PREG_OFFSET_CAPTURE),
            'match' => $matches[0]
        );
    }
}

