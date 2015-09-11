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

/**
 * The main SimpleI18N class.
 *
 * Translations in SimpleI18N is organised into <em>domains</em>.
 * Each domain is physically represented by a separate directory in the file
 * system.  Domains are added to SimpleI18N using the addDomain() method.
 *
 * Within each directory is a set of language files.  Each language file is
 * named locale.mo, where locale is the name of the locale for which the file
 * contains the translated strings.  The language file is formatted using the
 * Gettext mo binary format.
 *
 * The t() and nt() methods obtains the translated strings based on the
 * <em>current domain</em>.  The current domain can be set using the setDomain()
 * method.  At instantiation, the current domain is <code>messages</code>.
 *
 * The dt() and dnt() methods can be used to obtain the translated strings from
 * another domain without changing the current domain.
 */
class SimpleI18N {
    /** The default domain string */
    const DEFAULT_DOMAIN = 'messages';

    /** The gettext constant for LC_MESSAGES */
    const LC_MESSAGES = 6;

    /**
     * @var array the domain registry - a mapping of domain names
     * and the directories where the language files can be found
     */
    protected $domains = array();

    /** @var string the current domain */
    protected $current_domain = 'messages';

    /** @var string the current locale */
    protected $locale;

    /**
     * @var array the cache of MOReader objects
     */
    private $cache;

    /**
     * Creates an instance of SimpleI18N
     */
    function __construct() {
        $this->locale = setlocale(self::LC_MESSAGES, 0);
    }

    /**
     * Sets the current locale.
     *
     * @param string|int the locale to set.  Pass 0 as an integer to obtain
     * the current locale
     * @return string the current locale
     */
    function setLocale($locale) {
        if ($locale === 0) {
            if ($this->locale != '') return $this->locale;
            return $this->setLocale($this->locale);
        } else {
            if ($locale == '') $locale = setlocale(self::LC_MESSAGES, 0);
            if ($locale == '') $locale = getenv('LANG');
            
            $this->locale = $locale;
            return $this->locale;
        }
    }

    /**
     * Adds a domain.
     *
     * @param string $domain the name of the domain
     * @param string $directory the directory where language files
     * for this domain can be found.
     */
    public function addDomain($domain, $directory) {
        $this->domains[$domain] = $directory;
    }

    /**
     * Removes a domain.
     *
     * @param string $domain the domain to remove
     */
    public function removeDomain($domain) {
        unset($this->domains[$domain]);
    }

    /**
     * Gets the current domain.
     *
     * @return string the current domain
     */
    public function getDomain() {
        return $this->current_domain;
    }

    /**
     * Sets the current domain to be used in t() and nt().
     *
     * The domain must be added first using the addDomain function.
     * If the domain has not been added, this function will return
     * false.
     *
     * @param string $domain the domain to use
     * @return bool true if the 
     */
    public function setDomain($domain) {
        if (!isset($this->domains[$domain])) return false;
        $this->current_domain = $domain;
    }

    /**
     * Obtains the translation of a string from the current domain.
     *
     * @param string $original the message ID
     * @param string|null $context the context
     * @return string the translated string, or $original if the string
     * cannot be translated
     */
    public function t($original, $context = NULL) {
        return $this->dt($this->current_domain, $original, $context);
    }

    /**
     * Obtains the translation of a string with a plural form the current
     * domain.
     *
     * @param string $original_singular the message ID
     * @param string $original_plural the plural form of $original_singular
     * @param int $count the number to determine which form is used
     * @param string|null $context the context
     * @return string the translated string, or $original_singular or
     * $original_plural if the string cannot be translated
     */
    public function nt($original_singular, $original_plural, $n, $context = NULL) {
        return $this->dnt($this->current_domain, $original_singular, $original_plural, $n, $context);
    }

    /**
     * Obtains the translation of a string from a specified domain.
     *
     * @param string $domain the domain to use
     * @param string $original the message ID
     * @param string|null $context the context
     * @return string the translated string, or $original if the string
     * cannot be translated
     */
    public function dt($domain, $original, $context = NULL) {
        $reader = $this->loadMOFile($domain);
        if (!$reader) return $original;
        return $reader->getTranslation($original, $context);
    }

    /**
     * Obtains the translation of a string with a plural form from a
     * specified domain.
     *
     * @param string $domain the domain to use
     * @param string $original_singular the message ID
     * @param string $original_plural the plural form of $original_singular
     * @param int $count the number to determine which form is used
     * @param string|null $context the context
     * @return string the translated string, or $original_singular or
     * $original_plural if the string cannot be translated
     */
    public function dnt($domain, $original_singular, $original_plural, $n, $context = NULL) {
        $reader = $this->loadMOFile($domain);
        if (!$reader) return ($n === 1) ? $original_singular : $original_plural;
        return $reader->getPluralTranslation($original_singular, $original_plural, $n, $context);
    }

    /**
     * Loads the language file for a specified domain and the
     * current locale.
     *
     * @param string $domain the domain to use
     * @return MOReader|bool the MOReader for the language file for the current
     * locale, or false if the language file cannot be read
     */
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