<?php
/**
 * Dropbox Source Test
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
App::uses('DataSource', 'Model/Datasource');
App::uses('DropboxSource', 'Dropbox.Model/Datasource');
App::uses('Model', 'Model');
App::uses('HttpSocket', 'Network/Http');
class DropboxSourceTest extends CakeTestCase {
/**
 * Config
 *
 * @var array
 * @access public
 */
	var $config = array(
		'consumer_key' => '1234',
		'consumer_secret' => '1234',
		'cache' => 'test_dropbox_cache',
	);

/**
 * setUp
 */
	public function setUp() {
		parent::setUp();
		$this->Model = new Model();
		$this->Dropbox = new DropboxSource($this->config);
		$this->Dropbox->http = $this->getMock('HttpSocket', array('read', 'write', 'connect'), array(), 'MockHttpSocket');
	}

/**
 * tearDown
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Dropbox, $this->Model);
		Cache::clear(false, $this->config['cache']);
		ob_flush();
	}

/**
 * testRead
 */
	public function testRead() {
		$this->Dropbox->http->reset();
		
		$json = json_encode(array(
			'hash' => '773f22937f15ba834bd82ce287420422',
			'revision' => '1118637450',
			'rev' => '42ad0d8a0013b8cb',
			'thumb_exists' => false,
			'bytes' => 0,
			'path' => '/Test',
			'root' => 'dropbox',
			'is_dir' => true,
			'icon' => 'folder',
			'contents' => array(
				array(
					'revision' => '1118637450',
					'rev' => '42ad0d8a0013b8cb',
					'thumb_exists' => true,
					'bytes' => 497504,
					'path' => '/Test/2011_105.JPG',
					'root' => 'dropbox',
					'is_dir' => false,
					'icon' => 'page_white_picture',
					'mime_type' => 'image/jpeg',
				),
				array(
					'revision' => '1118637450',
					'rev' => '42ad0d8a0013b8cb',
					'thumb_exists' => true,
					'bytes' => 497504,
					'path' => '/Test/Test Two.png',
					'root' => 'dropbox',
					'is_dir' => false,
					'icon' => 'page_white_picture',
					'mime_type' => 'image/png',
				),
			),
		));
		$data = array(
			'fields' => array(
				'api' => 'metadata'
			),
			'conditions' => array(
				'path' => 'Test/File/Path',
				'file_limit' => 10,
				'include_deleted' => true,
			),
		);
		$this->Dropbox->http->connected = true;
		$serverResponse = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n" . $json;
		$this->Dropbox->http
				->expects($this->at(1))
				->method('read')
				->will($this->returnValue($serverResponse));
		$this->Dropbox->http
				->expects($this->at(2))
				->method('read')
				->will($this->returnValue(false));

		$result = $this->Dropbox->read($this->Model, $data);
		$request = $this->Dropbox->http->request;

		$this->assertEquals('/1/metadata/dropbox/Test/File/Path', $request['uri']['path']);
		$expected = array(
			'file_limit',
			'include_deleted',
			'oauth_consumer_key',
			'oauth_nonce',
			'oauth_signature',
			'oauth_signature_method',
			'oauth_timestamp',
			'oauth_token',
			'oauth_version',
		);
		$this->assertEquals($expected, array_keys($request['uri']['query']));
		$result = Set::extract('/Model/contents/path', $result);
		$expected = array(
			'/Test/2011_105.JPG',
			'/Test/Test Two.png',
		);
		$this->assertEquals($expected, $result);
	}
}