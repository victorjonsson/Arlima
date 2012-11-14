<?php

class jQueryTmpl_Token_Factory
{
    public function create($type, $level, array $options, $rawContent)
    {
        $class = "jQueryTmpl_Token_$type";
        return new $class($level, $options, $rawContent);
    }
}

