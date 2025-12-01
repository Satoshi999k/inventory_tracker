<?php
/**
 * PHPUnit Stub File for IDE Support
 * This file is not executed, only used for IDE/Editor autocomplete
 */

namespace PHPUnit\Framework;

class TestCase {
    protected function assertTrue($condition, $message = '') {}
    protected function assertFalse($condition, $message = '') {}
    protected function assertEquals($expected, $actual, $message = '', $delta = 0) {}
    protected function assertNotEquals($expected, $actual, $message = '') {}
    protected function assertIsNotEmpty($value, $message = '') {}
    protected function assertEmpty($value, $message = '') {}
    protected function assertNull($value, $message = '') {}
    protected function assertNotNull($value, $message = '') {}
    protected function assertCount($expectedCount, $haystack, $message = '') {}
    protected function assertIsArray($value, $message = '') {}
    protected function assertIsString($value, $message = '') {}
    protected function assertIsInt($value, $message = '') {}
    protected function assertIsFloat($value, $message = '') {}
    protected function assertIsNumeric($value, $message = '') {}
    protected function assertIsIterable($value, $message = '') {}
    protected function assertIsCallable($value, $message = '') {}
    protected function assertContains($needle, $haystack, $message = '') {}
    protected function assertStringContainsString($needle, $haystack, $message = '') {}
    protected function assertArrayHasKey($key, $array, $message = '') {}
    protected function assertIsObject($value, $message = '') {}
    protected function markTestSkipped($message = '') {}
    protected function expectException($exception) {}
    public function fetch($mode = null) {}
}

class Exception extends \Exception {}
