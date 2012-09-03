<?php
/**
 * DropboxApiComponent Test
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('DropboxAppModel', 'Dropbox.Model');
App::uses('Controller', 'Controller');
App::uses('Component', 'Controller');
App::uses('ComponentCollection', 'Controller');
App::uses('Dropbox', 'Dropbox.Model');
App::uses('DropboxApiComponent', 'Dropbox.Controller/Component');
class TestDropboxApiComponent extends DropboxApiComponent {
/**
 * Allow us to set protected vars for testing
 * @param string $name
 * @param mixed $value
 */
	public function __set($name, $value) {
		$prop = '_' . $name;
		if (property_exists($this, $prop)) {
			$this->{'_' . $name} = $value;
		}
	}
}
class DropboxApiComponentTest extends CakeTestCase {
/**
 * settings
 * @var array
 */
	public $settings = array(
		'fields' => array(
			'dropbox_token' => 'token',
			'dropbox_token_secret' => 'secret',
			'dropbox_userid' => 'userid',
		),
		'dropboxModel' => 'TestDropbox',
		'dropboxCallback' => 'http://example.com/test/',
		'dropboxSessionName' => '_test_dropbox_session',
		'user' => array(
			'id' => 1,
			'username' => 'test',
			'password' => '1234',
			'token' => '',
			'secret' => '',
			'userid' => '',
		),
	);

/**
 * setUp
 */
	public function setUp() {
		parent::setUp();
		$Collection = new ComponentCollection();
		$this->DropboxApi = new TestDropboxApiComponent($Collection, $this->settings);
		$this->Controller = $this->getMock('Controller', array('redirect'));
		$this->DropboxApi->initialize($this->Controller);
	}

/**
 * tearDown
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->DropboxApi, $this->Controller);
		CakeSession::delete($this->settings['dropboxSessionName']);
	}

/**
 * testSettings
 */
	public function testSettings() {
		$settings = $this->DropboxApi->settings;
		$this->assertTrue(($settings['fields']['dropbox_token'] === 'token'));
		$this->assertTrue(($settings['fields']['dropbox_token_secret'] === 'secret'));
		$this->assertTrue(($settings['fields']['dropbox_userid'] === 'userid'));
		$this->assertTrue(($settings['dropboxModel'] === 'TestDropbox'));
		$this->assertTrue(($settings['dropboxCallback'] === 'http://example.com/test/'));
		$this->assertTrue(($settings['dropboxSessionName'] === '_test_dropbox_session'));
	}

/**
 * testRequestToken
 */
	public function testRequestToken() {
		$expected = array(
			'oauth_token' => '1234',
			'oauth_token_secret' => '1234',
			'authorize_url' => 'http://example.com/auth',
		);
		$DropboxModel = $this->getMock('Dropbox', array('requestToken'), array(), 'TestDropboxRequestToken');
		$DropboxModel->expects($this->once())
				->method('requestToken')
				->will($this->returnValue($expected));
		$this->Controller->expects($this->once())
				->method('redirect')
				->with($expected['authorize_url'])
				->will($this->returnValue(true));
		$this->DropboxApi->dropboxModel = $DropboxModel;
		$this->DropboxApi->authorize();
		$this->assertEquals($expected, CakeSession::read($this->settings['dropboxSessionName']));
	}

/**
 * testRequestAccess
 */
	public function testRequestAccess() {
		$expected = array(
			'oauth_token' => 'token1234',
			'oauth_token_secret' => 'secret1234',
			'uid' => '1234',
		);
		CakeSession::write($this->settings['dropboxSessionName'], array(
			'oauth_token_secret' => $expected['oauth_token_secret'],
		));
		$this->Controller->request->query['oauth_token'] = $expected['oauth_token'];

		$DropboxModel = $this->getMock('Dropbox', array('requestAccess'), array(), 'TestDropboxRequestAccess');
		$with = $expected;
		unset($with['uid']);
		$DropboxModel->expects($this->once())
				->method('requestAccess')
				->with($with)
				->will($this->returnValue($expected));

		$UserModel = $this->getMock('Model', array('saveField'), array(), 'TestUser');
		$UserModel->expects($this->exactly(3))
				->method('saveField');

		$this->DropboxApi->dropboxModel = $DropboxModel;
		$this->DropboxApi->userModel = $UserModel;
		$this->DropboxApi->authorize();

		$this->assertEquals('token1234', $DropboxModel->dropbox_token);
		$this->assertEquals('secret1234', $DropboxModel->dropbox_token_secret);
		$this->assertEquals($this->settings['user']['id'], $UserModel->id);
	}

/**
 * testAuthorize
 */
	public function testAuthorize() {
		$DropboxModel = $this->getMock('Dropbox', array(), array(), 'TestDropboxAuthorize');
		$this->DropboxApi->dropboxModel = $DropboxModel;
		$this->DropboxApi->settings['user'] = array(
			'User' => array(
				'id' => 1,
				'username' => 'test',
				'password' => '1234',
				'token' => 'token1234',
				'secret' => 'secret1234',
			),
		);
		$this->DropboxApi->authorize();
		$this->assertEquals('token1234', $DropboxModel->dropbox_token);
		$this->assertEquals('secret1234', $DropboxModel->dropbox_token_secret);
	}
}