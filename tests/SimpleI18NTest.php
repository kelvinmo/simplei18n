<?php

namespace SimpleI18N;

class SimpleI18NTest extends \PHPUnit_Framework_TestCase {

    protected function getInstance() {
        $i18n = new SimpleI18N();
        $i18n->addDomain(SimpleI18N::DEFAULT_DOMAIN, '.');
        $i18n->setLocale('test');
        return $i18n;
    }

    function testStandard() {
        $i18n = $this->getInstance();

        $this->assertEquals('Standard test: LOREM IPSUM DOLOR SIT AMET.', $i18n->t('Standard test: Lorem ipsum dolor sit amet.'));
    }

    function testPlural() {
        $i18n = $this->getInstance();

        $original_singular = 'Plural test: %d test.';
        $original_plural = 'Plural test: %d tests.';
        $this->assertEquals('Plural test: %d TEST.', $i18n->nt($original_singular, $original_plural, 1));
        $this->assertEquals('Plural test: %d (TWO) TESTS.', $i18n->nt($original_singular, $original_plural, 2));
        $this->assertEquals('Plural test: %d (OTHER) TESTS.', $i18n->nt($original_singular, $original_plural, 3));
        $this->assertEquals('Plural test: %d (EIGHT OR ELEVEN) TESTS.', $i18n->nt($original_singular, $original_plural, 8));
    }

    function testContext() {
        $i18n = $this->getInstance();

        $original = 'Context test: Lorem ipsum dolor sit amet.';

        $this->assertEquals('Context test: LOREM IPSUM DOLOR SIT AMET (NULL CONTEXT).', $i18n->t($original));
        $this->assertEquals('Context test: LOREM IPSUM DOLOR SIT AMET (EMPTY CONTEXT).', $i18n->t($original, ''));
        $this->assertEquals('Context test: LOREM IPSUM DOLOR SIT AMET (CONTEXT 1).', $i18n->t($original, 'Context 1'));
        $this->assertEquals('Context test: LOREM IPSUM DOLOR SIT AMET (CONTEXT 2).', $i18n->t($original, 'Context 2'));
    }
}

?>