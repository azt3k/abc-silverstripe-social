<?php

/**
 * @todo need reconcile removals in both directions
 */
class Twitter extends Object {
	
	protected static $conf_instance;
	protected static $tmh_oauth_instance;
	protected $conf;
	protected $tmhOAuth;
	protected $errors = array();

	public static function inst() {
		$class = get_called_class();
		return new $class;
	}
	
	public function __construct() {

		$this->conf		= $this->getConf();
		$this->tmhOAuth = $this->getTmhOauth();
		
		parent::__construct();
	}
	
	public function call(array $conf = array()){

		$conf = (object) $this->array_merge_recursive_distinct(array(
			'path'		=> '1.1/statuses/user_timeline', 
			'method' 	=> 'GET', 
			'params'	=> array('count' => 200)
		), $conf );

		// call API
		$code = $this->tmhOAuth->request(
			$conf->method,
			$this->tmhOAuth->url($conf->path), 
			$conf->params
		);

		// handle result
		if ($code == 200) {
			return json_decode($this->tmhOAuth->response['response']);
		} else {
			$this->errors[] = $code." : ".$this->tmhOAuth->response['response'];
			return false;
		} 		
		
	}

	protected function getConf() {
		if (!self::$conf_instance) self::$conf_instance = SiteConfig::current_site_config();
		return self::$conf_instance;
	}
	
	protected function getTmhOauth() {
		
		if (!$this->conf) $this->conf = $this->getConf();
		
		if (!self::$tmh_oauth_instance) {
			self::$tmh_oauth_instance = new tmhOAuth(array(
				'consumer_key'		=> $this->conf->TwitterConsumerKey,
				'consumer_secret'	=> $this->conf->TwitterConsumerSecret,
			));
			self::$tmh_oauth_instance->config['user_token']		= $this->conf->TwitterOAuthToken;
			self::$tmh_oauth_instance->config['user_secret']	= $this->conf->TwitterOAuthSecret;			
		}
		
		return self::$tmh_oauth_instance;
	}	

    protected function array_merge_recursive_distinct( array $array1, array $array2 ) {

        $merged = $array1;

        foreach ( $array2 as $key => $value ) {
            if ( is_array( $value ) && isset( $merged [$key] ) && is_array( $merged [$key] ) ) {
                $merged [$key] = $this->array_merge_recursive_distinct( $merged [$key], $value );
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }	
	
}