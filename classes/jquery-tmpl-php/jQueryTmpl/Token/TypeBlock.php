<?php

abstract class jQueryTmpl_Token_TypeBlock extends jQueryTmpl_Token_Base
{
    /**
     *  Methods below help the jQueryTmpl_Parser determin what to do
     *  when it encounters the token.
     */
    abstract public function isBlockStart();
    abstract public function getBlockEndToken();
}

