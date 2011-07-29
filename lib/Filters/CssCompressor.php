<?php
/**
 * CSS Compressor
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @copyright Copyright (C) 2011 Markus Schlegel
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Namespaces
 */
namespace Swomp\Filters;
use Swomp\Filters\FilterInterface;

/**
 * CSS Compressor
 */
class CssCompressor implements FilterInterface
{
    /**
     * @see Swomp\Filters.FilterInterface::getTypes()
     */
    public function getTypes()
    {
        return array("css");
    }

    /**
     * @see Swomp\Filters.FilterInterface::apply()
     */
    public function apply($buffer)
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