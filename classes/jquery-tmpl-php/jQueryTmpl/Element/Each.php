<?php

class jQueryTmpl_Element_Each extends jQueryTmpl_Element_TypeBlock implements jQueryTmpl_Element_TypeRenderable
{
    private $_firstToken;
    private $_elements;

    public function parseTokens(array $tokens)
    {
        // Remove first and last token
        $this->_firstToken = array_shift($tokens);
        $lastToken = array_pop($tokens);

        // Make sure we have the right types of tokens
        if (!($this->_firstToken instanceof jQueryTmpl_Token_EachStart && $lastToken instanceof jQueryTmpl_Token_EachEnd))
        {
            throw new jQueryTmpl_Element_Exception('Token mismatch, cannot create {{each}} element.');
        }

        $this->_elements = $this->_parser->parse($tokens);
    }

    public function render()
    {
        $defaultOptions = array
        (
            'index' => '$index',
            'value' => '$value'
        );
        $options = array_merge($defaultOptions, $this->_firstToken->getOptions());

        $blockData = $this->_data->getValueOf($options['name']);

        if (!(is_array($blockData) || $blockData instanceof stdClass))
        {
            // If there is no valid data for this each block it becomes nothing.
            return '';
        }

        $rendered = '';

        foreach($blockData as $index => $value)
        {
            // Make a copy of the data to use as local
            $localData = $this->_data;

            // Add our local vars to copy of data
            $localData
                ->addDataPair($options['index'], $index)
                ->addDataPair($options['value'], $value)
                ->addDataPair('this', $value);

            // Now call each element and give them the data as well.
            foreach ($this->_elements as $element)
            {
                $rendered .= $element
                    ->setData($localData)
                    ->setCompiledTemplates($this->_compiledTemplates)
                    ->render();
            }
        }

        return $rendered;
    }
}

