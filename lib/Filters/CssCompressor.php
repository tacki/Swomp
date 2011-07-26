<?php

namespace Swomp\Filters;
use Swomp\Filters\CompressorInterface;

class CssCompressor implements CompressorInterface
{
    public function compress($buffer)
    {
        // remove comments
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        // remove tabs, spaces, newlines, etc.
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
        // remove spaces before/after colons, commas, semicolons and parentheses
        $buffer = str_replace(array('; ', ' ;', ', ', ': ', ' :', '( ', ' )', '{ ', ' }'),
                              array(';', ';', ',', ':', ':', '(', ')', '{', '}'),
                              $buffer
        );


        return $buffer;
    }
}