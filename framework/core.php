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
			'authentication' => array(
				'module' => '',
			),
			'newrelic' => array(
				'enabled' => true,
				'apmName' => '',
				'apmLicense' => '',
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
		);
		private $actions = array();
		private $routes = array();
		private $activeDatabases = array();
		private $requestInfo = array(
			'method' => 'GET',
			'routePattern' => '',
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
			$calf = sprintf( '%s/framework/composer/vendor/autoload.php', self::stripTrailingSlash( ABSPATH ) );
			if ( ! file_exists( $calf ) ) {
				throw new Exception( sprintf( 'Missing Core Framework Composer Autoloader File "%s"', self::obfuscateWebDirectory( $calf ) ), 1 );
			}
			else {
				array_push( $this->_loadedCoreFrameworkFiles, $calf );
				require_once $calf;
			}
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
			$cfmd = scandir( sprintf( '%s/framework/modules/', self::stripTrailingSlash( ABSPATH ) ) );
			$cfms = array();
			if ( self::canLoop( $cfmd ) ) {
				foreach ( $cfmd as $sd ) {
					$abssd = sprintf( '%s/framework/modules/%s', self::stripTrailingSlash( ABSPATH ), $sd );
					if ( '.' !== $sd && '..' !== $sd && file_exists( $abssd ) && is_dir( $abssd ) ) {
						array_push( $cfms, $abssd );
					}
				}
			}
			if ( self::canLoop( $cfms ) ) {
				foreach ( $cfms as $modulePath ) {
					$files = $this->loadModuleFromPath( $modulePath );
					if ( self::canLoop( $files ) ) {
						foreach ( $files as $file ) {
							array_push( $this->_loadedCoreFrameworkFiles, $file );
						}
					}
				}
			}
			$drcsvf = sprintf( '%s/framework/data/routes.csv', self::stripTrailingSlash( ABSPATH ) );
			if ( file_exists( $drcsvf ) ) {
				array_push( $this->_loadedCoreFrameworkFiles, $drcsvf );
				$routes_csv = file_get_contents( $drcsvf );
				$rows_csv = explode( "\n", $routes_csv );
				if ( self::canLoop( $rows_csv ) ) {
					$headerRow = array_shift( $rows_csv );
					$keys = str_getcsv( $headerRow );
					foreach ( $rows_csv as $row ) {
						$array = str_getcsv( $row );
						if ( count( $keys ) == count( $array ) ) {
							$data = array_combine( $keys, $array );
							$this->addRoute(
								self::getArrayKey( 'method', $data ),
								self::getArrayKey( 'pattern', $data ),
								self::getArrayKey( 'action', $data ),
								( 'TRUE' == self::getArrayKey( 'authRequired', $data ) ),
								( 'TRUE' == self::getArrayKey( 'redirectAuthenticated', $data ) ),
								self::getArrayKey( 'title', $data ),
								true
							);
						}
					}
				}
			}
			$amdd = scandir( sprintf( '%s/modules/', self::stripTrailingSlash( ABSPATH ) ) );
			$amds = array();
			if ( self::canLoop( $amdd ) ) {
				foreach ( $amdd as $sd ) {
					$abssd = sprintf( '%s/modules/%s', self::stripTrailingSlash( ABSPATH ), $sd );
					if ( '.' !== $sd && '..' !== $sd && file_exists( $abssd ) && is_dir( $abssd ) ) {
						array_push( $amds, $abssd );
					}
				}
			}
			if ( self::canLoop( $amds ) ) {
				foreach ( $amds as $modulePath ) {
					$files = $this->loadModuleFromPath( $modulePath );
					if ( self::canLoop( $files ) ) {
						foreach ( $files as $file ) {
							array_push( $this->_loadedCoreFrameworkFiles, $file );
						}
					}
				}
			}
			$this->requestInfo['routePattern'] = $this->getCurrentRequestRoutePattern();
			if ( $this->canUseNewRelic() ) {
				if ( ! self::isEmpty( $this->getConfigSetting( 'newrelic', 'apmName' ) ) ) {
					newrelic_set_appname( $this->getConfigSetting( 'newrelic', 'apmName' ) );
				}
				newrelic_background_job( $this->isCLI() );
				newrelic_ignore_apdex( $this->isCLI() );
				$nrtn = sprintf(
					'%s %s',
					self::getArrayKey( 'method', $this->requestInfo ),
					self::getArrayKey( 'routePattern', $this->requestInfo )
				);
				newrelic_name_transaction( $nrtn );
				if ( ! self::isEmpty( $this->getConfigSetting( 'newrelic', 'apmLicense' ) ) ) {
					newrelic_start_transaction( $nrtn, $this->getConfigSetting( 'newrelic', 'apmLicense' ) );
				}
			}
			set_error_handler( array( $this, 'handleSystemError' ) );
			set_exception_handler( array( $this, 'handleSystemException' ) );
			$this->addAction( 'shutdown', array( $this, 'handleRoute' ) );
		}

		public function handleSystemError( int $errno , string $errstr, string $errfile, int $errline, array $errcontext ) {
			$return = false;
			$reportNewRelic = true;
			switch ( $errno ) {
				case E_ERROR:
					$return = true;
					$reportNewRelic = true;
					break;

				case E_WARNING:
					$return = true;
					$reportNewRelic = false;
					break;

				case E_PARSE:
					$return = true;
					$reportNewRelic = true;
					break;

				case E_NOTICE:
					$return = true;
					$reportNewRelic = false;
					break;

				case E_CORE_ERROR:
					$return = true;
					$reportNewRelic = true;
					break;

				case E_CORE_WARNING:
					$return = true;
					$reportNewRelic = false;
					break;

				case E_COMPILE_ERROR:
					$return = true;
					$reportNewRelic = true;
					break;

				case E_COMPILE_WARNING:
					$return = true;
					$reportNewRelic = false;
					break;

				case E_USER_ERROR:
					$return = true;
					$reportNewRelic = true;
					break;

				case E_USER_WARNING:
					$return = true;
					$reportNewRelic = false;
					break;

				case E_USER_NOTICE:
					$return = true;
					$reportNewRelic = false;
					break;

				case E_STRICT:
					$return = true;
					$reportNewRelic = true;
					break;

				case E_RECOVERABLE_ERROR:
					$return = true;
					$reportNewRelic = true;
					break;

				case E_DEPRECATED:
					$return = true;
					$reportNewRelic = false;
					break;

				case E_USER_DEPRECATED:
					$return = true;
					$reportNewRelic = false;
					break;
			}
			if ( true == $reportNewRelic && true == $this->canUseNewRelic() ) {
				$ex = new Exception( $errstr, $errno );
				newrelic_notice_error( $errstr, $ex );
			}
			if ( true == $return ) {
				$returnType = $this->getReturnDataTypeFromReturnMime();
				$feedbackClass = sprintf( '%s_Feedback', ucwords( $returnType ) );
				if ( ! class_exists( $feedbackClass ) ) {
					$return = false;
				}
				else {
					call_user_func(
						array( $feedbackClass, 'FAILURE' ),
						array(
							'msg' => $errstr,
							'file' => self::obfuscateWebDirectory( $errfile ),
							'line' => intval( $errline ),
						),
						$errstr,
						array( $errstr ),
						501,
						true
					);
				}
			}
			return $return;
		}

		public function handleSystemException( $e ) {
			$errno = E_ERROR;
			$errstr = $e->getMessage();
			$errfile = $e->getFile();
			$errline = $e->getLine();
			$return = true;
			$returnType = $this->getReturnDataTypeFromReturnMime();
			$feedbackClass = sprintf( '%s_Feedback', ucwords( $returnType ) );
			if ( ! class_exists( $feedbackClass ) ) {
				$return = false;
			}
			else {
				if ( $this->canUseNewRelic() ) {
					newrelic_notice_error( $errstr, $e );
				}
				call_user_func(
					array( $feedbackClass, 'FAILURE' ),
					array(
						'msg' => $errstr,
						'file' => self::obfuscateWebDirectory( $errfile ),
						'line' => intval( $errline ),
					),
					$errstr,
					array( $errstr ),
					501,
					true
				);
			}
			return $return;
		}

		public function getConfigPHP() {
			$config = $this->config;
			$return = sprintf( '$config = %s;', var_export( $config, true ) );
			$return = preg_replace( '/=> (\r\n|\r|\n)\s*/', '=> ', $return );
			return $return;
		}

		public function getFeedbackClass() {
			$returnType = $this->getReturnDataTypeFromReturnMime();
			return sprintf( '%s_Feedback', ucwords( $returnType ) );
		}

		public function getReturnDataTypeFromReturnMime() {
			if ( true == self::matchesPattern( $this->returnMime, 'json' ) ) {
				return 'json';
			}
			if ( true == self::matchesPattern( $this->returnMime, 'html' ) ) {
				return 'html';
			}
			if ( true == self::matchesPattern( $this->returnMime, 'xml' ) ) {
				return 'xml';
			}
			return 'plaintext';
		}

		public function addAction( $key, $function, $priority = null ) {
			if ( ! is_array( $this->actions ) ) {
				$this->actions = array();
			}
			if ( ! array_key_exists( $key, $this->actions ) ) {
				$this->actions[ $key ] = array();
			}
			if ( is_null( $priority ) ) {
				array_push( $this->actions[ $key ], $function );
			}
			else {
				$keyid = $priority;
				while ( array_key_exists( $keyid, $this->actions[ $key ] ) ) {
					$keyid ++;
				}
				$this->actions[ $key ][ $keyid ] = $function;
			}
		}

		public function doAction( $action, $data = null, $params = 1 ) {
			if ( ! is_array( $this->actions ) ) {
				$this->actions = array();
			}
			if ( array_key_exists( $action, $this->actions ) && count( $this->actions[ $action ] ) > 0 ) {
				if ( function_exists( 'newrelic_identify_action' ) ) {
					newrelic_identify_action( $action );
				}
				ksort( $this->actions[ $action ], SORT_NUMERIC );
				foreach ( $this->actions[ $action ] as $index => $function ) {
					if ( is_array( $function ) ) {
						list( $class, $method ) = $function;
						$valid = (
							( is_object( $class ) || class_exists( $class ) )
							&& method_exists( $class, $method )
						);
					}
					else {
						$valid = function_exists( $function );
					}
					if ( true == $valid ) {
						if ( is_null( $data ) ) {
							call_user_func( $function );
						}
						else if ( ! is_array( $data ) || $params > 1 ) {
							call_user_func( $function, $data );
						}
						else {
							call_user_func_array( $function, $data );
						}
					}
				}
			}
		}

		public function addRoute( $method, $pattern, $action, $authRequired, $redirectAuthenticated, $title, $overwrite = false ) {
			if ( ! is_array( $this->routes ) ) {
				$this->routes = array();
			}
			$method = strtoupper( $method );
			if ( ! array_key_exists( $method, $this->routes ) || ! is_array( $this->routes[ $method ] ) ) {
				$this->routes[ $method ] = array();
			}
			if ( ! array_key_exists( $pattern, $this->routes[ $method ] ) || true == $overwrite ) {
				$this->routes[ $method ][ $pattern ] = array(
					'action' => $action,
					'authRequired' => ( true == $authRequired ),
					'redirectAuthenticated' => ( true == $redirectAuthenticated ),
					'title' => $title,
				);
			}
			else {
				return false;
			}
			return true;
		}

		public function addDatabase( string $key, string $type = 'sqlite', string $host = '', int $port = 3306, string $name = '/tmp/dbfile.db', $user = null, $pass = null, $prefix = null, bool $frozen = false ) {
			if ( ! array_key_exists( $this->config['databases'] ) ) {
				$this->config['databases'][ $key ] = array(
					'type' => $type,
					'host' => $host,
					'port' => $port,
					'name' => $name,
					'user' => $user,
					'pass' => $pass,
					'prefix' => $prefix,
					'frozen' => ( true == $frozen ),
				);
			}
			return false;
		}

		##
		# Loads the core and starts running the various "actions"
		# We use the static version to do this so that if someone wants to load the framework but not start running the various associated actions, they can
		##
		public static function init( array $config = array() ) {
			$c = get_called_class();
			$obj = new $c( $config );
			/**
			 * Load stuff that needs to be loaded before loading various external stuff
			 */
			$obj->doAction( 'init' );
			/**
			 * Load Databases
			 */
			$obj->doAction( 'initDatabases' );
			/**
			 * Handle Routing
			 */
			$obj->doAction( 'initRouting' );
			/**
			 * Handle Shutdown
			 */
			$obj->doAction( 'shutdown' );
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

		public static function absInt( $input ) {
			if ( ! is_numeric( $input ) || self::isEmpty( $input ) ) {
				return 0;
			}
			$int = intval( $input );
			if ( $int < 0 ) {
				$int = $int * -1;
			}
			return $int;
		}

		public static function stripTrailingSlash( $input ) {
			if ( '/' == substr( $input, -1 ) ) {
				$input = substr( $input, 0, strlen( $input ) - 1 );
			}
			return $input;
		}

		public static function obfuscateWebDirectory( $input ) {
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

		private function loadModuleFromPath( $modulePath ) {
			$return = array();
			if ( file_exists( $modulePath ) && is_dir( $modulePath ) ) {
				$mfds = array( 'interfaces', 'adapters', 'data', 'abstracts', 'classes', 'functions' );
				foreach ( $mfds as $reldir ) {
					$abspath = sprintf( '%s/%s/', self::stripTrailingSlash( $modulePath ), self::stripTrailingSlash( $reldir ) );
					try {
						$df = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $abspath ), RecursiveIteratorIterator::SELF_FIRST );
						foreach ( $df as $name => $chuff ) {
							if ( substr( $name, -4 ) == '.php' && strpos( $name, 'index.php' ) === false ) {
								array_push( $return, $name );
								require_once $name;
							}
						}
					}
					catch ( Exception $e ) {}
				}
				$if = sprintf( '%s/instructions.php', self::stripTrailingSlash( $modulePath ) );
				if ( file_exists( $if ) ) {
					require_once $if;
				}
			}
			return $return;
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

		private function getCurrentRelativePath( $prefix = true ) {
			$cf = self::getArrayKey( 'PHP_SELF', $_SERVER, self::getArrayKey( 'SCRIPT_NAME', $_SERVER, '/' ) );
			$cf = str_replace( 'index.php', '', $cf );
			$uri = self::getArrayKey( 'REQUEST_URI', $_SERVER, self::getArrayKey( 'REDIRECT_URL', $_SERVER, '/' ) );
			if ( '/' !== $cf ) {
				$cfl = strlen( $cf );
				$uri = substr( $uri, $cfl );
			}
			if ( '/' !== substr( $uri, 0, 1 ) ) {
				$uri = '/' . $uri;
			}
			$return = ( true == $prefix ) ? '.' : '';
			$return .= $uri;
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

		private function getCurrentRequestRoutePattern() {
			$method = $this->requestInfo['method'];
			$methodRoutes = self::getArrayKey( $method, $this->routes );
			$path = $this->getCurrentRelativePath( false );
			if ( array_key_exists( $path, $methodRoutes ) ) {
				return $path;
			}
			else {
				foreach ( $methodRoutes as $pattern => $info ) {
					$pat = self::fixRoutePatternForRegex( $pattern );
					if ( intval( preg_match( $pat, $path, $matches ) ) > 0 ) {
						return $pattern;
					}
				}
			}
			$keys = array_keys( $methodRoutes );
			if ( self::canLoop( $keys ) ) {
				return array_shift( $keys );
			}
			return null;
		}

		private function getPassthroughDataFromPath( $path, $pattern ) {
			$return = array();
			$pat = self::fixRoutePatternForRegex( $pattern );
			if ( intval( preg_match( $pat, $path, $matches ) ) > 0 ) {
				array_shift( $matches );
				$return = array_merge( $return, $matches );
			}
			return $return;
		}

		private static function fixRoutePatternForRegex( $input ) {
			$input = str_replace( '/', '\/', $input );
			$input = sprintf( '/%s/', $input );
			return $input;
		}

		private function canUseNewRelic() {
			return ( extension_loaded( 'newrelic' ) && $this->getConfigSetting( 'newrelic', 'enabled' ) );
		}

		private function handleRoute() {
			$fbc = $this->getFeedbackClass();
			$routeInfo = self::getArrayKey( self::getArrayKey( 'routePattern', $this->requestInfo, '' ), self::getArrayKey( self::getArrayKey( 'method', $this->requestInfo, 'GET' ), $this->routes, array() ), array() );
			$path = $this->getCurrentRelativePath( false );
			$passthrough = $this->getPassthroughDataFromPath( $path, self::getArrayKey( 'routePattern', $this->requestInfo, '' ) );
			$routeAction = sprintf( 'route_action_%s', self::getArrayKey( 'action', $routeInfo, '404' ) );
			$ptd = array_merge( $routeInfo, array(
				'fbc' => $fbc,
				'pt' => $passthrough
			) );
			$this->doAction( $routeAction, $ptd );
			$ptd['action'] = 'error';
			$fbc::FAILURE(
				$ptd,
				( true == $this->getConfigSetting( 'application', 'debug' ) ) ? sprintf( 'Action "%s" not defined in any modules', $routeAction ) : 'No Such Action',
				array(),
				404,
				true
			);
		}

		private static function matchesPattern( $string, $pattern ) {
			return ( $pattern == $string || intval( preg_match( self::fixRoutePatternForRegex( $pattern ), $string, $matches ) ) > 0 );
		}

		public static function debug( $input ) {
			$_server = $_SERVER;
			if ( function_exists( 'getallheaders' ) ) {
				$headers = getallheaders();
			}
			else {
				$headers = array();
				foreach ( $_server as $key => $value ) {
					if ( substr( strtoupper( $key ), 0, 5 ) == 'HTTP_' ) {
						$key = substr( $key, 0, 5 );
						$key = str_replace( '_', ' ', $key );
						$key = ucwords( strtolower( $key ) );
						$key = str_replace( ' ', '-', $key );
						$headers[ $key ] = $value;
					}
				}
			}
			$mime = 'text/plain';
			$accepted = self::getArrayKey( 'Accept', $headers, '*/*' );
			if ( false == strpos( $accepted, ',' ) ) {
				$mimes = array( $accepted );
			}
			else {
				$mimes = explode( ',', $accepted );
			}
			$first = array_shift( $mimes );
			if ( '*/*' !== trim( $first ) ) {
				$mime = strtolower( $first );
			}
			$returnType = 'plaintext';
			if ( true == self::matchesPattern( $mime, 'json' ) ) {
				$returnType = 'json';
			}
			if ( true == self::matchesPattern( $mime, 'html' ) ) {
				$returnType = 'html';
			}
			if ( true == self::matchesPattern( $mime, 'xml' ) ) {
				$returnType = 'xml';
			}
			$fbc = sprintf( '%s_Feedback', ucwords( $returnType ) );
			$fbc::DEBUG(
				$input,
				'Debug',
				array(),
				200,
				true
			);
		}

		public static function showFileContents( $file, $line = 0 ) {
			$return = '';
			if ( file_exists( $file ) ) {
				$contents = file_get_contents( $file );
				$lines = explode( "\n", $contents );
				if ( self::canLoop( $lines ) ) {
					if ( $line <= 0 ) {
						$line = 0;
					}
					$start = $line - 6;
					if ( $start <= 0 ) {
						$start = 0;
					}
					$myLines = array_slice( $lines, $start, 13, true );
					if ( self::canLoop( $myLines ) ) {
						foreach ( $myLines as $index => $content ) {
							$lineNumber = $index + 1;
							$return .= '[' . $lineNumber . '] ' . str_replace( "\r", '', $content ) . "\r\n";
						}
					}
				}
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
			return $this->getConfigSetting( 'application', 'name' );
		}

		function __sleep() {
			return false;
		}

		function __wakeup() {
			return false;
		}
	}