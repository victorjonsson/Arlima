<?php

/**
 *  Tags define the jQuery tmpl tags that should be parsed out and
 *  operated on.
 */
interface jQueryTmpl_Tag
{
    /**
     *  The token type that strings matching this tag object should
     *  create. ${var} and {{=var}} should both be of type
     *  ValueEscaped however {{each foo}} and {{/each}} are two
     *  seperate token types as they have semantically different meanings.
     *  @return string Internal name for tag type.
     */
    public function getTokenType();

    /**
     *  The regex to use in order to search for this tag, within a
     *  string block. This regex should extract the entire block
     *  including opening and closing tag markers.
     *  @return string The regex string for preg_match_all()
     */
    public function getRegex();

    /**
     *  This returns an integer representing the effect on the
     *  nesting level of this tag. This may be an increase, decrease
     *  or 0 for no effect. There are two values being passed back in
     *  the array, the pre value is the amount nesting changes before
     *  the token is added, and the post is the amount nesting
     *  changes after the token has already been added.
     *  @return array(pre,post) The int amount nesting changes
     */
    public function getNestingValue();

    /**
     *  Given a entire tag this method should be able to parse out
     *  the required and optional paramiters the user specified.
     *  @param string $rawTagString The complete tag.
     *  @return array A hash of options as defined by the syntax.
     */
    public function parseTag($rawTagString);
}

