<?php
/**
 * Dropbox Source
 * DataSource for the Dropbox API
 * 
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
App::uses('OAuthSimple', 'Dropbox.Lib');
App::uses('HttpSocket', 'Network/Http');
App::uses('File', 'Utility');
class DropboxSource extends DataSource {
/**
 * description
 * @var string
 */
	public $description = 'Dropbox DataSource';

/**
 * config
 * @var array
 */
	public $config = array(
		'consumer_key' => '',
		'consumer_secret' => '',
		'token' => '',
		'token_secret' => '',
		'host' => 'api.dropbox.com',
		'version' => '1',
		'authorize_url' => 'https://www.dropbox.com/1/oauth/authorize',
		'api_content_host' => 'api-content.dropbox.com',
		'api_content_endpoints' => array(
			'files',
			'files_put',
			'thumbnails',
		),
		/**
		 * cache results
		 * true = use plugin cache
		 * false = disable cache
		 * 'cache-name' = cache config to use
		 */
		'cache' => true,
	);

/**
 * http
 * @var object
 * @access public
 */
	public $http = null;

/**
 * oauth
 * @var object
 * @access public
 */
	public $oauth = null;

/**
 * __construct
 * @param array $config
 */
	public function __construct($config) {
		$this->_init($config);
		parent::__construct($config);
	}

/**
 * init
 * Inits the socket and cache.
 *
 * @param array $config
 * @return bool
 */
	protected function _init($config = null) {
		$this->config = array_merge($this->config, (array)$config);
		$this->http = new HttpSocket();
		$this->oauth = new OAuthSimple();
		if ($this->config['cache'] === true) {
			Cache::config('dropbox', array(
				'engine'=> 'File',
				'prefix' => 'dropbox_',
				'duration' => '+1 weeks',
			));
			$this->config['cache'] = 'dropbox';
		}
		return true;
	}

/**
 * Main entry point for most get operations
 *
 * @access public
 * @param Object $Model
 * @param array $data
 * @return array
 */
	public function read($Model, $data = array()) {
		// Get tokens
		$this->config['token'] = !empty($Model->dropbox_token) ? $Model->dropbox_token : '';
		$this->config['token_secret'] = !empty($Model->dropbox_token_secret) ? $Model->dropbox_token_secret : '';
		if (empty($this->config['token']) && !empty($data['conditions']['token'])) {
			$this->config['token'] = $data['conditions']['token'];
		}
		if (empty($this->config['token_secret']) && !empty($data['conditions']['token_secret'])) {
			$this->config['token_secret'] = $data['conditions']['token_secret'];
		}
		
		// Get the api or use the model alias as api
		$api = !empty($data['fields']['api']) ? $data['fields']['api'] : Inflector::underscore($Model->alias);

		// Default conditions
		$data['conditions'] = Set::merge(array(
			'root' => 'dropbox',
			'path' => '',
		), $data['conditions']);

		// Check for required parameters
		if (in_array($api, array('fileops/move', 'fileops/copy'))) {
			if (empty($data['conditions']['from_path'])) {
				throw new Exception(_d('dropbox', 'That operation requires the parameter from_path'));
				return array();
			}
			if (empty($data['conditions']['to_path'])) {
				throw new Exception(_d('dropbox', 'That operation requires the parameter to_path'));
				return array();
			}
		}

		// Determine which Dropbox server to use
		if (in_array($api, $this->config['api_content_endpoints'])) {
			$host = $this->config['api_content_host'];
		} else {
			$host = $this->config['host'];
		}

		// Correct Path
		if (substr($data['conditions']['path'], 0, 1) == '/') {
			$data['conditions']['path'] = substr($data['conditions']['path'], 1);
		}
		$data['conditions']['path'] = rawurlencode($data['conditions']['path']);
		$data['conditions']['path'] = str_replace('%2F', '/', $data['conditions']['path']);

		// Build endpoint
		$endpoint = $api . '/' . $data['conditions']['root'] . '/' . $data['conditions']['path'];
		unset($data['conditions']['root'], $data['conditions']['path']);

		// Build OAuth Sig
		$this->oauth->reset();
		$oauth = $this->oauth->sign(array(
			'path' => 'https://' . $host . '/' . $this->config['version'] . '/' . $endpoint,
			'parameters'=> $data['conditions'],
			'signatures' => array(
				'consumer_key'	=> $this->config['consumer_key'],
				'shared_secret'	=> $this->config['consumer_secret'],
				'oauth_token'	=> $this->config['token'],
				'oauth_secret'	=> $this->config['token_secret'],
			)
		));

		// If a thumbnail just return the URL
		if ($api == 'thumbnails') {
			return $oauth['signed_url'];
		}

		// Check cache
		$res = Cache::read($endpoint, $this->config['cache']);
		if ($res === false) {
			// Hey Dropbox!
			$json = $this->_request($oauth['signed_url']);
			if ($json === false) {
				return array();
			}
			$res = json_decode($json, true);
			if (is_null($res) && !empty($json)) {
				// file download - output file content
				if (gettype($json) == 'object' && isset($json['body'])) {
					return $json->body;
				
				// json exception
				} else {
					throw new Exception(__d('dropbox', 'Error decoding json, run json_last_error() after to see the error code.'));
					return array();
				}
			}
			if ($res === false) {
				return array();
			}
			// Cache it?
			if ($this->config['cache'] !== false) {
				Cache::write($endpoint, $res, $this->config['cache']);
			}
		}
		return array($Model->alias => $res);
	}

/**
 * query
 * Give outside access to things in datasource
 * @param string $query
 * @param array $data
 * @param object $Model
 * @return mixed
 */
	public function query($query = null, $data = null, $Model = null) {
		if (strtolower($query) == 'requesttoken') {
			return $this->_requestToken(current($data));
		}
		if (strtolower($query) == 'requestaccess') {
			return $this->_requestAccess(current($data));
		}
		throw new Exception(__d('dropbox', 'Sorry, that find method is not supported.'));
	}

/**
 * listSources
 * @return boolean
 */
	public function listSources() {
		return false;
	}

/**
 * describe
 * @param object $Model
 * @return array
 */
	public function describe($Model) {
		if (isset($Model->schema)) {
			return $Model->schema;
		} else {
			return array('id' => array());
		}
	}

/**
* calculate
* Just return $func to give read() the field 'count'
* @param object $Model
* @param mixed $func
* @param array $params
* @return array
* @access public
*/
	public function calculate($Model, $func, $params = array()) {
		return $func;
	}

/**
 * _requestToken
 * Get request tokens from Dropbox
 * @param string $callback
 * @return array
 */
	protected function _requestToken($callback = 'oob') {
		if (!empty($this->config['token'])) {
			return true;
		}
		$this->oauth->reset();
		$res = $this->oauth->sign(array(
			'path' =>'https://' . $this->config['host'] . '/' . $this->config['version'] . '/oauth/request_token',
			'parameters' => array(
				'oauth_callback' => $callback,
			),
			'signatures' => array(
				'consumer_key' => $this->config['consumer_key'],
				'shared_secret' => $this->config['consumer_secret']
			)
		));
		$res = $this->_request($res['signed_url']);
		if ($res === false) {
			return false;
		}
		parse_str($res, $res);
		$res['authorize_url'] = $this->config['authorize_url'] . '?oauth_token=' . $res['oauth_token'] . '&oauth_callback=' . urlencode($callback);
		return $res;
	}

/**
 * _requestAccess
 * Request access tokens from Dropbox
 * @param array $params
 * @return array
 */
	protected function _requestAccess($params = null) {
		if (empty($params['oauth_token']) || empty($params['oauth_token_secret'])) {
			return false;
		}
		$this->oauth->reset();
		$res = $this->oauth->sign(array(
			'path' =>'https://' . $this->config['host'] . '/' . $this->config['version'] . '/oauth/access_token',
			'parameters' => array(
				'oauth_token' => $params['oauth_token'],
			),
			'signatures' => array(
				'consumer_key' => $this->config['consumer_key'],
				'shared_secret' => $this->config['consumer_secret'],
				'oauth_secret' => $params['oauth_token_secret'],
				'oauth_token' => $params['oauth_token'],
			),
		));
		$res = $this->_request($res['signed_url']);
		if ($res === false) {
			return false;
		}
		parse_str($res, $res);
		return $res;
	}

/**
 * Request a url
 * @param string $url
 * @return mixed
 */
	protected function _request($url = null) {
		$res = $this->http->request($url);
		if ($this->http->response['status']['code'] != 200) {
			$error = json_decode($res, true);
			if (empty($error['error'])) {
				$error = 'Unknown error occured.';
			} else if (is_array($error['error'])) {
				$error = implode(', ', $error['error']);
			} else {
				$error = $error['error'];
			}
			throw new Exception((string)$error);
			return false;
		}
		return $res;
	}
}