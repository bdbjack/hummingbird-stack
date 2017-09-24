<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	/**
	 * Hummingbird Core Class
	 * This is the core of the application. It takes the configuration data sets up the foundation for the rest of the application
	 * Many functions are static which can be used outside of this class, but a lot of functions are also non-static because they rely on information stored in the object.
	 * @Author: Jak Giveon <jak@jak.guru>
	 */
	class HC {
		private $config = array(
			'application' => array(
				'name' => '',
				'debug' => true,
				'timezone' => 'UTC',
			),
			'newrelic' => array(
				'enabled' => true,
				'apmName' => '',
			),
			'session' => array(
				'enabled' => false,
				'handler' => 'default',
			),
			'databases' => array(),
			'memcache' => array(),
			'memcached' => array(),
			'redis' => array(),
			'smtp' => array(),
			'twilio' => array(),
			'frameworkFiles' => array(),
			'moduleFiles' => array(),
		);
		private $actions = array();
		private $routes = array();
		private $activeDatabases = array();
		private $requestInfo = array(
			'method' => 'GET',
			'_headers' => array(),
			'_get' => array(),
			'_post' => array(),
			'_put' => array(),
			'_delete' => array(),
			'_files' => array(),
			'_cookies' => array(),
			'_session' => array(),
			'_cli' => array(),
			'_server' => array(),
		);
		private $absoluteURLBase = '';
		private $returnMime = 'text/plain';
		private $_loadedCoreFrameworkFiles = array();

		function __construct( array $config = array() ) {
			if ( self::canLoop( $config ) ) {
				$this->config = array_merge( $this->config, $config );
			}
			if ( true == $this->getConfigSetting( 'session', 'enabled' ) ) {
				session_start();
			}
			$this->requestInfo['_server'] = $_SERVER;
			$this->requestInfo['_get'] = $this->parseHttpMethodData( 'GET' );
			$this->requestInfo['_post'] = $this->parseHttpMethodData( 'POST' );
			$this->requestInfo['_put'] = $this->parseHttpMethodData( 'PUT' );
			$this->requestInfo['_delete'] = $this->parseHttpMethodData( 'DELETE' );
			$this->requestInfo['_headers'] = $this->parseHttpHeaders();
			$this->requestInfo['_files'] = $_FILES;
			$this->requestInfo['_cookies'] = $_COOKIE;
			$this->requestInfo['_cli'] = $this->parseCLIQuery();
			$this->requestInfo['_session'] = ( true == $this->getConfigSetting( 'session', 'enabled' ) ) ? $_SESSION : array();
			$this->absoluteURLBase = $this->getAbsoluteUrl();
			if ( true == self::isCLI() ) {
				$this->requestInfo['method'] = 'CLI';
			}
			else if ( array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
				$this->requestInfo['method'] = strtoupper( self::getArrayKey( 'REQUEST_METHOD', $_SERVER, 'GET' ) );
			}
			$this->returnMime = $this->getReturnMime();
			date_default_timezone_set( $this->getConfigSetting( 'application', 'timezone' ) );
			/**
			 * Now we need to load all core framework files
			 */
			## First load the composer files, since they're required in some of the other stuff
			$calf = sprintf( '%s/framework/composer/vendor/autoload.php', self::stripTrailingSlash( ABSPATH ) );
			if ( ! file_exists( $calf ) ) {
				throw new Exception( sprintf( 'Missing Core Framework Composer Autoloader File "%s"', self::obfuscateWebDirectory( $calf ) ), 1 );
			}
			else {
				array_push( $this->_loadedCoreFrameworkFiles, $calf );
				require_once $calf;
			}
			## Load the framework files
			$cffd = array( 'interfaces', 'adapters', 'abstracts', 'classes' );
			foreach ( $cffd as $relativeDir ) {
				$absDir = sprintf( '%s/framework/%s/', self::stripTrailingSlash( ABSPATH ), $relativeDir );
				try {
					$df = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $absDir ), RecursiveIteratorIterator::SELF_FIRST );
					foreach ( $df as $name => $chuff ) {
						if ( substr( $name, -4 ) == '.php' && strpos( $name, 'index.php' ) === false ) {
							array_push( $this->_loadedCoreFrameworkFiles, $name );
							require_once $name;
						}
					}
				}
				catch ( Exception $e ) {
					throw new Exception( sprintf( 'Missing Core Framework Directory "%s"', self::obfuscateWebDirectory( $absDir ) ), 1 );
				}
			}
			## Set Error handling function
			/**
			 * Now we need to load a list of all module files so we can load them as needed
			 */
			/**
			 * Now we need to load all actions
			 */
			/**
			 * Now we need to load all routes
			 */
		}

		##
		# Loads the core and starts running the various "actions"
		# We use the static version to do this so that if someone wants to load the framework but not start running the various associated actions, they can
		##
		public static function init( array $config = array() ) {
			$c = get_called_class();
			$obj = new $c( $config );
			return $obj;
		}

		public static function canLoop( $data ) {
			return ( is_array( $data ) && count( $data ) > 0 );
		}

		public static function getArrayKey( $key, $array = array(), $default = null ) {
			return ( is_array( $array ) && array_key_exists( $key, $array ) ) ? $array[ $key ] : $default;
		}

		public static function isCLI() {
			return ( 'cli' == php_sapi_name() );
		}

		public static function isEmpty( $var ) {
			if ( is_object( $var ) ) {
				return false;
			}
			if ( is_array( $var ) && self::canLoop( $var ) ) {
				return false;
			}
			return ( empty( $var ) || is_null( $var ) || ( ! is_array( $var ) && ! is_object( $var ) && 0 == strlen( $var ) ) );
		}

		public static function stripTrailingSlash( $input ) {
			if ( '/' == substr( $input, -1 ) ) {
				$input = substr( $input, 0, strlen( $input ) - 1 );
			}
			return $input;
		}

		private static function obfuscateWebDirectory( $input ) {
			$find = self::stripTrailingSlash( ABSPATH );
			return str_replace( $find, '{ABSPATH}', $input );
		}

		private function getConfigSection( $section = '' ) {
			return self::getArrayKey( $section, $this->config, array() );
		}

		private function getConfigSetting( $section = '', $key = '' ) {
			$s = $this->getConfigSection( $section );
			return self::getArrayKey( $key, $s, null );
		}

		private function parseHttpMethodData( $method ) {
			$_server = $_SERVER;
			$method = strtoupper( $method );
			if ( 'POST' == $method && self::canLoop( $_POST ) ) {
				return $_POST;
			}
			if ( 'GET' == $method && self::canLoop( $_GET ) ) {
				return $_GET;
			}
			$return = array();
			$input = file_get_contents( 'php://input' );
			$rows = explode( "\r\n-", $input );
			$querystring = '';
			if ( ! array_key_exists( 'CONTENT_TYPE', $_server ) ) {
				$_server['CONTENT_TYPE'] = null;
			}
			if ( false !== strpos( $_server['CONTENT_TYPE'], 'form-urlencoded' ) ) {
				parse_str( $input, $return );
			}
			else if ( false !== strpos( $_server['CONTENT_TYPE'], 'text/plain' ) ) {
				parse_str( $input, $return );
			}
			else if ( false !== strpos( $_server['CONTENT_TYPE'], 'application/json' ) ) {
				$return = json_decode( $input, true );
			}
			else if ( false !== strpos( $_server['CONTENT_TYPE'], '/xml' ) ) {
				try {
					$e = simplexml_load_string( $input );
					$return = json_decode( json_encode( $e ), true );
				}
				catch ( Exception $e ) {

				}
			}
			else if ( self::canLoop( $rows ) ) {
				foreach ( $rows as $row ) {
					if ( ! self::isEmpty( $row ) ) {
						if ( false !== strpos( $row, "\r\n\r\n" ) ) {
							list( $uglyname, $value ) = explode( "\r\n\r\n", $row );
							list( $boundary, $info ) = explode( "\r\n", $uglyname );
							if ( ! self::isEmpty( $info ) && ! is_null( $value ) ) {
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
			return $return;
		}

		private function parseHttpHeaders() {
			$_server = $_SERVER;
			if ( function_exists( 'getallheaders' ) ) {
				return getallheaders();
			}
			$return = array();
			foreach ( $_server as $key => $value ) {
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

		private function parseCLIQuery() {
			if ( ! self::isCLI() ) {
				return array();
			}
			$vars = getopt( '', array( 'query:' ) );
			return self::getArrayKey( 'query', $vars, array() );
		}

		private function getAbsoluteUrl( $path = '/', $query = array() ) {
			$_headers = self::getArrayKey( '_headers', $this->requestInfo, array() );
			$cf = self::getArrayKey( 'PHP_SELF', $_SERVER, self::getArrayKey( 'SCRIPT_NAME', $_SERVER, '/' ) );
			$cf = str_replace( 'index.php', '', $cf );
			$uri = self::getArrayKey( 'REQUEST_URI', $_SERVER, self::getArrayKey( 'REDIRECT_URL', $_SERVER, '/' ) );
			if ( '/' !== $cf ) {
				$cfl = strlen( $cf );
				$uri = substr( $uri, 0, $cfl );
			}
			if ( '/' !== substr( $uri, 0, 1 ) ) {
				$uri = '/' . $uri;
			}
			$return = '';
			if ( 'https' == self::getArrayKey( 'X-Forwarded-Proto', $_headers, 'http' ) || 'on' == strtolower( self::getArrayKey( 'HTTPS', $_SERVER, 'off' ) ) ) {
				$return .= 'https';
			}
			else {
				$return .= 'http';
			}
			$return .= '://';
			$return .= self::getArrayKey( 'HTTP_HOST', $_SERVER, 'localhost' );
			$return .= $uri;
			if ( '/' == substr( $return, -1 ) ) {
				$return = substr( $return, 0, strlen( $return ) - 1 );
			}
			if ( '/' !== substr( $path, 0, 1 ) ) {
				$path = '/' . $path;
			}
			$return .= $path;
			if ( self::canLoop( $query ) ) {
				$return .= '?' . http_build_query( $query );
			}
			return $return;
		}

		private function getReturnMime() {
			$return = 'text/plain';
			$headers = self::getArrayKey( '_headers', $this->requestInfo );
			$accepted = self::getArrayKey( 'Accept', $headers, '*/*' );
			if ( false == strpos( $accepted, ',' ) ) {
				$mimes = array( $accepted );
			}
			else {
				$mimes = explode( ',', $accepted );
			}
			$first = array_shift( $mimes );
			if ( '*/*' !== trim( $first ) ) {
				$return = strtolower( $first );
			}
			return $return;
		}

		function __get( string $name ) {
			return null;
		}

		function __set( string $name, $value ) {
			return false;
		}

		function __isset( string $name ) {
			return false;
		}

		function __unset( string $name ) {
			return false;
		}

		function __call( string $name, array $arguments = array() ) {
			return false;
		}

		static function __callStatic( string $name, array $arguments = array() ) {
			return false;
		}

		function __toString() {
			if ( true == $this->getConfigSetting( 'application', 'debug' ) ) {
				return var_export( $this, true );
			}
			return $this->getConfigSetting( 'application', 'name' );
		}

		function __sleep() {
			return false;
		}

		function __wakeup() {
			return false;
		}
	}