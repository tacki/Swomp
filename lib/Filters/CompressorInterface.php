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
interface CompressorInterface
{
    /**
     * Compress the given Buffer and return the Result
     * @param string $buffer
     * @return string
     */
    public function compress($buffer);
}