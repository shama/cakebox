<?php
/**
 * Dropbox Controller
 *
 * @package cakebox
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 */
class DropboxController extends DropboxAppController {
	public $name = 'Dropbox';
	public $uses = array('Dropbox.Dropbox');
	public $autoRender = false;

/**
 * Redirect to a dropbox file
 * @param string $path
 */
	public function link($path = null) {
		$path = !empty($this->request->params['named']['path']) ? $this->request->params['named']['path'] : $path;
		$path = base64_decode(urldecode($path));
		if (empty($path)) {
			throw new NotFoundException(__d('dropbox', 'Could not find a thumbnail for that file'));
		}
		try {
			$media = $this->Dropbox->media($path);
			if (!empty($media['Dropbox']['url'])) {
				$this->redirect($media['Dropbox']['url']);
			}
			throw new NotFoundException($path . ' not found');
		} catch (Exception $e) {
			throw new NotFoundException($e->getMessage());
		}
	}
}