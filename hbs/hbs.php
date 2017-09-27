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
		private $_routes = array();
		private $_databases = array();
		private $_caches = array();
		private $__hbs_loaded_files = array();

		/**
		 * Initializes the application by loading all of the relevant files from the subdirectories
		 * Also checks for required libraries and throws errors if things don't exit
		 */
		function __construct() {
			$this->hummbingbirdBaseDir = realpath( dirname( __FILE__ ) );
			$this->setBaseDir( substr( $this->hummbingbirdBaseDir, 0, strlen( $this->hummbingbirdBaseDir ) - 3 ) );
			if ( true !== $recs->status ) {
				foreach ( $recs as $rec => $fulfilled ) {
					if ( 'status' !== $rec && true !== $fulfilled ) {
						throw new \Exception( sprintf( 'Missing requirement "%s"', $rec ), 1 );
					}
				}
			}
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
			$this->setConfig( $defaultConfig );
		}

		public function setConfig( array $config = array() ) {
			$this->_config = array_replace_recursive( $this->_config, $config );
		}

		public function addAction() {

		}

		public function addRoute() {

		}

		public function addDatabase() {

		}

		public function addCache() {

		}

		public function run() {

		}

		public function setBaseDir( $dir ) {
			if ( file_exists( $dir ) && is_dir( $dir ) ) {
				$this->baseDir = self::_hba_strip_trailing_slash( $dir );
			}
		}

		private function doAction( $key ) {

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
			return sprintf( '%s/%s', $this->hummbingbirdBaseDir, self::_hba_strip_leading_slash( $relativePath ) );
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
	}