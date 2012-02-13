<?php
/**
 * All Tests Test
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
class AllTestsTest extends CakeTestSuite {
	public static function suite() {
		$suite = new CakeTestSuite('All Dropbox Tests');
		$suite->addTestDirectoryRecursive(CakePlugin::path('Dropbox') . 'Test' . DS . 'Case');
		return $suite;
	}
}