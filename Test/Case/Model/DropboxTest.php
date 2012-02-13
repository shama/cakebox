<?php
/**
 * Dropbox Test
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('DropboxAppModel', 'Dropbox.Model');
App::uses('Dropbox', 'Dropbox.Model');
class DropboxTest extends CakeTestCase {
/**
 * setUp
 */
	public function setUp() {
		parent::setUp();
	}

/**
 * tearDown
 */
	public function tearDown() {
		parent::tearDown();
	}

/**
 * testLs
 */
	public function testLs() {
		// No parameters
		$expected = array(
			'fields' => array('api' => 'metadata'),
			'conditions' => array(),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->ls();

		// First is path
		$expected = array(
			'fields' => array('api' => 'metadata'),
			'conditions' => array(
				'path' => 'ME/Test',
			),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->ls('ME/Test');

		// First is path and second is parameters
		$expected = array(
			'fields' => array('api' => 'metadata'),
			'conditions' => array(
				'path' => 'ME/Test',
				'file_limit' => 10,
			),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->ls('ME/Test', array('file_limit' => 10));

		// Array only
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->ls(array('path' => 'ME/Test', 'file_limit' => 10));
	}

/*
 * testMkdir
 */
	public function testMkdir() {
		$expected = array(
			'fields' => array('api' => 'fileops/create_folder'),
			'conditions' => array(
				'path' => 'Create/This/Folder',
			),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->mkdir('Create/This/Folder');
	}

/*
 * testCp
 */
	public function testCp() {
		$expected = array(
			'fields' => array('api' => 'fileops/copy'),
			'conditions' => array(
				'from_path' => 'Copy/This',
				'to_path' => 'To/Here',
			),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->cp(array(
			'from_path' => 'Copy/This',
			'to_path' => 'To/Here',
		));
	}

/*
 * testRm
 */
	public function testRm() {
		$expected = array(
			'fields' => array('api' => 'fileops/delete'),
			'conditions' => array(
				'path' => 'Delete/This',
			),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->rm('Delete/This');
	}

/*
 * testMv
 */
	public function testMv() {
		$expected = array(
			'fields' => array('api' => 'fileops/move'),
			'conditions' => array(
				'from_path' => 'Move/This',
				'to_path' => 'To/Here',
			),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->mv(array(
			'from_path' => 'Move/This',
			'to_path' => 'To/Here',
		));
	}

/*
 * testDownload
 */
	public function testDownload() {
		$expected = array(
			'fields' => array('api' => 'files'),
			'conditions' => array(
				'path' => 'Download/This/File',
			),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->download('Download/This/File');
	}

/*
 * testLink
 */
	public function testLink() {
		$expected = array(
			'fields' => array('api' => 'media'),
			'conditions' => array(
				'path' => 'Get/Link/To/File',
			),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->link('Get/Link/To/File');
	}

/*
 * testAccountInfo
 */
	public function testAccountInfo() {
		$expected = array(
			'fields' => array('api' => 'account/info'),
			'conditions' => array(),
		);
		$Dropbox = $this->getMock('Dropbox', array('find'));
		$Dropbox->expects($this->once())
				->method('find')
				->with($this->equalTo('all'), $this->equalTo($expected));
		$Dropbox->account_info();
	}
}