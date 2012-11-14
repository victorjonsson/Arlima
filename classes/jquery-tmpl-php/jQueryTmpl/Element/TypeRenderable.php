<?php

/**
 *  The parser takes the tokens array of this type of elements, this
 *  array is then iterated over given data then rendered. For block
 *  level tags turned into elements this class will proabaly contain
 *  other renderable elements as well as control elements. nested
 *  within.
 *
 *  This jQueryTmpl_Element_Type is an interface unlike the other
 *  types because elements can be of any type and be made renderable.
 */
interface jQueryTmpl_Element_TypeRenderable extends jQueryTmpl_Element
{
    /**
     *  Renders the element and returns the HTML string.
     *  @return string Rendered output.
     */
    public function render();
}

