<?php

class jQueryTmpl_Parser
{
    private $_elementFactory;

    public function __construct(jQueryTmpl_Element_Factory $elementFactory)
    {
        $this->_elementFactory = $elementFactory;
    }

    public function parse($tokens)
    {
        $elements = array();
        $count = count($tokens);

        /* @var jQueryTmpl_Token_TypeBlock[]|jQueryTmpl_Token_TypeControl[] $tokens */
        for ($i=0;$i<$count;$i++)
        {
            /**
             *  If the type of token is a block we need to provide
             *  the element an array of tokens, not just one.
             */
            if ($tokens[$i] instanceof jQueryTmpl_Token_TypeBlock)
            {
                $startToken = $tokens[$i];

                if (!$startToken->isBlockStart())
                {
                    throw new jQueryTmpl_Parser_Exception('A unmatched block end tag has been encountered.');
                }

                $blockNest = $startToken->getLevel();
                $endToken = 'jQueryTmpl_Token_'.$startToken->getBlockEndToken();

                $childTokens = array();

                for ($j=$i;$j<$count;$j++)
                {
                    $childTokens[] = $tokens[$j];

                    if (($tokens[$j]->getLevel() == $blockNest) && ($tokens[$j] instanceof $endToken))
                    {
                        $i = $j;
                        break;
                    }
                }

                $elements[] = $this->_elementFactory->createBlock
                (
                    $startToken->getElementType(),
                    $childTokens
                );

                continue;
            }

            /**
             *  If the type of token is control call specific factory
             *  create method.
             */
            if ($tokens[$i] instanceof jQueryTmpl_Token_TypeControl)
            {
                $elements[] = $this->_elementFactory->createControl
                (
                    $tokens[$i]->getElementType(),
                    $tokens[$i]
                );

                continue;
            }

            /**
             *  If the type of token is inline call specific factory
             *  create method.
             */
            if ($tokens[$i] instanceof jQueryTmpl_Token_TypeInline)
            {
                $elements[] = $this->_elementFactory->createInline
                (
                    $tokens[$i]->getElementType(),
                    $tokens[$i]
                );

                continue;
            }

            throw new jQueryTmpl_Parser_Exception('Uncaught token type detected.');
        }

        return $elements;
    }
}

