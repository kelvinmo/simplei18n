<?php

namespace SimpleI18N;

class SimpleI18NTest extends \PHPUnit_Framework_TestCase {

/*    protected function getInstance() {
        return new MOReader('test.mo');
    }

    function testStandard() {
        $reader = $this->getInstance();

        $this->assertEquals('Standard test: LOREM IPSUM DOLOR SIT AMET.', $reader->getTranslation('Standard test: Lorem ipsum dolor sit amet.'));
    }

    function testPlural() {
        $reader = $this->getInstance();

        $original_singular = 'Plural test: %d test.';
        $original_plural = 'Plural test: %d tests.';
        $this->assertEquals('Plural test: %d TEST.', $reader->getPluralTranslation($original_singular, $original_plural, 1));
        $this->assertEquals('Plural test: %d (TWO) TESTS.', $reader->getPluralTranslation($original_singular, $original_plural, 2));
        $this->assertEquals('Plural test: %d (OTHER) TESTS.', $reader->getPluralTranslation($original_singular, $original_plural, 3));
        $this->assertEquals('Plural test: %d (EIGHT OR ELEVEN) TESTS.', $reader->getPluralTranslation($original_singular, $original_plural, 8));
    }

    function testContext() {
        $reader = $this->getInstance();

        $original = 'Context test: Lorem ipsum dolor sit amet.';

        $this->assertEquals('Context test: LOREM IPSUM DOLOR SIT AMET (NULL CONTEXT).', $reader->getTranslation($original));
        $this->assertEquals('Context test: LOREM IPSUM DOLOR SIT AMET (EMPTY CONTEXT).', $reader->getTranslation($original, ''));
        $this->assertEquals('Context test: LOREM IPSUM DOLOR SIT AMET (CONTEXT 1).', $reader->getTranslation($original, 'Context 1'));
        $this->assertEquals('Context test: LOREM IPSUM DOLOR SIT AMET (CONTEXT 2).', $reader->getTranslation($original, 'Context 2'));
    }*/
}

?>