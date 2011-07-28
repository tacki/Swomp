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

require(__DIR__.'../../vendor/JSPacker/JavaScriptPacker.php');

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

        if (strlen($buffer) > 1024) {
            $packer = new \JavaScriptPacker($buffer);

            return $packer->pack();
        } else {
            // dont compress files smaller than 1024 chars
            return $buffer;
        }
    }
}