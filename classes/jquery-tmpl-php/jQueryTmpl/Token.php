<?php

/**
 *  The tokenizer produces tokens for each type of tag as well as
 *  NoOp tokens for html content.
 */
interface jQueryTmpl_Token
{
    /**
     *  Each token must be constructed with information about how far
     *  nested it is, the parsed out options array, and the raw
     *  content of the token.
     *  @param int $level Reletive level of nesting
     *  @param array $options Hash as returned by jQueryTmpl_Tags->parseTag()
     *  @param string $rawContent The entire tag or raw html content
     */
    public function __construct($level, array $options, $rawContent);

    /**
     *  The element type that should be created by this token type.
     *  Closing tags will not create elements and should return an
     *  empty string.
     *  @return string Internal name for element type.
     */
    public function getElementType();

    /**
     *  Simply gets the values set in the constructor.
     *  @return integer Level of nesting.
     */
    public function getLevel();

    /**
     *  Simply gets the values set in the constructor.
     *  @return array Hash of options.
     */
    public function getOptions();

    /**
     *  Simply gets the values set in the constructor.
     *  @return string Raw string extracted from template.
     */
    public function getRawContent();
}

