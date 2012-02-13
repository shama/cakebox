<?php
/**
 * Dropbox Authorize
 *
 * Usage:
 *     $this->Auth->authorize = array(
 *         'Dropbox.Dropbox',
 *     );
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
App::uses('BaseAuthorize', 'Controller/Component/Auth');
App::uses('CakeSession', 'Model/Datasource');
class DropboxAuthorize extends BaseAuthorize {
/**
 * __construct
 * @param ComponentCollection $collection
 * @param array $settings
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->settings = Set::merge(array(
			'fields' => array(
				'dropbox_token' => 'dropbox_token',
				'dropbox_secret' => 'dropbox_secret',
				'dropbox_userid' => 'dropbox_userid',
			),
			'dropboxModel' => 'Dropbox.Dropbox',
			'dropboxCallback' => '',
			'dropboxSessionName' => '_dropbox_request_tokens',
		), $this->settings);
	}

/**
 * authorize
 * @param array $user
 * @param CakeRequest $request
 */
	public function authorize($user, CakeRequest $request) {
		$token = !empty($this->settings['fields']['dropbox_token']) ? $this->settings['fields']['dropbox_token'] : null;
		$secret = !empty($this->settings['fields']['dropbox_secret']) ? $this->settings['fields']['dropbox_secret'] : null;
		
		// Make sure token/secret exist in userModel
		if (!key_exists($token, $user) || !key_exists($secret, $user)) {
			throw new CakeException(__d('dropbox', 'Please create your dropbox_token and dropbox_secret fields in your user model.'));
			return false;
		}
		
		// Get our Drobox Model
		$Dropbox = $this->_getDropboxModel();
		
		// Did we already request authorization?
		$requestTokens = CakeSession::read($this->settings['dropboxSessionName']);
		if ($requestTokens) {
			$res = $Dropbox->requestAccess(array(
				'oauth_token' => !empty($request->query['oauth_token']) ? $request->query['oauth_token'] : '',
				'oauth_token_secret' => $requestTokens['oauth_token_secret'],
			));
			if ($res) {
				// Set our tokens
				$user[$token] = $res['oauth_token'];
				$user[$secret] = $res['oauth_token_secret'];
				
				// Save tokens into User model
				$User = $this->_getUserModel();
				$User->id = $user['id'];
				$User->saveField($token, $user[$token]);
				$User->saveField($secret, $user[$secret]);
				if (key_exists($this->settings['fields']['dropbox_userid'], $user)) {
					$User->saveField($this->settings['fields']['dropbox_userid'], $res['uid']);
				}
			}
			CakeSession::delete($this->settings['dropboxSessionName']);
		}
		
		// If token/secret empty let's get some from Dropbox
		if (empty($user[$token]) || empty($user[$secret])) {
			if (empty($this->settings['dropboxCallback'])) {
				$this->settings['dropboxCallback'] = Router::reverse($request, true);
			}
			$res = $Dropbox->requestToken($this->settings['dropboxCallback']);
			CakeSession::write($this->settings['dropboxSessionName'], $res);
			$this->_Controller->redirect($res['authorize_url']);
			return false;
		}
		
		// Give Dropbox Model our tokens
		$Dropbox->dropbox_token = $user[$token];
		$Dropbox->dropbox_secret = $user[$secret];
		return true;
	}

/**
 * Checks the Controller for the Dropbox model if not there add it
 * @return Object
 */
	protected function _getDropboxModel() {
		list($plugin, $model) = pluginSplit($this->settings['dropboxModel']);
		if (!isset($this->_Controller->{$model})) {
			$this->_Controller->loadModel($this->settings['dropboxModel']);
		}
		return $this->_Controller->{$model};
	}
	
/**
 * Checks the Controller for the User model if not there add it
 * @return Object
 */
	protected function _getUserModel() {
		list($plugin, $model) = pluginSplit($this->settings['userModel']);
		if (!isset($this->_Controller->{$model})) {
			$this->_Controller->loadModel($this->settings['userModel']);
		}
		return $this->_Controller->{$model};
	}
}