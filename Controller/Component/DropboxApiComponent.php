<?php
App::uses('CakeSession', 'Model/Datasource');
App::uses('AuthComponent', 'Controller/Component');

/**
 * Dropbox Api Component
 * For help generating tokens and logging into Dropbox.
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
class DropboxApiComponent extends Component {
/**
 * settings
 * @var array
 */
	public $settings = array(
		'fields' => array(
			'dropbox_token' => 'dropbox_token',
			'dropbox_token_secret' => 'dropbox_token_secret',
			'dropbox_userid' => 'dropbox_userid',
		),
		'dropboxModel' => 'Dropbox.Dropbox',
		'dropboxCallback' => '',
		'dropboxSessionName' => '_dropbox_request_tokens',
		'userModel' => 'User',
	);

/**
 * Reference to our dropboxModel
 * @var Model
 */
	protected $_dropboxModel = null;

/**
 * Reference to our userModel
 * @var Model
 */
	protected $_userModel = null;

/**
 * Reference to our Controller
 * @var Controller
 */
	protected $_Controller = null;

/**
 * __construct
 * @param ComponentCollection $Collection
 * @param array $this->settings
 */
	public function __construct(ComponentCollection $Collection, $settings = array()) {
		$settings = Set::merge($this->settings, $settings);
		parent::__construct($Collection, $settings);
	}

/**
 * initialize
 * Set the Controller
 */
	public function initialize(Controller $Controller) {
		$this->_Controller = $Controller;
		$this->_getDropboxModel();
	}

/**
 * Attempt to authorize to Dropbox
 * @return boolean
 */
	public function authorize() {
		$token = !empty($this->settings['fields']['dropbox_token']) ? $this->settings['fields']['dropbox_token'] : null;
		$secret = !empty($this->settings['fields']['dropbox_token_secret']) ? $this->settings['fields']['dropbox_token_secret'] : null;

		$user = array();
		if (isset($this->_Controller->Auth)) {
			// Get User from Auth
			$user = $this->_Controller->Auth->user();
		} else if (!empty($this->settings['user'])) {
			// If manually passed
			$user = $this->settings['user'];
		}

		// If $user has a Model alias
		if (isset($user[$this->settings['userModel']])) {
			$user = $user[$this->settings['userModel']];
		}

		// Make sure token/secret exist in userModel
		if (!key_exists($token, $user) || !key_exists($secret, $user)) {
			throw new CakeException(__d('dropbox', 'Please create your dropbox_token and dropbox_token_secret fields in your user model.'));
			return false;
		}

		// Get Controller request
		$request = $this->_Controller->request;

		// Load our Dropbox Model again in case it changed
		$this->_getDropboxModel();

		// Did we already request authorization?
		$requestTokens = CakeSession::read($this->settings['dropboxSessionName']);
		if ($requestTokens) {
			$res = $this->_dropboxModel->requestAccess(array(
				'oauth_token' => !empty($request->query['oauth_token']) ? $request->query['oauth_token'] : '',
				'oauth_token_secret' => $requestTokens['oauth_token_secret'],
			));
			if ($res) {
				// Set our tokens
				$user[$token] = $res['oauth_token'];
				$user[$secret] = $res['oauth_token_secret'];

				// Save tokens into User model
				if ($this->_getUserModel()) {
					$this->_userModel->id = $user['id'];
					$this->_userModel->saveField($token, $user[$token]);
					$this->_userModel->saveField($secret, $user[$secret]);
					if (key_exists($this->settings['fields']['dropbox_userid'], $user)) {
						$this->_userModel->saveField($this->settings['fields']['dropbox_userid'], $res['uid']);
					}
				}

				// Update Auth Session With Tokens
				$authSession = CakeSession::read(AuthComponent::$sessionKey);
				if ($authSession) {
					$authSession = Set::merge($authSession, array(
						$token => $user[$token],
						$secret => $user[$secret],
					));
					CakeSession::write(AuthComponent::$sessionKey, $authSession);
				}
			}
			CakeSession::delete($this->settings['dropboxSessionName']);
		}

		// If token/secret empty let's get some from Dropbox
		if (empty($user[$token]) || empty($user[$secret])) {
			if (empty($this->settings['dropboxCallback'])) {
				$this->settings['dropboxCallback'] = Router::reverse($request, true);
			}
			$res = $this->_dropboxModel->requestToken($this->settings['dropboxCallback']);
			CakeSession::write($this->settings['dropboxSessionName'], $res);
			$this->_Controller->redirect($res['authorize_url']);
			return false;
		}

		// Give Dropbox Model our tokens
		$this->_dropboxModel->dropbox_token = $user[$token];
		$this->_dropboxModel->dropbox_token_secret = $user[$secret];
		return true;
	}

/**
 * Pass to Dropbox Model
 * @param string $name
 * @param array $arguments
 * @return mixed
 */
	public function __call($name, $arguments) {
		if ($this->_dropboxModel instanceof Model) {
			return call_user_func_array(array($this->_dropboxModel, $name), $arguments);
		}
		return false;
	}

/**
 * Load and return the Dropbox Model
 * @return Model
 */
	protected function _getDropboxModel() {
		if ($this->settings['dropboxModel'] !== false && !$this->_dropboxModel) {
			if ($this->_Controller->loadModel($this->settings['dropboxModel'])) {
				list($plugin, $model) = pluginSplit($this->settings['dropboxModel']);
				$this->_dropboxModel = $this->_Controller->{$model};
			}
		}
		return $this->_dropboxModel;
	}

/**
 * Load and return the User model
 * @return Model
 */
	protected function _getUserModel() {
		if (!$this->_userModel) {
			$this->_userModel = ClassRegistry::init($this->settings['userModel']);
		}
		return $this->_userModel;
	}
}