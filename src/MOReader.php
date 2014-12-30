<?php
/*
 * SimpleI18N
 *
 * Copyright (C) Kelvin Mo 2014
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


namespace SimpleI18N;

class MOReader {

    const MAGIC_BIG_ENDIAN = "\x95\x04\x12\xde";
    const MAGIC_LITTLE_ENDIAN = "\xde\x12\x04\x95";
    const CONTEXT_SEPARATOR = "\x04";
    const PLURAL_SEPARATOR = "\x00";

    const ORIGINAL_ENTRY = 0;
    const TRANSLATION_ENTRY = 1;

    const INDEX_ORIGINAL_PLURALS = 0;
    const INDEX_TRANSLATION_SINGULAR = 1;
    const INDEX_TRANSLATION_PLURALS = 2;

    private $filename;
    private $endianess;

    private $table = array();
    private $plurals_count = 2;
    private $plural_test_expr = null;
    private $charset;

    public function __construct($filename) {
        $this->filename = $filename;
        $this->readFile();
    }

    private function readFile() {
        $file = @fopen($this->filename, 'r');
        if (!$file) throw new Exception("Cannot open file");
        
        // 1. Read MO header
        $buf = fread($file, 4);
        if ($buf == self::MAGIC_BIG_ENDIAN) {
            $this->endianess = 'N';
        } elseif ($buf == self::MAGIC_LITTLE_ENDIAN) {
            $this->endianess = 'V';
        } else {
            throw new Exception("Not a gettext MO file");
        }

        extract($this->readInts($file, 'revision', 'count', 'originals_pos', 'translations_pos', 'hash_size', 'hash_pos'));

        // support revision 0 of MO format specs only
        if ($revision != 0) throw new Exception("Unsupported MO file version");

        // 2. Read offset tables
        $offset_table = array();
        fseek($file, $originals_pos, SEEK_SET);
        $this->readOffsetTable($file, $count, self::ORIGINAL_ENTRY, $offset_table);
        fseek($file, $translations_pos, SEEK_SET);
        $this->readOffsetTable($file, $count, self::TRANSLATION_ENTRY, $offset_table);

        // 3. Read strings
        $strings = array();
        foreach ($offset_table as $pos => $entry) {
            list($i, $type, $length) = $entry;

            fseek($file, $pos, SEEK_SET);
            $buf = ($length > 0) ? fread($file, $length) : '';

            if (!isset($strings[$i])) $strings[$i] = array();
            $strings[$i][$type] = $buf;
        }

        // 5. Parse strings table
        foreach ($strings as $string) {
            $original = $string[self::ORIGINAL_ENTRY];
            $translation = $string[self::TRANSLATION_ENTRY];

            if ($original == '') {
                $this->parsePOHeaders($translation);
            } else {
                $this->parseTranslation($original, $translation);
            }
        }

        unset($strings);
    }

    function getTranslation($original, $context = null) {
        $key = $this->getTableKey($original, $context);

        if (isset($this->table[$key])) {
            if (isset($this->table[$key][self::INDEX_TRANSLATION_SINGULAR]))
                return $this->table[$key][self::INDEX_TRANSLATION_SINGULAR];
        }

        return $original;
    }

    function getPluralTranslation($original_singular, $original_plural, $count, $context = null) {
        $key = $this->getTableKey($original_singular, $context);

        if (isset($this->table[$key])) {
            $index = $this->selectPlural($count);
            if (($index == 0) && isset($this->table[$key][self::INDEX_TRANSLATION_SINGULAR])) {
                return $this->table[$key][self::INDEX_TRANSLATION_SINGULAR];
            } elseif (($index > 0) && ($index < $this->nplurals)) {
                $index--;
                if (isset($this->table[$key][self::INDEX_TRANSLATION_PLURALS][$index]))
                    return $this->table[$key][self::INDEX_TRANSLATION_PLURALS][$index];
            }
        }
 
        return ($count == 1) ? $original_singular : $original_plural;
    }

    private function parsePOHeaders($po_headers) {
        foreach (explode("\n", $po_headers) as $po_header) {
            if (mb_strpos($po_header, ':', 0, 'ASCII') === false) continue;

            list ($header, $values) = explode(':', $po_header, 2);

            $header = mb_strtolower($header, 'ASCII');

            if ($header = 'plural-forms') {
                foreach (explode(';', $values) as $value) {
                    if (preg_match('/^nplurals\s*=\s*(\d+)$/i', trim($value), $matches)) {
                        $this->nplurals = intval($matches[1]);
                    } elseif (preg_match('@^plural\s*=([ a-zA-Z0-9_:;\(\)\?\|\&=!<>+*/\%-]+)$@i', trim($value), $matches)) {
                        $this->plural_test_expr = $this->convertTernaryOperator($matches[1]);
                    }
                }
            } elseif ($header = 'content-type') {
                foreach (explode(';', $values) as $value) {
                    if (preg_match('/^charset\s*=\s*(\S+)$/i', trim($value), $matches)) {
                        $this->charset = $matches[1];
                    }
                }
            }
        }
    }

    private function convertTernaryOperator($expr) {
        // Add parenthesis for tertiary '?' operator.
        $expr .= ';';
        $result = '';
        $level = 0;
        for ($i = 0; $i < strlen($expr); $i++) {
            $c = $expr[$i];
            switch ($c) {
            case '?':
                $result .= ' ? (';
                $level++;
                break;
            case ':':
                $result .= ') : (';
                break;
            case ';':
                $result .= str_repeat(')', $level) . ';';
                $level = 0;
                break;
            default:
                $result .= $c;
            }
        }
        return $result;
  }

    private function parseTranslation($original, $translation) {
        $context_pos = mb_strpos($original, self::CONTEXT_SEPARATOR, 0, 'ASCII');
        if ($context_pos === false) {
            $context = null;
        } else {
            $context = mb_substr($original, 0, $context_pos, 'ASCII');
            $original = mb_substr($original, $context_pos + 1, NULL, 'ASCII');
        }

        $original_plurals = explode(self::PLURAL_SEPARATOR, $original);
        $original_singular = array_shift($original_plurals);

        $translation_plurals = explode(self::PLURAL_SEPARATOR, $translation);
        $translation_singular = array_shift($translation_plurals);
        
        $this->table[$this->getTableKey($original_singular, $context)] = array($original_plurals, $translation_singular, $translation_plurals);
    }


    private function selectPlural($count) {
        $n = intval($count);
        if ($this->plural_test_expr == null) return ($count == 1) ? 0 : 1;
        return intval(eval('return ' . str_replace('n', '$n', $this->plural_test_expr)));
    }

    private function getTableKey($original, $context) {
        return ($context === null) ? $original : $context . self::CONTEXT_SEPARATOR . $original;
    }

    private function readOffsetTable($file, $count, $entry_type, &$table) {
        for ($i = 0; $i < $count; $i++) {
            $entry = $this->readInts($file, 'length', 'pos');
            $offsets[] = array($entry['length'], $entry['pos']);
            $table[$entry['pos']] = array($i, $entry_type, $entry['length']);
        }
    }

    private function readInts() {
        $endianess = $this->endianess;
        $args = func_get_args();
        $file = array_shift($args);        
        $elements = array_map(function($e) use ($endianess) { return $endianess . $e; }, $args);
        $format = implode('/', $elements);
        return unpack($format, fread($file, 4 * count($args)));
    }
}
?>