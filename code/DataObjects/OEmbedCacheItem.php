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
		return $this->Response ? json_decode($this->Response) : null;
	}

	public static function fetch($conf, $nocache = false) {

		// handle embeds with no service attr
		if (empty($conf['service'])) {
			if (stripos($conf['url'], 'facebook.com') !== false)
				$conf['service'] = 'facebook';
			if (stripos($conf['url'], 'twitter.com') !== false)
				$conf['service'] = 'twitter';
			if (stripos($conf['url'], 'instagr.am') !== false || stripos($conf['url'], 'instagram.com') !== false)
				$conf['service'] = 'instagram';
		}

		// abort if we dont have what we need
		if (empty($conf['service'])) return null;

		// generate the url
		switch ($conf['service']) {

			// $type == 'video' || $type == ????
			case 'twitter':
				$type = empty($conf['type']) ? 'tweet' : $conf['type'];
				$url = 'https://api.twitter.com/1/statuses/oembed.json' .
					'?url=' . rawurlencode($conf['url']) .
					'&widget_type=' . $type;
				break;

			// $type == 'video' || $type == 'post'
			case 'facebook':
				$type = empty($conf['type']) ? 'post' : $conf['type'];
				$url = 'https://www.facebook.com/plugins/' . $type . '/oembed.json' .
					'?url=' . rawurlencode($conf['url']);
				break;

			// $type == void
			case 'instagram':
			$url = 'https://api.instagram.com/oembed' .
				'?url=' . rawurlencode($conf['url']);
				break;
		}

		// try to get the  item
		if (!$nocache) $item = self::get()->filter(array('URL' => $url))->first();

		// if the item doesn't exist or we are bypassing the cache
		if (empty($item)) {

			// omg facebook...
			// spoof the user agent header or FB packs a sad
			$options  = array(
				'http' => array(
					'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0'
				)
			);
			$context  = stream_context_create($options);

			// fetch the oEmbed
			$raw = file_get_contents($url, false, $context);
			if (!$raw) return false;

			// create item
			$item = self::create()->update(array(
				'URL' => $url,
				'Response' => $raw,
			));

			// save it if nocache isn't present
			if (!$nocache) {
				$item->write();
			}
		}

		return $item;
	}
}
