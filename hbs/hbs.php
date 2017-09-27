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
		private $_request = array();
		private $_config = array();
		private $_actions = array();
		private $_filters = array();
		private $_routes = array();
		private $_databases = array();
		private $_caches = array();
		private $__hbs_loaded_files = array();
		private $__hbs_loaded_functions = array();

		/**
		 * Initializes the application by loading all of the relevant files from the subdirectories
		 * Also checks for required libraries and throws errors if things don't exit
		 */
		function __construct() {
			$this->hummbingbirdBaseDir = realpath( dirname( __FILE__ ) );
			$this->setBaseDir( substr( $this->hummbingbirdBaseDir, 0, strlen( $this->hummbingbirdBaseDir ) - 3 ) );
			$res = $this->loadComposer();
			$hummingbird_library_directories = array( 'abstracts', 'controllers', 'adapters', 'interfaces', 'functions', 'data' );
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
		}

		public function setConfig( array $config = array() ) {
			$this->_config = array_replace_recursive( $this->_config, $config );
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

		public function addDatabase() {

		}

		public function addCache() {

		}

		public function run() {
			$this->doAction( 'init' );
		}

		public function setBaseDir( $dir ) {
			if ( file_exists( $dir ) && is_dir( $dir ) ) {
				$this->baseDir = self::_hba_strip_trailing_slash( $dir );
			}
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

		private static function _hba_strip_trailing_slash( $input ) {
			if ( '/' == substr( $input, -1 ) || '\\' == substr( $input, -1 ) ) {
				$input = substr( $input, 0, strlen( $input ) - 1 );
			}
			return $input;
		}

		private static function _hba_strip_leading_slash( $input ) {
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

		public static function EchoTest() {
			$args = func_get_args();
			echo '<pre>';
			print_r( $args );
			echo '</pre>';
		}
	}