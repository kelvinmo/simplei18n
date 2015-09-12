<?php
/*
 * SimpleI18N
 *
 * Copyright (C) Kelvin Mo 2015
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above
 *    copyright notice, this list of conditions and the following
 *    disclaimer in the documentation and/or other materials provided
 *    with the distribution.
 *
 * 3. The name of the author may not be used to endorse or promote
 *    products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS
 * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
 * GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
 * OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */


namespace SimpleI18N\Util;

/**
 * A reader to read files formatted in the Gettext text (.po/.pot) format.
 *
 * @link http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files
 */
class POReader {
    /** @var string the name of the language file */
    private $filename;

    /** @var array the parse options */
    private $options = array(
        'use-fuzzy' => false
    );

    /** @var array the list of parsed translation entries */
    private $list = array();

    /** @var int the current line number */
    private $line_number;

    /** @var int the string buffer */
    private $_string = null;

    /** @var int the keyword buffer */
    private $_keyword = null;

    /** @var int the entry buffer */
    private $_entry = array();

    /**
     * Creates a new PO reader.
     *
     * @param string $filename the name of the file to read
     * @param array $options read options.  Currently only `use-fuzzy` is
     * supported
     * @throws Exception if there is an error in reading the file, or
     * if the file format is not supported by this reader
     */
    public function __construct($filename, $options = array()) {
        $this->filename = $filename;
        $this->options = array_merge($this->options, $options);
        $this->readFile();
    }

    /**
     * Returns a list of translation entries.
     *
     * @return array a list of translation entries
     */
    public function getList() {
        if ($this->options['use-fuzzy']) {
            return $this->list;
        } else {
            return array_filter($this->list, function($entry) {
                return (!isset($entry['flags']) || !in_array('fuzzy', $entry['flags']));
            });
        }
    }

    /**
     * Reads the PO file
     */
    private function readFile() {
        $file = @fopen($this->filename, 'r');
        if (!$file) throw new Exception("Cannot open file");

        $this->line_number = 0;
        while ($line = $this->readLine($file)) {
            $this->line_number++;
            $this->parseLine($line);
        }
        $this->flushEntry();
    }

    /**
     * Reads a line from a file with an indeterminate line length.
     */
    private function readLine($file) {
        $line = '';
        while ($s = fgets($file, 8192)) {
            $line .= $s;
            if ((substr($s, -1) == "\n") || (substr($s, -1) == "\r")) break;
        }
        return $line;
    }

    /**
     * Parses a line
     *
     * @param string $line the line to parse
     */
    private function parseLine($line) {
        $line = trim($line, " \t\n\r\0\x0B\x0C");

        if ($line == '') {
            $this->flushEntry();
        } elseif ($line[0] == '#') {
            $this->flushString();

            if ($line[1] == ',') {
                $this->entry['flags'] = preg_split('/,\s*/', trim(substr($line, 2)));
            }
        } elseif ($line[0] == '"') {
            if (substr($line, -1) != '"') {
                throw new \UnexpectedValueException("Missing \" in line " . $this->line_number);
            }
            $this->_string .= $this->parseString($line);
        } else {
            if (!is_null($this->_string)) $this->flushString();

            list($keyword, $value) = preg_split('/\s+/', $line, 2);
            $this->_keyword = $keyword;
            $this->_string = $this->parseString($value);
        }
    }

    /**
     * Unescapes a gettext string
     *
     * @param string $s the string to unescape
     * @return string the unescape string
     */
    private function parseString($s) {
        return stripcslashes(trim($s, '"'));
    }

    /**
     * Flushes the current string buffer to the current entry buffer
     */
    private function flushString() {
        if ($this->_keyword == null) throw new \UnexpectedValueException("Unexpected string in line " . $this->line_number);

        if (preg_match('/msgstr\[(\d+)\]/', $this->_keyword, $matches)) {
            if (!isset($this->_entry['msgstr'])) $this->_entry['msgstr'] = array();
            $this->_entry['msgstr'][$matches[1]] = $this->_string;
        } else {
            $this->_entry[$this->_keyword] = $this->_string;
        }

        $this->_string = '';
    }

    /**
     * Flushes the current entry buffer to the list.
     */
    private function flushEntry() {
        $this->flushString();

        if (count($this->_entry) > 0) {
            if (!isset($this->_entry['msgid'])) {
                throw new \UnexpectedValueException("Missing msgid entry in line " . $this->line_number);
            }
            if (!isset($this->_entry['msgstr'])) {
                throw new \UnexpectedValueException("Missing msgstr entry in line " . $this->line_number);
            }
            $this->list[] = $this->_entry;
            $this->_entry = array();
        }
    }
}
?>
