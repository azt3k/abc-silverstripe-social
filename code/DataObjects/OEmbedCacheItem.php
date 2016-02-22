<?php

class OEmbedCacheItem extends DataObject {

	private static $db = array(
		'URL' => 'Varchar(255)',
		'Response' => 'Text',
	);

	private static $indexes = array(
		'URL' => true
	);

	public function data() {
		return json_decode($this->Response);
	}

	public static function fetch($url) {

		// try to get the  item
		if (!$item = self::get()->filter(array('URL' => $url))->first()) {

			// fetch the oEmbed
			$raw = file_get_contents($url);
			if (!$raw) return false;

			// save it
			$item = self::create()->update(array(
				'URL' => $url,
				'Response' => $raw,
			));

			$item->write();
		}

		return $item;
	}
}
