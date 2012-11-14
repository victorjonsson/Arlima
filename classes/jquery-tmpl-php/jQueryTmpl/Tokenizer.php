<?php

class jQueryTmpl_Tokenizer
{
    private $_tokenFactory;
    private $_tags = array();

    public function __construct(jQueryTmpl_Token_Factory $tokenFactory)
    {
        $this->_tokenFactory = $tokenFactory;
    }

    public function addTag(jQueryTmpl_Tag $tag)
    {
        $this->_tags[] = $tag;
        return $this;
    }

    public function tokenize($template)
    {
        $markers = $this->_findAllTokens($template);
        return $this->_createTokensFromMarkers($template, $markers);
    }

    private function _findAllTokens($template)
    {
        $markers = array();
        foreach ($this->_tags as $tag)
        {
            $matches = array();
            preg_match_all($tag->getRegex(), $template, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as $match)
            {
                $markers[$match[1]] = array
                (
                    'rawMatch' => $match[0],
                    'tokenType' => $tag->getTokenType(),
                    'nestingValue' => $tag->getNestingValue(),
                    'options' => $tag->parseTag($match[0])
                );
            }
        }

        ksort($markers);
        return $markers;
    }

    private function _createTokensFromMarkers($template, array $markers)
    {
        $tokens = array();
        $currPos = 0;
        $currNest = 0;

        foreach ($markers as $pos => $marker)
        {
            /**
             *  If next marker is beyond where we are the delta is
             *  just a NoOp token.
             */
            if ($currPos < $pos)
            {
                $tokens[] = $this->_tokenFactory->create
                (
                    'NoOp',
                    $currNest,
                    array(),
                    substr($template, $currPos, $pos-$currPos)
                );
            }

            /**
             *  Add the tag that was found as a token advancing the
             *  nesting and position as needed.
             */
            $currNest += $marker['nestingValue'][0];

            $tokens[] = $this->_tokenFactory->create
            (
                $marker['tokenType'],
                $currNest,
                $marker['options'],
                substr($template, $pos, strlen($marker['rawMatch']))
            );

            $currNest += $marker['nestingValue'][1];
            $currPos = $pos + strlen($marker['rawMatch']);
        }

        if ($currPos < strlen($template))
        {
            $tokens[] = $this->_tokenFactory->create
            (
                'NoOp',
                $currNest,
                array(),
                substr($template, $currPos)
            );
        }

        if ($currNest != 0)
        {
            throw new jQueryTmpl_Tokenizer_Exception('jQuery Template can not be tokenized, there exists an unclosed tag.');
        }

        return $tokens;
    }
}

