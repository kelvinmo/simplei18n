<?php

namespace SimpleI18N\Util;

class POReaderTest extends \PHPUnit_Framework_TestCase {

    protected function getInstance() {
        return new POReader('test.po');
    }

    function testPOReader() {
        $reader = $this->getInstance();
        $list = $reader->getList();

        $this->assertEquals(7, count($list));

        $this->assertEquals("Standard test: Lorem ipsum dolor sit amet.", $list[1]['msgid']);

        $this->assertEquals("Plural test: %d tests.", $list[2]['msgid_plural']);
        $this->assertEquals("Plural test: %d (EIGHT OR ELEVEN) TESTS.", $list[2]['msgstr'][3]);

        $this->assertEquals("Context 1", $list[3]['msgctxt']);

        $this->assertEquals("", $list[5]['msgctxt']);

        $this->assertFalse(isset($list[6]['msgctxt']));
    }

}

?>
