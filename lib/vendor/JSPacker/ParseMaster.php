<?php
/* 9 April 2008. version 1.1
*
* This is the php version of the Dean Edwards JavaScript's Packer,
* Based on :
*
* ParseMaster, version 1.0.2 (2005-08-19) Copyright 2005, Dean Edwards
* a multi-pattern parser.
* KNOWN BUG: erroneous behavior when using escapeChar with a replacement
* value that is a function
*
* packer, version 2.0.2 (2005-08-19) Copyright 2004-2005, Dean Edwards
*
* License: http://creativecommons.org/licenses/LGPL/2.1/
*
* Ported to PHP by Nicolas Martin.
*
* ----------------------------------------------------------------------
* changelog:
* 1.1 : correct a bug, '\0' packed then unpacked becomes '\'.
* ----------------------------------------------------------------------
*
* examples of usage :
* $myPacker = new JavaScriptPacker($script, 62, true, false);
* $packed = $myPacker->pack();
*
* or
*
* $myPacker = new JavaScriptPacker($script, 'Normal', true, false);
* $packed = $myPacker->pack();
*
* or (default values)
*
* $myPacker = new JavaScriptPacker($script);
* $packed = $myPacker->pack();
*
*
* params of the constructor :
* $script:       the JavaScript to pack, string.
* $encoding:     level of encoding, int or string :
*                0,10,62,95 or 'None', 'Numeric', 'Normal', 'High ASCII'.
*                default: 62.
* $fastDecode:   include the fast decoder in the packed result, boolean.
*                default : true.
* $specialChars: if you are flagged your private and local variables
*                in the script, boolean.
*                default: false.
*
* The pack() method return the compressed JavasScript, as a string.
*
* see http://dean.edwards.name/packer/usage/ for more information.
*
* Notes :
* # need PHP 5 . Tested with PHP 5.1.2, 5.1.3, 5.1.4, 5.2.3
*
* # The packed result may be different than with the Dean Edwards
*   version, but with the same length. The reason is that the PHP
*   function usort to sort array don't necessarily preserve the
*   original order of two equal member. The Javascript sort function
*   in fact preserve this order (but that's not require by the
*   ECMAScript standard). So the encoded keywords order can be
*   different in the two results.
*
* # Be careful with the 'High ASCII' Level encoding if you use
*   UTF-8 in your files...
*/
class ParseMaster {
    public $ignoreCase = false;
    public $escapeChar = '';

    // constants
    const EXPRESSION = 0;
    const REPLACEMENT = 1;
    const LENGTH = 2;

    // used to determine nesting levels
    private $GROUPS = '/\\(/';//g
    private $SUB_REPLACE = '/\\$\\d/';
    private $INDEXED = '/^\\$\\d+$/';
    private $TRIM = '/([\'"])\\1\\.(.*)\\.\\1\\1$/';
    private $ESCAPE = '/\\\./';//g
    private $QUOTE = '/\'/';
    private $DELETED = '/\\x01[^\\x01]*\\x01/';//g

    public function add($expression, $replacement = '') {
        // count the number of sub-expressions
        //  - add one because each pattern is itself a sub-expression
        $length = 1 + preg_match_all($this->GROUPS, $this->_internalEscape((string)$expression), $out);

        // treat only strings $replacement
        if (is_string($replacement)) {
            // does the pattern deal with sub-expressions?
            if (preg_match($this->SUB_REPLACE, $replacement)) {
                // a simple lookup? (e.g. "$2")
                if (preg_match($this->INDEXED, $replacement)) {
                    // store the index (used for fast retrieval of matched strings)
                    $replacement = (int)(substr($replacement, 1)) - 1;
                } else { // a complicated lookup (e.g. "Hello $2 $1")
                    // build a function to do the lookup
                    $quote = preg_match($this->QUOTE, $this->_internalEscape($replacement))
                             ? '"' : "'";
                    $replacement = array(
                        'fn' => '_backReferences',
                        'data' => array(
                            'replacement' => $replacement,
                            'length' => $length,
                            'quote' => $quote
                        )
                    );
                }
            }
        }
        // pass the modified arguments
        if (!empty($expression)) $this->_add($expression, $replacement, $length);
        else $this->_add('/^$/', $replacement, $length);
    }

    public function exec($string) {
        // execute the global replacement
        $this->_escaped = array();

        // simulate the _patterns.toSTring of Dean
        $regexp = '/';
        foreach ($this->_patterns as $reg) {
            $regexp .= '(' . substr($reg[self::EXPRESSION], 1, -1) . ')|';
        }
        $regexp = substr($regexp, 0, -1) . '/';
        $regexp .= ($this->ignoreCase) ? 'i' : '';

        $string = $this->_escape($string, $this->escapeChar);
        $string = preg_replace_callback(
            $regexp,
            array(
                &$this,
                '_replacement'
            ),
            $string
        );
        $string = $this->_unescape($string, $this->escapeChar);

        return preg_replace($this->DELETED, '', $string);
    }

    public function reset() {
        // clear the patterns collection so that this object may be re-used
        $this->_patterns = array();
    }

    // private
    private $_escaped = array();  // escaped characters
    private $_patterns = array(); // patterns stored by index

    // create and add a new pattern to the patterns collection
    private function _add() {
        $arguments = func_get_args();
        $this->_patterns[] = $arguments;
    }

    // this is the global replace function (it's quite complicated)
    private function _replacement($arguments) {
        if (empty($arguments)) return '';

        $i = 1; $j = 0;
        // loop through the patterns
        while (isset($this->_patterns[$j])) {
            $pattern = $this->_patterns[$j++];
            // do we have a result?
            if (isset($arguments[$i]) && ($arguments[$i] != '')) {
                $replacement = $pattern[self::REPLACEMENT];

                if (is_array($replacement) && isset($replacement['fn'])) {

                    if (isset($replacement['data'])) $this->buffer = $replacement['data'];
                    return call_user_func(array(&$this, $replacement['fn']), $arguments, $i);

                } elseif (is_int($replacement)) {
                    return $arguments[$replacement + $i];

                }
                $delete = ($this->escapeChar == '' ||
                           strpos($arguments[$i], $this->escapeChar) === false)
                        ? '' : "\x01" . $arguments[$i] . "\x01";
                return $delete . $replacement;

            // skip over references to sub-expressions
            } else {
                $i += $pattern[self::LENGTH];
            }
        }
    }

    private function _backReferences($match, $offset) {
        $replacement = $this->buffer['replacement'];
        $quote = $this->buffer['quote'];
        $i = $this->buffer['length'];
        while ($i) {
            $replacement = str_replace('$'.$i--, $match[$offset + $i], $replacement);
        }
        return $replacement;
    }

    private function _replace_name($match, $offset){
        $length = strlen($match[$offset + 2]);
        $start = $length - max($length - strlen($match[$offset + 3]), 0);
        return substr($match[$offset + 1], $start, $length) . $match[$offset + 4];
    }

    private function _replace_encoded($match, $offset) {
        return $this->buffer[$match[$offset]];
    }


    // php : we cannot pass additional data to preg_replace_callback,
    // and we cannot use &$this in create_function, so let's go to lower level
    private $buffer;

    // encode escaped characters
    private function _escape($string, $escapeChar) {
        if ($escapeChar) {
            $this->buffer = $escapeChar;
            return preg_replace_callback(
                '/\\' . $escapeChar . '(.)' .'/',
                array(&$this, '_escapeBis'),
                $string
            );

        } else {
            return $string;
        }
    }
    private function _escapeBis($match) {
        $this->_escaped[] = $match[1];
        return $this->buffer;
    }

    // decode escaped characters
    private function _unescape($string, $escapeChar) {
        if ($escapeChar) {
            $regexp = '/'.'\\'.$escapeChar.'/';
            $this->buffer = array('escapeChar'=> $escapeChar, 'i' => 0);
            return preg_replace_callback
            (
                $regexp,
                array(&$this, '_unescapeBis'),
                $string
            );

        } else {
            return $string;
        }
    }
    private function _unescapeBis() {
        if (isset($this->_escaped[$this->buffer['i']])
            && $this->_escaped[$this->buffer['i']] != '')
        {
             $temp = $this->_escaped[$this->buffer['i']];
        } else {
            $temp = '';
        }
        $this->buffer['i']++;
        return $this->buffer['escapeChar'] . $temp;
    }

    private function _internalEscape($string) {
        return preg_replace($this->ESCAPE, '', $string);
    }
}