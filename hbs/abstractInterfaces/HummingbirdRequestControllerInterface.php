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
		public function unsetCooke( string $key );
		public function getCurrentUserIP();
		public function getCurrentUserIPInfo();
	}