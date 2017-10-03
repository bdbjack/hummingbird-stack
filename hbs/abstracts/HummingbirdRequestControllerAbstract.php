<?php
	namespace Hummingbird;

	abstract class HummingbirdRequestControllerAbstract implements \Hummingbird\HummingbirdRequestControllerInterface {
		protected  $hba;
		private $_method = 'GET';
		private $_protocol = 'HTTP/1.0';
		private $_scheme = 'http';
		private $_agent = '';
		private $_get = array();
		private $_post = array();
		private $_put = array();
		private $_delete = array();
		private $_head = array();
		private $_patch = array();
		private $_cli = array();
		private $_cookies = array();
		private $__server = array();
		private $__headers = array();
		private $_ip;

		function __construct( \Hummingbird\HummingbirdApp $hba ) {
			$this->hba = $hba;
			$this->__server = $_SERVER;
			$this->__headers = $this->parse_http_headers();
			$this->_method = __hba_get_array_key( 'REQUEST_METHOD', $this->__server, 'GET' );
			$this->_protocol = __hba_get_array_key( 'SERVER_PROTOCOL', $this->__server, 'HTTP/1.0' );
			$this->_agent = __hba_get_array_key( 'HTTP_USER_AGENT', $this->__server, '' );
			$this->_get = $this->parse_http_method_data( 'GET' );
			$this->_post = $this->parse_http_method_data( 'POST' );
			$this->_put = $this->parse_http_method_data( 'PUT' );
			$this->_patch = $this->parse_http_method_data( 'PATCH' );
			$this->_delete = $this->parse_http_method_data( 'DELETE' );
			$this->_head = $this->parse_http_method_data( 'HEAD' );
			$this->_options = $this->parse_http_method_data( 'OPTIONS' );
			$this->_cookies = $_COOKIE;
			if ( ! __hba_is_empty( __hba_get_array_key( 'HTTP_CF_VISITOR', $this->__server ) ) ) {
				$cfv = json_decode( __hba_get_array_key( 'HTTP_CF_VISITOR', $this->__server ) );
			}
			else {
				$cfv = new \stdClass();
			}
			switch ( true ) {
				case ( 'on' == __hba_get_array_key( 'HTTPS', $this->__server, 'off' ) ):
					$this->_scheme = 'https';
					break;

				case ( 'https' == strtolower( __hba_get_array_key( 'HTTP_X_FORWARDED_PROTO', $this->__server, 'http' ) ) ):
					$this->_scheme = 'https';
					break;

				case ( 'https' == strtolower( __hba_get_array_key( 'REQUEST_SCHEME', $this->__server, 'http' ) ) ):
					$this->_scheme = 'https';
					break;

				case ( is_object( $cfv ) && property_exists( $cfv, 'scheme' ) && 'https' == strtolower( $cfv->scheme ) ):
					$this->_scheme = 'https';
					break;

				default:
					$this->_scheme = 'http';
					break;
			}
			$this->_cli = __hba_get_array_key( 'query', $this->getCLIInfo(), array() );
			$this->_ip = new \Hummingbird\HummingbirdIPInterface();
			$this->_ip = $this->hba->doFilter( 'filterIP', $this->_ip );
			if ( __hba_is_cli() ) {
				$this->_method = 'CLI';
				$this->_protocol = 'PHP';
				$this->_scheme = 'php';
			}
		}

		function getRequestMethod() {
			return $this->_method;
		}

		function getQueryByMethod( string $method = 'GET' ) {
			$m = strtolower( $method );
			while( '$' == substr( $m, 0, 1 ) ) {
				$m = substr( $m, 1 );
			}
			while( '_' == substr( $m, 0, 1 ) ) {
				$m = substr( $m, 1 );
			}
			$varname = sprintf( '_%s', $m );
			return $this->{ $varname };
		}

		function getQueryVarByMethod( string $method = 'GET', string $var = '' ) {
			$query = $this->getQueryByMethod( $method );
			return __hba_get_array_key( $var, $query, '' );
		}

		function getRequestHeaders( string $key = '' ) {
			if ( __hba_is_empty( $key ) ) {
				return $this->__headers;
			}
			return __hba_get_array_key( $key, $this->__headers, '' );
		}

		function getServerVars( string $key = '' ) {
			if ( __hba_is_empty( $key ) ) {
				return $this->__server;
			}
			return __hba_get_array_key( $key, $this->__server, '' );
		}

		function getCurrentURI() {
			$baseUri = $this->_get_base_uri();
			return __hba_get_array_key( 'REQUEST_URI', $this->__server, $baseUri );
		}

		function getCurrentPath( bool $prefix = false ) {
			if ( true == __hba_is_cli() ) {
				return $this->getCLICurrentPath();
			}
			$baseUri = \Hummingbird\HummingbirdApp::_hba_strip_trailing_slash( $this->_get_base_uri() );
			$currentUri = $this->getCurrentURI();
			$path = substr( $currentUri, strlen( $baseUri ) );
			if ( false !== strpos( $path, '?' ) ) {
				list( $path, $query ) = explode( '?', $path );
			}
			if ( true == $prefix ) {
				$path = '.' . $path;
			}
			return $path;
		}

		function getCurrentURL() {
			if ( true == __hba_is_cli() ) {
				return $this->getCLICurrentURL();
			}
			$scheme = $this->_scheme;
			$host = $this->_get_hostname();
			$uri = $this->getCurrentURI();
			return sprintf( '%s://%s/%s', $scheme, $host, \Hummingbird\HummingbirdApp::_hba_strip_leading_slash( $uri ) );
		}

		function getURIFromPath( string $path = '/', array $query = array() ) {
			if ( true == __hba_is_cli() ) {
				return $this->getCLIURIFromPath( $path, $query );
			}
			$baseUri = $this->_get_base_uri();
			$return = sprintf( '%s/%s', \Hummingbird\HummingbirdApp::_hba_strip_trailing_slash( $baseUri ), \Hummingbird\HummingbirdApp::_hba_strip_leading_slash( $path ) );
			if ( __hba_can_loop( $query ) ) {
				$return = sprintf( '%s?%s', $return, http_build_query( $query ) );
			}
			return $return;
		}

		function getURLFromPath( string $path = '/', array $query = array() ) {
			if ( true == __hba_is_cli() ) {
				return $this->getCLIURLFromPath( $path, $query );
			}
			$scheme = $this->_scheme;
			$host = $this->_get_hostname();
			$uri = $this->getURIFromPath( $path, $query );
			return sprintf( '%s://%s/%s', $scheme, $host, \Hummingbird\HummingbirdApp::_hba_strip_leading_slash( $uri ) );
		}

		function getCookie( string $key, $default = null ) {
			return __hba_get_array_key( $key, $this->_cookies, $default );
		}

		function setCookie( string $key, $value = null, $exp = null ) {
			if ( is_null( $exp ) || ! is_numeric( $exp ) ) {
				$exp = time() + ( 86400 * 30 );
			}
			$_COOKIE[ $key ] = $value;
			$this->_cookies[ $key ] = $value;
			if ( headers_sent() ) {
				return true;
			}
			else {
				return setcookie( $key, $value, $exp, $this->getCookiePath(), $this->getCookieDomain(), false, false );
			}
		}

		function unsetCookie( string $key ) {
			$exp = time() - 3600;
			$this->setCookie( $key, '', $exp );
		}

		function getCurrentUserIP() {
			return $this->_ip->ip();
		}

		function getCurrentUserIPInfo( string $key = '' ) {
			return $this->_ip->get( $key );
		}

		function getCurrentUserIPGeoInfo( string $key = '' ) {
			return $this->_ip->getGeo( $key );
		}

		function getCookieDomain() {
			$host = $this->_get_hostname();
			if ( ! __hba_is_ip( $host ) ) {
				$host = '.' . $host;
			}
			$portstop = strpos( $host, ':' );
			if ( false !== $portstop ) {
				$host = substr( $host, 0, $portstop );
			}
			return $host;
		}

		function getCookiePath() {
			return ( __hba_is_cli() ) ? '/' : $this->_get_base_uri();
		}

		function isHttps() {
			return ( 'https' == $this->_scheme );
		}

		public function __get( string $name ) {
			return array();
		}

		public function __set( string $name, $value ) {
			return false;
		}

		public function __isset( string $name ) {
			return false;
		}

		public function __unset( string $name ) {
			return false;
		}

		public function __call( string $name, array $arguments = array() ) {
			return false;
		}

		public static function __callStatic( string $name, array $arguments = array() ) {
			return false;
		}

		private function _get_base_uri() {
			$absScript = $this->_get_current_script_absolute();
			$absRoot = $this->_get_document_root();
			$relativeScript = substr( $absScript, strlen( $absRoot ) );
			$lastSlashRPos = strrpos( $relativeScript, '/' );
			return substr( $relativeScript, 0, $lastSlashRPos + 1 );
		}

		private function _get_current_script_absolute() {
			if ( ! __hba_is_empty( __hba_get_array_key( 'SCRIPT_FILENAME', $this->__server ) ) ) {
				return __hba_get_array_key( 'SCRIPT_FILENAME', $this->__server );
			}
			$root = $this->_get_document_root();
			if ( __hba_is_empty( $root ) ) {
				return false;
			}
			$script = __hba_get_array_key( 'SCRIPT_NAME', $this->__server );
			if ( __hba_is_empty( $script ) ) {
				$script = __hba_get_array_key( 'PHP_SELF', $this->__server );
			}
			if ( __hba_is_empty( $script ) ) {
				return false;
			}
			return sprintf( '%s/%s', \Hummingbird\HummingbirdApp::_hba_strip_trailing_slash( $root ), \Hummingbird\HummingbirdApp::_hba_strip_leading_slash( $script ) );
		}

		private function _get_document_root() {
			$root = __hba_get_array_key( 'DOCUMENT_ROOT', $this->__server );
			if ( __hba_is_empty( $root ) ) {
				$root = __hba_get_array_key( 'CONTEXT_DOCUMENT_ROOT', $this->__server );
			}
			if ( __hba_is_empty( $root ) ) {
				$root = getenv( 'DOCUMENT_ROOT' );
			}
			if ( __hba_is_empty( $root ) ) {
				return false;
			}
			return \Hummingbird\HummingbirdApp::_hba_strip_trailing_slash( $root );
		}

		private function _get_hostname() {
			$ret = '127.0.0.1';
			$ret = __hba_get_array_key( 'Host', $this->__headers, '127.0.0.1' );
			if ( '127.0.0.1' == $ret ) {
				$ret = __hba_get_array_key( 'HTTP_HOST', $this->__server, '127.0.0.1' );
			}
			if ( '127.0.0.1' == $ret ) {
				$ret = __hba_get_array_key( 'SERVER_NAME', $this->__server, '127.0.0.1' );
			}
			return $ret;
		}

		private function getCLICurrentPath() {
			return __hba_get_array_key( 'path', $this->getCLIInfo(), '/' );
		}

		private function getCLICurrentURL() {
			$path = $this->getCLICurrentPath();
			$query = __hba_get_array_key( 'query', $this->getCLIInfo(), array() );
			$base = $this->_get_current_script_absolute();
			$return = sprintf( '%s --path="%s"', $base, $path );
			if ( __hba_can_loop( $query ) ) {
				$return .= sprintf( ' --query="%s"', http_build_query( $query ) );
			}
			return $return;
		}

		private function getCLIURIFromPath( $path = '/', array $query = array() ) {
			$return = sprintf( '--path="%s"', $path );
			if ( __hba_can_loop( $query ) ) {
				$return .= sprintf( ' --query="%s"', http_build_query( $query ) );
			}
			return $return;
		}

		private function getCLIURLFromPath( $path = '/', array $query = array() ) {
			$base = $this->_get_current_script_absolute();
			return sprintf( '%s %s', $base, $this->getCLIURIFromPath( $path, $query ) );
		}

		private function parse_http_method_data( $method ) {
			$method = strtoupper( $method );
			if ( 'POST' == $method && __hba_can_loop( $_POST ) ) {
				return $_POST;
			}
			if ( 'GET' == $method && __hba_can_loop( $_GET ) && ! in_array( $this->getRequestMethod(), array( 'PATCH', 'DELETE', 'HEAD', 'OPTIONS' ) ) ) {
				return $_GET;
			}
			$return = array();
			$input = file_get_contents( 'php://input' );
			$rows = explode( "\r\n-", $input );
			$querystring = '';
			if ( ! array_key_exists( 'CONTENT_TYPE', $this->__server ) ) {
				$this->__server['CONTENT_TYPE'] = null;
			}
			if ( false !== strpos( $this->__server['CONTENT_TYPE'], 'form-urlencoded' ) ) {
				parse_str( $input, $return );
			}
			else if ( false !== strpos( $this->__server['CONTENT_TYPE'], 'text/plain' ) ) {
				parse_str( $input, $return );
			}
			else if ( false !== strpos( $this->__server['CONTENT_TYPE'], 'application/json' ) ) {
				$return = json_decode( $input, true );
			}
			else if ( false !== strpos( $this->__server['CONTENT_TYPE'], '/xml' ) ) {
				try {
					$e = simplexml_load_string( $input );
					$return = json_decode( json_encode( $e ), true );
				}
				catch ( Exception $e ) {

				}
			}
			else if ( __hba_can_loop( $rows ) ) {
				foreach ( $rows as $row ) {
					if ( ! is_empty( $row ) ) {
						if ( false !== strpos( $row, "\r\n\r\n" ) ) {
							list( $uglyname, $value ) = explode( "\r\n\r\n", $row );
							list( $boundary, $info ) = explode( "\r\n", $uglyname );
							if ( ! is_empty( $info ) && ! is_null( $value ) ) {
								list( $chuff, $rawname ) = explode( 'name=', $info );
								$name = str_replace( '"', '', $rawname );
								$name = str_replace( "'", '', $name );
								$querystring .= '&' . $name . '=' . $value;
								$return[ $name ] = $value;
							}
						}
					}
				}
				parse_str( $querystring, $return );
			}
			if ( $method !== $this->getRequestMethod() && ! in_array( $this->getRequestMethod(), array( 'GET', 'POST' ) ) ) {
				$return = array();
			}
			if ( 'GET' !== $this->getRequestMethod() && 'GET' == $method && __hba_can_loop( $return ) ) {
				$return = array();
			}
			return $return;
		}

		private function parse_http_headers() {
			if ( function_exists( 'getallheaders' ) ) {
				return getallheaders();
			}
			$return = array();
			foreach ( $this->__server as $key => $value ) {
				if ( substr( strtoupper( $key ), 0, 5 ) == 'HTTP_' ) {
					$key = substr( $key, 0, 5 );
					$key = str_replace( '_', ' ', $key );
					$key = ucwords( strtolower( $key ) );
					$key = str_replace( ' ', '-', $key );
					$return[ $key ] = $value;
				}
			}
			return $return;
		}

		private function getCLIInfo() {
			$vars = getopt( '', array( 'path:', 'query:' ) );
			$query = __hba_get_array_key( 'query', $vars, array() );
			if ( ! is_array( $query ) ) {
				if ( ! is_empty( $query ) ) {
					parse_str( $query, $query );
				}
				else {
					$query = array();
				}
			}
			return array(
				'path' => __hba_get_array_key( 'path', $vars, '/' ),
				'query' => $query,
			);
		}
	}