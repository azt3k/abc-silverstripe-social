<?php

use Facebook\Facebook;

/**
 * @author AzT3k
 */
class SocialHelper extends Object
{

    /**
     * generates a url to the current page
     * @param  boolean $dropqs [description]
     * @return string          [description]
     */
    public static function php_self($dropqs = true)
    {
        $protocol = 'http';

        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
            $protocol = 'https';
        } elseif (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443')) {
            $protocol = 'https';
        }

        $url    = sprintf('%s://%s%s', $protocol, $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']);
        $parts  = parse_url($url);
        $port   = $_SERVER['SERVER_PORT'];
        $scheme = $parts['scheme'];
        $host   = $parts['host'];
        $path   = @$parts['path'];
        $qs     = @$parts['query'];
        $port or $port = ($scheme == 'https') ? '443' : '80';

        if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }

        $url = $scheme. '://' . $host . $path;

        if (!$dropqs) {
            return "{$url}?{$qs}";
        } else {
            return $url;
        }
    }

    public static function fb_access_token()
    {
        $conf = SiteConfig::current_site_config();
        $token = null;

        // get page token
        $token = $conf->FacebookPageAccessToken;

        // if that failed get the user token
        if (!$token) {
            $token = $conf->FacebookUserAccessToken;
        }

        // if the page and user token are bad then get an app access token
        if (!$token) {
            $facebook = new Facebook(array(
                'app_id'  => $conf->FacebookAppId,
                'app_secret' => $conf->FacebookAppSecret
            ));

            $url = '/oauth/access_token' .
                    '?client_id=' . $conf->FacebookAppId .
                    '&client_secret=' . $conf->FacebookAppSecret .
                    '&grant_type=client_credentials';

            $res = $facebook->sendRequest('get', $url)->getDecodedBody();
            $token = $res['access_token'];
        }

        return $token;
    }

    /**
     * generates page links for various services
     * @param  string $id      [description]
     * @param  string $service [description]
     * @param  string $type    [description]
     * @return string          [description]
     */
    public static function link($id, $service, $type = 'user')
    {
        switch ($service) {
            case 'facebook':
                if ($type == 'user') {
                    return 'https://www.facebook.com/' . $id;
                }
                if ($type == 'page') {
                    return 'https://www.facebook.com/pages/' . $id;
                }

            case 'twitter':
                return 'https://twitter.com/' . $id;

            case 'instagram':
                return 'https://instagram.com/' . $id;

        }
        return null;
    }
}
