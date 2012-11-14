<?php

/**
 *  The parser takes the tokens array and produces an array of
 *  elements, more specificaly renderable elements. Each element is a
 *  object that takes a jQueryTmpl_Data object and acts upon it be it
 *  to render content or issue flow control messages.
 */
interface jQueryTmpl_Element
{
    /**
     *  Each element must take some data and then act upon it.
     *  @param jQueryTmpl_Data $data The data
     *  @return jQueryTmpl_Element Return $this to chain.
     */
    public function setData(jQueryTmpl_Data $data);

    /**
     *  Each element can optionally take an array of rendered
     *  templates. (Currently an array of elements.)
     */
    public function setCompiledTemplates(array $compiledTemplates);
}

