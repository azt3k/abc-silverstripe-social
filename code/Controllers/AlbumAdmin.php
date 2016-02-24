<?php

class SocialAdmin extends Controller {

	private static $allowed_actions = array(
		'index',
		'albums',
		'htmlfragment'
	);

	public function ModuleDir() {
		return SS_EMBEDABLE_GALLERY_DIR;
	}

	public function init() {
		parent::init();
		if (!Permission::check('CMS_ACCESS')) Security::permissionFailure();
	}

	public function index() {
		return [];
	}

	public function albums() {

		// look for albums
		$q = $this->request->getVar('q');
		$r = AlbumPage::get()->where('Title LIKE \'%' . Convert::raw2sql($q) . '%\'');

		// build output
		$out = [];
		foreach ($r as $a) {
			$out[] = [
				'value' => $a->ID,
				'text' => $a->Title,
			];
		}

		$this->response->addHeader('Content-Type', 'application/json');
		return json_encode($out);
	}

	public function htmlfragment() {
		$id = (int) $this->request->param('ID');
		$a = AlbumPage::get()->filter(['ID' => $id])->first();
		return $a->renderWith('EmbeddedAlbum');
	}
}
