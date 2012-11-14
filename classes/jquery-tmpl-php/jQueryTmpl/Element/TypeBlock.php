<?php

abstract class jQueryTmpl_Element_TypeBlock extends jQueryTmpl_Element_Base
{
    protected $_parser;

    public function __construct(jQueryTmpl_Parser $parser)
    {
        $this->_parser = $parser;
    }

    abstract public function parseTokens(array $tokens);
}

