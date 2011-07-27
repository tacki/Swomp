<?php
/**
 * JS Compressor
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @copyright Copyright (C) 2011 Markus Schlegel
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Namespaces
 */
namespace Swomp\Filters;
use Swomp\Filters\CompressorInterface;

/**
 * JS Compressor
 */
class JsCompressor implements CompressorInterface
{
    /**
     * @see Swomp\Filters.CompressorInterface::compress()
     */
    public function compress($buffer)
    {
        // remove comments
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = preg_replace('!//[^\n\r]*!', '', $buffer);

        // remove tabs
        $buffer = str_replace("\t", "", $buffer);

        // remove newlines
        $buffer = str_replace("\n", "", $buffer);

        // remove whitespaces
        $buffer = preg_replace('/(\n)\n+/', '$1', $buffer);
        $buffer = preg_replace('/(\n)\ +/', '$1', $buffer);
        $buffer = preg_replace('/(\r)\r+/', '$1', $buffer);
        $buffer = preg_replace('/(\r\n)(\r\n)+/', '$1', $buffer);
        $buffer = preg_replace('/(\ )\ +/', '$1', $buffer);

        // remove spaces before/after colons, commas, semicolons...
        $buffer = str_replace(array('; ', ' ;', ', ', ': ', ' :', '= ', ' =', '| ', " |", '& ', ' &'),
                              array(';' , ';' , ',' , ':' , ':' , '=' , '=' , '|' , "|" , '&' , '&' ),
                              $buffer
        );

        // remove spaces before/after parentheses
        $buffer = str_replace(array('( ', ' )', ' (', ') ', '{ ', ' }'),
                              array('(' , ')' , '(' , ')' , '{' , '}' ),
                              $buffer
        );

        return $buffer;
    }
}