<?php
/**
 * Dropbox
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
class Dropbox extends DropboxAppModel {
/**
 * name
 * @var string
 */
	public $name = 'Dropbox';

/**
 * Token for Dropbox
 * @var string
 */
	public $dropbox_token = '';

/**
 * Token Secret for Dropbox
 * @var string
 */
	public $dropbox_token_secret = '';

/**
 * Retrieve a list of files
 * Dropbox API Method: /metadata
 * @return array
 */
	public function ls() {
		$args = func_get_args();
		return call_user_func_array(array($this, 'metadata'), $args);
	}

/**
 * Create a folder
 * Dropbox API Method: /fileops/create_folder
 * @return array
 */
	public function mkdir() {
		$args = func_get_args();
		return call_user_func_array(array($this, 'fileops__create_folder'), $args);
	}

/**
 * Copy a file or folder
 * Dropbox API Method: /fileops/copy
 * @return array
 * 
 * TODO: This needs to be a POST method
 */
	public function cp() {
		$args = func_get_args();
		$conds = call_user_func_array(array($this, '_parseArgs'), $args);
		$conds = array_merge(array(
			'from_path' => '',
			'to_path' => '',
		), $conds);
		return call_user_func(array($this, 'fileops__copy'), $conds);
	}

/**
 * Delete a file or folder
 * Dropbox API Method: /fileops/delete
 * @return array
 * 
 * TODO: This needs to be a POST method
 */
	public function rm() {
		$args = func_get_args();
		return call_user_func_array(array($this, 'fileops__delete'), $args);
	}

/**
 * Moves a file or folder
 * Dropbox API Method: /fileops/move
 * @return array
 * 
 * TODO: This needs to be a POST method
 */
	public function mv() {
		$args = func_get_args();
		$conds = call_user_func_array(array($this, '_parseArgs'), $args);
		$conds = array_merge(array(
			'from_path' => '',
			'to_path' => '',
		), $conds);
		return call_user_func(array($this, 'fileops__move'), $conds);
	}

/**
 * Downloads a file
 * Dropbox API Method: /files (GET)
 * @return array
 */
	public function download() {
		$args = func_get_args();
		return call_user_func_array(array($this, 'files'), $args);
	}
	
/**
 * Return a link directly to a file
 * Dropbox API Method: /media
 * @return array
 */
	public function link() {
		$args = func_get_args();
		return call_user_func_array(array($this, 'media'), $args);
	}

/**
 * Return account/info
 * Dropbox API Method: /account/info
 * @return array
 */
	public function account_info() {
		$args = func_get_args();
		return call_user_func_array(array($this, 'account__info'), $args);
	}

/**
 * Call Dropbox API methods
 * @param string $method
 * @param array $params
 * @return array
 */
	public function __call($method, $params = array()) {
		$skip = array('requesttoken', 'requestaccess');
		if (in_array(strtolower($method), $skip)) {
			return parent::__call($method, $params);
		}
		$method = str_replace('__', '/', $method);
		$conds = call_user_func_array(array($this, '_parseArgs'), $params);
		return $this->find('all', array(
			'fields' => array('api' => $method),
			'conditions' => $conds,
		));
	}

/**
 * Parse arguments given to methods
 * @return array
 */
	protected function _parseArgs() {
		$params = func_get_args();
		$conds = array();
		if (!empty($params[0])) {
			if (is_string($params[0])) {
				$conds = array('path' => $params[0]);
			} else {
				$conds = $params[0];
			}
		}
		if (!empty($params[1])) {
			$conds = (array)$params[1] + $conds;
		}
		if (!empty($conds['path'])) {
			if (substr($conds['path'], 0, 1) == '/') {
				$conds['path'] = substr($conds['path'], 1);
			}
		}
		return $conds;
	}
}