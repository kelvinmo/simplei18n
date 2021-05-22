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
 * A write to write files formatted in the Gettext binary (.mo) format.
 *
 * @link http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
 */
class MOWriter {
    /** Magic number */
    const MAGIC = 0x950412de;

    /** The separator between the context and the message ID */
    const CONTEXT_SEPARATOR = "\x04";

    /** The separator between the different plural forms */
    const PLURAL_SEPARATOR = "\x00";

    /** The size of the .mo header */
    const HEADER_SIZE = 28;

    /** The size of a single entry in the offset table */
    const OFFSET_ENTRY_SIZE = 8;

    /** @var string the name of the language file */
    private $filename;

    /** @var array the translation entry list */
    private $list = null;

    /**
     * Adds a list of translation entries.
     *
     * Translation entries can be obtained by using {@link POReader::getList()}.
     *
     * @param array $list the list of translation entries
     */
    public function addList($list) {
        if ($this->list === null) {
            $this->list = $list;
        } else {
            $this->list = array_merge($this->list, $list);
        }
    }

    /**
     * Writes a MO file from the current list of translation
     * entries.
     *
     * @param string $filename the name of the file to write
     * @throws \RuntimeException is an error occurs in building the MO file
     */
    public function write($filename) {
        $mo = $this->buildMO();
        file_put_contents($filename, $mo);
    }

    /**
     * Builds a MO-formatted binary string from the current list of translation
     * entries.
     *
     * @return string the MO-formatted binary string
     * @throws \RuntimeException is an error occurs in building the MO file
     */
    public function buildMO() {
        $entry_table = $this->createEntryTable();

        $originals = array();
        $translations = array();

        // 1. Build strings
        foreach ($entry_table as $id => $i) {
            $entry =& $this->list[$i];

            $original = $id;
            if (isset($entry['msgid_plural'])) $original .= self::PLURAL_SEPARATOR . $entry['msgid_plural'];
            $originals[] = $original;

            if (is_array($entry['msgstr'])) {
                $translation = implode(self::PLURAL_SEPARATOR, $entry['msgstr']);
            } else {
                $translation = $entry['msgstr'];
            }
            $translations[] = $translation;
        }

        // 2. Calculate offsets
        $count = count($entry_table);
        $originals_pos = self::HEADER_SIZE;
        $originals_size = $translations_size = $count * self::OFFSET_ENTRY_SIZE;
        $translations_pos = $originals_pos + $originals_size;
        $strings_pos = $translations_pos + $translations_size;

        // 3. Build header
        $mo_header = pack('IIIIIII', self::MAGIC, 0, $count, $originals_pos, $translations_pos, 0, 0);

        // 4. Build originals
        $mo_original_offset_table = $mo_original_strings = '';
        foreach ($originals as $original) {
            $mo_original_strings .= $original . "\x00";
            $mo_original_offset_table .= pack('II', strlen($original), $strings_pos);
            $strings_pos += strlen($original) + 1;
        }

        // 5. Build translations
        $mo_translation_offset_table = $mo_translation_strings = '';
        foreach ($translations as $translation) {
            $mo_translation_strings .= $translation . "\x00";
            $mo_translation_offset_table .= pack('II', strlen($translation), $strings_pos);
            $strings_pos += strlen($translation) + 1;
        }

        return $mo_header . $mo_original_offset_table . $mo_translation_offset_table . $mo_original_strings . $mo_translation_strings;
    }

    /**
     * Builds and sorts the entry table.  The entry table is a mapping between
     * the ID (i.e. the context-encoded original string), and the offset in the
     * list of translation entries.  The entry table is sorted alphabetically.
     *
     * @return array the entry table
     */
    private function createEntryTable() {
        $entry_table = array();

        for ($i = 0; $i < count($this->list); $i++) {
            $entry =& $this->list[$i];
            $id = $entry['msgid'];
            if (isset($entry['msgctxt'])) $id = $entry['msgctxt'] . self::CONTEXT_SEPARATOR . $id;

            if (isset($entry_table[$id])) {
                throw new \UnexpectedValueException('Duplicate msgid: ' . $entry['msgid']);
            }

            $entry_table[$id] = $i;
        }

        ksort($entry_table, SORT_STRING);
        return $entry_table;
    }
}
?>
