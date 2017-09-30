<?php
	namespace Hummingbird;
	/**
	 * Development Notes
	 **
	 * Naming Conventions for Classes
	 * - Controller: A class which interacts with a non-PHP component such as Databases, Caches, Filesystem, External API's etc.
	 * - Adapter: A class which normalizes the feedback from controller to return normalized feedback
	 * - Interface: A class which receives input and returns output
	 * - Application: A wrapper which simplifies configuration and running of an application
	 */

	class HummingbirdApp {
		private $version = '0.0.1';
		private $baseDir = '';
		private $hummbingbirdBaseDir = '';
		private $baseUri = '';
		private $baseUrl = '';
		private $_config = array();
		private $_actions = array();
		private $_filters = array();
		private $_routes = array();
		private $__hbs_loaded_files = array();
		private $__hbs_loaded_functions = array();
		private $__hba_loaded_actions = array();
		private $__hbs_feedback_controller = null;
		private $__hbs_error_controller = null;
		private $__hbs_request_controller = null;
		private $__hbs_database_controllers = array();
		private $__hbs_cache_controller = null;

		/**
		 * Initializes the application by loading all of the relevant files from the subdirectories
		 * Also checks for required libraries and throws errors if things don't exit
		 */
		function __construct() {
			$this->hummbingbirdBaseDir = realpath( dirname( __FILE__ ) );
			$this->setBaseDir( substr( $this->hummbingbirdBaseDir, 0, strlen( $this->hummbingbirdBaseDir ) - 3 ) );
			$res = $this->loadComposer();
			$hummingbird_library_directories = array( 'abstractInterfaces', 'abstracts', 'controllers', 'adapters', 'interfaces', 'functions', 'data' );
			foreach ( $hummingbird_library_directories as $dir ) {
				$absdir = $this->getExistingAbsoluteDirOfFile( sprintf( '/%s/', $dir ), true );
				if ( false !== $absdir ) {
					try {
						$df = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $absdir ), \RecursiveIteratorIterator::SELF_FIRST );
						foreach ( $df as $name => $chuff ) {
							if ( substr( $name, -4 ) == '.php' && strpos( $name, 'index.php' ) === false ) {
								array_push( $this->__hbs_loaded_files, $name );
								require_once $name;
							}
						}
					}
					catch ( \Exception $e ) {}
				}
			}
			$recs = self::CheckRequirements( true );
			if ( true !== $recs->status ) {
				foreach ( $recs as $rec => $fulfilled ) {
					if ( 'status' !== $rec && true !== $fulfilled ) {
						throw new \Exception( sprintf( 'Missing requirement "%s"', $rec ), 1 );
					}
				}
			}
			$allFunctions = get_defined_functions();
			$allRealFunctions = array();
			foreach ( $allFunctions as $section => $list ) {
				foreach ( $list as $funct ) {
					if ( ! in_array( $funct, $allRealFunctions ) ) {
						array_push( $allRealFunctions, $funct );
					}
				}
			}
			foreach ( $allRealFunctions as $funct ) {
				if ( __hba_beginning_matches( '__hba_', $funct ) ) {
					$rf = substr( $funct, strlen( '__hba_' ) );
					if ( ! function_exists( $rf ) ) {
						$toEval = sprintf(
							'function %s() {' . "\r\n" .
							'	$args = func_get_args();' . "\r\n" .
							'	return call_user_func_array( \'%s\', $args );' . "\r\n" .
							'}',
							$rf,
							$funct
						);
						eval( $toEval );
						$this->__hbs_loaded_functions[ $rf ] = $funct;
					}
				}
			}
			$this->setConfig( $defaultConfig );
			$this->addAction( 'init', array( $this, 'activateControllers' ) );
			$this->addAction( 'initDatbases', array( $this, 'activateDatabaseControllers' ) );
			$this->addAction( 'initCache', array( $this, 'activateCacheController' ) );
		}

		/**
		 * Merges an array with configuration parameters into the existing application configuration
		 * @param array $config an array of parameter sections and settings
		 */
		public function setConfig( array $config = array() ) {
			$this->_config = array_replace_recursive( $this->_config, $config );
		}

		/**
		 * Retreive a section of the current application configuration
		 * @param  string $key The section of the configuration which should be returned
		 * @return array      The second of the configuration relatd to the key
		 */
		public function getConfigSection( string $key = '' ) {
			return __hba_get_array_key( $key, $this->_config, array() );
		}

		/**
		 * Retrieve a specific setting
		 * @param  string $section The section which the setting is found in
		 * @param  string $setting The name of the setting
		 * @param  mixed $default The default value to be returned
		 * @return mixed          The setting's value
		 */
		public function getConfigSetting( string $section = '', string $setting = '', $default = null ) {
			$section = $this->getConfigSection( $section );
			return __hba_get_array_key( $setting, $section, $default );
		}

		public function addAction( $key, $function, $priority = 100, $passApp = true ) {
			if ( ! array_key_exists( $key, $this->_actions ) ) {
				$this->_actions[ $key ] = array();
			}
			array_push( $this->_actions[ $key ], array(
				'function' => $function,
				'priority' => $priority,
				'passApp' => ( true == $passApp ),
			) );
			usort( $this->_actions[ $key ], array( get_called_class(), '_hba_sort_by_priority' ) );
		}

		public function addFilter( $key, $function, $priority = 100, $passApp = true ) {
			if ( ! array_key_exists( $key, $this->_filters ) ) {
				$this->_filters[ $key ] = array();
			}
			array_push( $this->_filters[ $key ], array(
				'function' => $function,
				'priority' => $priority,
				'passApp' => ( true == $passApp ),
			) );
			usort( $this->_filters[ $key ], array( get_called_class(), '_hba_sort_by_priority' ) );
		}

		public function addRoute() {

		}

		public function addDatabase( string $key, string $type = 'sqlite', string $host = '', int $port = 0, string $name = '/tmp/dbfile.db', string $user = '', string $pass = '', string $prefix = '', bool $frozen = false, bool $readonly = false, bool $overwrite = false ) {
			if ( false == $this->getConfigSetting( 'databases', 'enabled' ) ) {
				return false;
			}
			if ( in_array( 'initDatbases', $this->__hba_loaded_actions ) ) {
				$dbc = $this->getConfigSetting( 'application', 'databaseController' );
				if ( ! __hba_is_instance_of( $dbc, 'Hummingbird\HummingbirdDatabaseControllerInterface' ) ) {
					throw new \Exception( sprintf( 'Class "%s" must implement Hummingbird\HummingbirdDatabaseControllerInterface', $dbc ), 1 );
				}
				if ( ! array_key_exists( $key, $this->__hbs_database_controllers ) || true == $overwrite ) {
					$this->__hbs_database_controllers[ $key ] = new $dbc(
						$this,
						$key,
						$type,
						$host,
						$port,
						$name,
						$user,
						$pass,
						$prefix,
						$frozen,
						$readonly
					);
				}
			}
			else {
				if ( ! array_key_exists( $key, $this->_config['databases']['servers'] ) || true == $overwrite ) {
					$this->_config['databases']['servers'][ $key ] = array(
						'type' => $type,
						'host' => $host,
						'port' => $port,
						'name' => $name,
						'user' => $user,
						'pass' => $pass,
						'prefix' => $prefix,
						'frozen' => $frozen,
						'readonly' => $readonly,
					);
				}
			}
		}

		public function run() {
			$this->doAction( 'init' );
			$this->doAction( 'initDatbases' );
			$this->doAction( 'initCache' );
		}

		public function setBaseDir( $dir ) {
			if ( file_exists( $dir ) && is_dir( $dir ) ) {
				$this->baseDir = self::_hba_strip_trailing_slash( $dir );
			}
		}

		public function getBaseDir() {
			return $this->baseDir;
		}

		public function doFilter( $key, $filterable = null ) {
			if (
				array_key_exists( $key, $this->_filters )
				&& is_array( $this->_filters[ $key ] )
			) {
				$args = func_get_args();
				if ( is_array( $args ) && count( $args ) > 1 ) {
					array_shift( $args );
				}
				else {
					$args = array();
				}
				foreach ( $this->_filters[ $key ] as $action ) {
					$function = ( is_array( $action ) && array_key_exists( 'function', $action ) ) ? $action['function'] : '';
					$passApp = ( is_array( $action ) && array_key_exists( 'passApp', $action ) ) ? $action['passApp'] : false;
					$exists = false;
					if ( is_array( $function ) ) {
						list( $class, $method ) = $function;
						if ( ( is_object( $class ) || class_exists( $class ) ) && method_exists( $class, $method ) ) {
							$exists = true;
						}
					}
					else {
						if ( function_exists( $function ) ) {
							$exists = true;
						}
					}
					if ( true == $exists ) {
						if ( true == $passApp ) {
							array_unshift( $args, $this );
						}
						$filterable = call_user_func_array( $function, $args );
					}
				}
			}
			return $filterable;
		}

		public function runFeedbackFunction( string $function ) {
			$args = func_get_args();
			array_shift( $args );
			return call_user_func_array( array( $this->__hbs_feedback_controller, $function ), $args );
		}

		public function runErrorFunction( string $function ) {
			$args = func_get_args();
			array_shift( $args );
			return call_user_func_array( array( $this->__hbs_error_controller, $function ), $args );
		}

		public function runRequestFunction( string $function ) {
			$args = func_get_args();
			array_shift( $args );
			return call_user_func_array( array( $this->__hbs_request_controller, $function ), $args );
		}

		public function runDatabaseFunction( string $key = 'default', string $function ) {
			$args = func_get_args();
			array_shift( $args );
			array_shift( $args );
			if ( false == $this->getConfigSetting( 'databases', 'enabled' ) ) {
				return false;
			}
			if ( ! array_key_exists( $key, $this->__hbs_database_controllers ) ) {
				return false;
			}
			$c = $this->__hbs_database_controllers[ $key ];
			try {
				return call_user_func_array( array( $c, $function ), $args );
			}
			catch ( \Exception $e ) {
				$bt = debug_backtrace();
				$trace = array_shift( $bt );
				$this->runErrorFunction( 'writeToLogFile', sprintf( 'Database function "%s" failed on database "%s": %s IN File %s Line %d', $function, $key, $e->getMessage(), __hba_get_array_key( 'file', $trace ), __hba_get_array_key( 'line', $trace ) ) );
				return false;
			}
		}

		public function runCacheFunction( string $function ) {
			$args = func_get_args();
			array_shift( $args );
			return call_user_func_array( array( $this->__hbs_cache_controller, $function ), $args );
		}

		private function doAction( $key ) {
			if (
				array_key_exists( $key, $this->_actions )
				&& is_array( $this->_actions[ $key ] )
			) {
				$args = func_get_args();
				if ( is_array( $args ) && count( $args ) > 1 ) {
					array_shift( $args );
				}
				else {
					$args = array();
				}
				foreach ( $this->_actions[ $key ] as $action ) {
					$function = ( is_array( $action ) && array_key_exists( 'function', $action ) ) ? $action['function'] : '';
					$passApp = ( is_array( $action ) && array_key_exists( 'passApp', $action ) ) ? $action['passApp'] : false;
					$exists = false;
					if ( is_array( $function ) ) {
						list( $class, $method ) = $function;
						if ( ( is_object( $class ) || class_exists( $class ) ) && method_exists( $class, $method ) ) {
							$exists = true;
						}
					}
					else {
						if ( function_exists( $function ) ) {
							$exists = true;
						}
					}
					if ( true == $exists ) {
						if ( true == $passApp ) {
							array_unshift( $args, $this );
						}
						call_user_func_array( $function, $args );
					}
				}
				array_push( $this->__hba_loaded_actions, $key );
			}
			return true;
		}

		private function getExistingAbsoluteDirOfFile( $relativePath = '/', $directory = false ) {
			$file = $this->getAbsoluteDirOfFile( $relativePath );
			if ( ! file_exists( $file ) ) {
				$file = false;
			}
			if ( false !== $file && false !== $directory && ! is_dir( $file ) ) {
				$file = false;
			}
			return $file;
		}

		private function getAbsoluteDirOfFile( $relativePath = '/' ) {
			return sprintf(
				'%s/%s',
				$this->hummbingbirdBaseDir,
				self::_hba_strip_leading_slash( $relativePath )
			);
		}

		private function loadComposer() {
			$composer_auto_loader = $this->getExistingAbsoluteDirOfFile( '/composer/vendor/autoload.php' );
			if ( false == $composer_auto_loader ) {
				throw new \Exception( sprintf( 'Missing Composer Libraries. Cannot find autoloader "%s"', $this->getAbsoluteDirOfFile( '/composer/vendor/autoload.php' ) ), 1 );
			}
			array_push( $this->__hbs_loaded_files, $composer_auto_loader );
			require_once $composer_auto_loader;
		}

		private function activateControllers() {
			$fcc = $this->getConfigSetting( 'application', 'feedbackController' );
			if ( __hba_is_instance_of( $fcc, 'Hummingbird\HummingbirdFeedbackControllerInterface' ) ) {
				$this->__hbs_feedback_controller = new $fcc( $this );
			}
			else {
				throw new \Exception( sprintf( 'Class "%s" must implement Hummingbird\HummingbirdFeedbackControllerInterface', $ecc ), 1 );
			}
			$ecc = $this->getConfigSetting( 'application', 'errorController' );
			if ( __hba_is_instance_of( $ecc, 'Hummingbird\HummingbirdErrorControllerInterface' ) ) {
				$this->__hbs_error_controller = new $ecc( $this );
			}
			else {
				throw new \Exception( sprintf( 'Class "%s" must implement Hummingbird\HummingbirdErrorControllerInterface', $ecc ), 1 );
			}
			$rcc = $this->getConfigSetting( 'application', 'requestController' );
			if ( __hba_is_instance_of( $rcc, 'Hummingbird\HummingbirdRequestControllerInterface' ) ) {
				$this->__hbs_request_controller = new $rcc( $this );
			}
			else {
				throw new \Exception( sprintf( 'Class "%s" must implement Hummingbird\HummingbirdRequestControllerInterface', $rcc ), 1 );
			}
			set_error_handler( array( $this->__hbs_error_controller, 'handleError' ), E_ALL | E_STRICT );
			set_exception_handler( array( $this->__hbs_error_controller, $this->__hbs_error_controller->getExceptionHandlerFunctionName() ) );
			$this->baseUri = $this->runRequestFunction( 'getURIFromPath', '/' );
			$this->baseUrl = $this->runRequestFunction( 'getURLFromPath', '/' );
		}

		private function activateDatabaseControllers() {
			if ( false == $this->getConfigSetting( 'databases', 'enabled' ) ) {
				return false;
			}
			$dbc = $this->getConfigSetting( 'application', 'databaseController' );
			if ( ! __hba_is_instance_of( $dbc, 'Hummingbird\HummingbirdDatabaseControllerInterface' ) ) {
				throw new \Exception( sprintf( 'Class "%s" must implement Hummingbird\HummingbirdDatabaseControllerInterface', $dbc ), 1 );
			}
			$dbs = $this->getConfigSetting( 'databases', 'servers' );
			if ( __hba_can_loop( $dbs ) ) {
				foreach ( $dbs as $key => $dbi ) {
					$this->__hbs_database_controllers[ $key ] = new $dbc(
						$this,
						$key,
						(string) __hba_get_array_key( 'type', $dbi, '' ),
						(string) __hba_get_array_key( 'host', $dbi, '' ),
						(int) __hba_sanitize_absint( __hba_get_array_key( 'port', $dbi, 0 ) ),
						(string) __hba_get_array_key( 'name', $dbi, '' ),
						(string) __hba_get_array_key( 'user', $dbi, '' ),
						(string) __hba_get_array_key( 'pass', $dbi, '' ),
						(string) __hba_get_array_key( 'prefix', $dbi, '' ),
						(bool) __hba_get_array_key( 'frozen', $dbi, false ),
						(bool) __hba_get_array_key( 'readonly', $dbi, false )
					);
				}
			}
		}

		private function activateCacheController() {
			$cc = $this->getConfigSetting( 'application', 'cacheController' );
			if ( ! __hba_is_instance_of( $cc, 'Hummingbird\HummingbirdCacheControllerInterface' ) ) {
				throw new \Exception( sprintf( 'Class "%s" must implement Hummingbird\HummingbirdCacheControllerInterface', $cc ), 1 );
			}
			$this->__hbs_cache_controller = new $cc( $this );
		}

		public static function _hba_strip_trailing_slash( $input ) {
			if ( '/' == substr( $input, -1 ) || '\\' == substr( $input, -1 ) ) {
				$input = substr( $input, 0, strlen( $input ) - 1 );
			}
			return $input;
		}

		public static function _hba_strip_leading_slash( $input ) {
			if ( '/' == substr( $input, 0, 1 ) || '\\' == substr( $input, 0, 1 ) ) {
				$input = substr( $input, 1 );
			}
			return $input;
		}

		private static function _hba_sort_by_priority( $a, $b ) {
			$pa = ( is_array( $a ) && array_key_exists( 'priority', $a ) ) ? floatval( $a['priority'] ) : 100;
			$pb = ( is_array( $b ) && array_key_exists( 'priority', $b ) ) ? floatval( $b['priority'] ) : 100;
			if ( $pa == $pb ) {
				return 0;
			}
			return ( $pa < $pb ) ? -1 : 1;
		}

		public static function CheckRequirements( $loaded = false ) {
			$preLoadRequirements = array(
				'curl' => 'extension',
				'PDO' => 'extension',
				'xml' => 'extension',
				'mbstring' => 'extension',
				'intl' => 'extension',
				'json' => 'extension',
				'mcrypt' => 'extension',
				'SimpleXML' => 'extension',
			);
			$postLoadRequirements = array(
				'\libphonenumber\PhoneNumberUtil' => 'class',
			);
			$return = new \stdClass();
			$return->status = true;
			foreach ( $preLoadRequirements as $req => $type ) {
				switch ( $type ) {
					case 'extension':
						$return->{$req} = extension_loaded( $req );
						if ( true == $return->status && true !== $return->{$req} ) {
							$return->status = false;
						}
						break;

					case 'class':
						$return->{$req} = class_exists( $req );
						if ( true == $return->status && true !== $return->{$req} ) {
							$return->status = false;
						}
						break;
				}
			}
			if ( true == $loaded ) {
				foreach ( $postLoadRequirements as $req => $type ) {
					switch ( $type ) {
						case 'extension':
							$return->{$req} = extension_loaded( $req );
							if ( true == $return->status && true !== $return->{$req} ) {
								$return->status = false;
							}
							break;

						case 'class':
							$return->{$req} = class_exists( $req );
							if ( true == $return->status && true !== $return->{$req} ) {
								$return->status = false;
							}
							break;
					}
				}
			}
			return $return;
		}

		public function __get( string $name ) {
			return null;
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
	}