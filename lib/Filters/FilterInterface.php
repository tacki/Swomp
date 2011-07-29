<?php
/**
 * Compressor Interface
 *
 * @author Markus Schlegel <g42@gmx.net>
 * @copyright Copyright (C) 2011 Markus Schlegel
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Namespaces
 */
namespace Swomp\Filters;

/**
 * Compressor Interface
 */
interface FilterInterface
{
    /**
     * Get Types of Ressources, this Filter is applied to
     * @return array
     */
    public function getTypes();

    /**
     * Apply the Filter to the Buffer and return the Result
     * @param string $buffer
     * @return string
     */
    public function apply($buffer);
}