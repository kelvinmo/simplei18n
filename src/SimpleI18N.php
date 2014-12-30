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

class SimpleI18N {
    const DEFAULT_DOMAIN = 'messages';
    const LC_MESSAGES = 6;

    protected $domains = array();

    protected $current_domain = 'messages';

    protected $locale;

    private $cache;

    function __construct() {
        $this->locale = setlocale(self::LC_MESSAGES, "0");
    }

    function setLocale($locale) {
        if ($locale === 0) {
            if ($this->locale == '') return $this->setlocale('');

            return $this->locale;
        } else {
            $this->locale = $locale;
            return $this->locale;
        }
    }

    function addDomain($domain, $directory) {
        $this->domains[$domain] = $directory;
    }

    function setDomain($domain) {
        $this->currentDomain = $domain;
    }

    /**
     * Gets a translation from the current domain.
     */
    function t($original, $context = NULL) {
        return $this->dt($this->current_domain, $original, $context);
    }

    /**
     * Gets a translation from the current domain.
     */
    function nt($original_singular, $original_plural, $n, $context = NULL) {
        return $this->dnt($this->current_domain, $original_singular, $original_plural, $n, $context);
    }

    /**
     * Gets a translation from a specified domain.
     */
    function dt($domain, $original, $context = NULL) {
        $reader = $this->loadMOFile($domain);
        if (!$reader) return $original;
        return $reader->getTranslation($original, $context);
    }

    /**
     * Gets a translation from a specified domain.
     */
    function dnt($domain, $original_singular, $original_plural, $n, $context = NULL) {
        $reader = $this->loadMOFile($domain);
        if (!$reader) return ($n === 1) ? $original_singular : $original_plural;
        return $reader->getPluralTranslation($original_singular, $original_plural, $n, $context);
    }

    private function loadMOFile($domain) {
        if (!isset($this->domains[$domain])) return false;

        $filename = $this->domains[$domain] . '/' . $this->locale . '.mo';

        if (isset($this->cache[$filename])) return $this->cache[$filename];

        if (!file_exists($filename)) return false;
        try {
            $this->cache[$filename] = new MOReader($filename);
            return $this->cache[$filename];
        } catch (Exception $e) {
            return false;
        }

        return false;
    }
}

?>