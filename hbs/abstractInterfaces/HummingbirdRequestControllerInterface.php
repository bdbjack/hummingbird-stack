<?php
	namespace Hummingbird;

	interface HummingbirdRequestControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function getRequestMethod();
		public function getQueryByMethod( string $method = 'GET' );
		public function getQueryVarByMethod( string $method = 'GET', string $var = '' );
		public function getRequestHeaders( string $key = '' );
		public function getServerVars( string $key = '' );
		public function getCurrentURI();
		public function getCurrentPath( bool $prefix = false );
		public function getCurrentURL();
		public function getURIFromPath( string $path = '/', array $query = array() );
		public function getURLFromPath( string $path = '/', array $query = array() );
		public function getCookie( string $key, $default = null );
		public function setCookie( string $key, $value = null, $exp = null );
		public function unsetCookie( string $key );
		public function getCookieDomain();
		public function getCookiePath();
		public function isHttps();
		public function getCurrentUserIP();
		public function getCurrentUserIPInfo( string $key = '' );
		public function getCurrentUserIPGeoInfo( string $key = '' );
	}