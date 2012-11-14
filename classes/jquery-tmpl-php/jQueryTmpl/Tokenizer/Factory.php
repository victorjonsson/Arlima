<?php

class jQueryTmpl_Tokenizer_Factory
{
    public function create()
    {
        $tokenizer = new jQueryTmpl_Tokenizer
        (
            new jQueryTmpl_Token_Factory()
        );

        $tokenizer
            // Comment
            ->addTag(new jQueryTmpl_Tag_Comment())
            // Each block
            ->addTag(new jQueryTmpl_Tag_EachStart())
            ->addTag(new jQueryTmpl_Tag_EachEnd())
            // If/Else Block
            ->addTag(new jQueryTmpl_Tag_IfStart())
            ->addTag(new jQueryTmpl_Tag_Else())
            ->addTag(new jQueryTmpl_Tag_IfEnd())
            // Tmpl Tag
            ->addTag(new jQueryTmpl_Tag_Tmpl())
            // Values
            ->addTag(new jQueryTmpl_Tag_ValueEscaped())
            ->addTag(new jQueryTmpl_Tag_ValueEscapedShorthand())
            ->addTag(new jQueryTmpl_Tag_ValueNotEscaped());

        return $tokenizer;
    }
}

