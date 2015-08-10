<?php

class SocialHelper extends Object {

	public static function php_self($dropqs = true) {
		$protocol = 'http';
		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
			$protocol = 'https';
		}
		elseif (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443')) {
			$protocol = 'https';
		}
		$url = sprintf('%s://%s%s', $protocol, $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']);
		$parts = parse_url($url);
		$port = $_SERVER['SERVER_PORT'];
		$scheme = $parts['scheme'];
		$host = $parts['host'];
		$path = @$parts['path'];
		$qs   = @$parts['query'];
		$port or $port = ($scheme == 'https') ? '443' : '80';
		if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
			$host = "$host:$port";
		}
		$url = "$scheme://$host$path";
		if (!$dropqs) return "{$url}?{$qs}";
		else return $url;
	}

}
