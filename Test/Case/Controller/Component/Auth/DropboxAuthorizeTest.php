<?php
/**
 * Dropbox Authorize Test
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('ComponentCollection', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('DropboxAuthorize', 'Dropbox.Controller/Component/Auth');
class DropboxAuthorizeTest extends CakeTestCase {
/**
 * settings
 * @var array
 */
	public $settings = array(
		'fields' => array(
			'dropbox_token' => 'token',
			'dropbox_secret' => 'secret',
			'dropbox_userid' => 'userid',
		),
		'dropboxModel' => 'TestDropbox',
		'dropboxCallback' => 'http://example.com/test/',
		'dropboxSessionName' => '_test_dropbox_session',
	);

/**
 * setUp
 */
	public function setUp() {
		parent::setUp();
		$Collection = new ComponentCollection();
		$this->DropboxAuthorize = new DropboxAuthorize($Collection, $this->settings);
		$this->Controller = $this->getMock('Controller', array('redirect'));
		$this->DropboxAuthorize->controller($this->Controller);
	}

/**
 * tearDown
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->DropboxAuthorize, $this->Controller);
		CakeSession::delete($this->settings['dropboxSessionName']);
	}

/**
 * testSettings
 */
	public function testSettings() {
		$settings = $this->DropboxAuthorize->settings;
		$this->assertTrue(($settings['fields']['dropbox_token'] === 'token'));
		$this->assertTrue(($settings['fields']['dropbox_secret'] === 'secret'));
		$this->assertTrue(($settings['fields']['dropbox_userid'] === 'userid'));
		$this->assertTrue(($settings['dropboxModel'] === 'TestDropbox'));
		$this->assertTrue(($settings['dropboxCallback'] === 'http://example.com/test/'));
		$this->assertTrue(($settings['dropboxSessionName'] === '_test_dropbox_session'));
	}

/**
 * testRequestToken
 */
	public function testRequestToken() {
		$user = array(
			'id' => 1,
			'username' => 'test',
			'password' => '1234',
			'token' => '',
			'secret' => '',
			'userid' => '',
		);
		$expected = array(
			'oauth_token' => '1234',
			'oauth_token_secret' => '1234',
			'authorize_url' => 'http://example.com/auth',
		);
		
		$this->DropboxAuthorize->settings['dropboxModel'] = 'TestDropbox';
		$DropboxModel = $this->getMock('Model', array('requestToken'), array(), 'TestDropbox');
		$DropboxModel->expects($this->once())
				->method('requestToken')
				->will($this->returnValue($expected));
		
		$this->Controller->expects($this->once())
				->method('redirect')
				->with($expected['authorize_url'])
				->will($this->returnValue(true));
		
		$CakeRequest = new CakeRequest();
		$this->DropboxAuthorize->authorize($user, $CakeRequest);
		
		$this->assertEquals($expected, CakeSession::read($this->settings['dropboxSessionName']));
	}

/**
 * testRequestAccess
 */
	public function testRequestAccess() {
		$user = array(
			'id' => 1,
			'username' => 'test',
			'password' => '1234',
			'token' => '',
			'secret' => '',
			'userid' => '',
		);
		$expected = array(
			'oauth_token' => 'token1234',
			'oauth_token_secret' => 'secret1234',
			'uid' => '1234',
		);
		
		CakeSession::write($this->settings['dropboxSessionName'], array(
			'oauth_token_secret' => $expected['oauth_token_secret'],
		));
		
		$CakeRequest = new CakeRequest();
		$CakeRequest->query['oauth_token'] = $expected['oauth_token'];
		
		$this->DropboxAuthorize->settings['dropboxModel'] = 'TestDropboxAccess';
		$DropboxModel = $this->getMock('Model', array('requestAccess'), array(), 'TestDropboxAccess');
		$with = $expected;
		unset($with['uid']);
		$DropboxModel->expects($this->once())
				->method('requestAccess')
				->with($with)
				->will($this->returnValue($expected));
		
		$this->DropboxAuthorize->settings['userModel'] = 'TestUser';
		$UserModel = $this->getMock('Model', array('saveField'), array(), 'TestUser');
		$UserModel->expects($this->exactly(3))
				->method('saveField');
		
		$this->DropboxAuthorize->authorize($user, $CakeRequest);
		
		$this->assertEquals('token1234', $DropboxModel->dropbox_token);
		$this->assertEquals('secret1234', $DropboxModel->dropbox_secret);
		$this->assertEquals($user['id'], $UserModel->id);
	}

/**
 * testAuthorize
 */
	public function testAuthorize() {
		$user = array(
			'id' => 1,
			'username' => 'test',
			'password' => '1234',
			'token' => 'token1234',
			'secret' => 'secret1234',
		);
		
		$this->DropboxAuthorize->settings['dropboxModel'] = 'TestDropboxAuthorize';
		$DropboxModel = $this->getMock('Model', array('requestToken'), array(), 'TestDropboxAuthorize');
		
		$CakeRequest = new CakeRequest();
		$this->DropboxAuthorize->authorize($user, $CakeRequest);
		
		$this->assertEquals('token1234', $DropboxModel->dropbox_token);
		$this->assertEquals('secret1234', $DropboxModel->dropbox_secret);
	}
}