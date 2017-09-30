<?php
	namespace Hummingbird;

	/**
	 * The cache controller is used for creating and managing caches, however it doesn't allow you to choose which cache to use - instead it chooses the best cache based on availability in the following order:
	 * - Redis
	 * - Memcached
	 * - Memcache
	 * - Database
	 * - Filesystem
	 * Each one of these has their own dependancies, and if NONE of them are working (including the file system cache) all cache functions will return (bool) false
	 * Thankfully, we have a WONDERFUL function called active() which we can use to check if caching is active
	 */
	abstract class HummingbirdCacheControllerAbstract implements \Hummingbird\HummingbirdCacheControllerInterface {
		protected $hba;
		private $type;
		private $servers = array();
		private $active = false;
		private $_settings = array();

		function __construct( \Hummingbird\HummingbirdApp $hba ) {
			$this->hba = $hba;
			switch ( true ) {
				case ( true == $this->hba->getConfigSetting( 'redis', 'enabled' ) && class_exists( '\Redis' ) && __hba_can_loop( $this->hba->getConfigSetting( 'redis', 'servers' ) ) ):
					$this->type = 'redis';
					$this->servers = $this->hba->getConfigSetting( 'redis', 'servers' );
					break;

				case ( true == $this->hba->getConfigSetting( 'memcached', 'enabled' ) && class_exists( '\Memcached' ) && __hba_can_loop( $this->hba->getConfigSetting( 'memcached', 'servers' ) ) ):
					$this->type = 'memcached';
					$this->servers = $this->hba->getConfigSetting( 'memcached', 'servers' );
					break;

				case ( true == $this->hba->getConfigSetting( 'memcache', 'enabled' ) && class_exists( '\Memcache' ) && __hba_can_loop( $this->hba->getConfigSetting( 'memcache', 'servers' ) ) ):
					$this->type = 'memcache';
					$this->servers = $this->hba->getConfigSetting( 'memcache', 'servers' );
					break;

				case (
					true == $this->hba->getConfigSetting( 'databases', 'enabled' )
					&& false == $this->hba->runDatabaseFunction( 'default', 'isReadOnly' )
					&& true == $this->hba->runDatabaseFunction( 'default', 'isRedBean' )
				):
					$this->type = 'database';
					break;

				default:
					$this->type = 'filesystem';
					break;
			}
			$tf = sprintf( '%sCacheIsActive', strtolower( $this->type ) );
			$this->active = call_user_func( array( $this, $tf ) );
		}

		function active() {
			return $this->active;
		}

		function set( string $key, $value = null, int $exp = 0 ) {
			if ( ! $this->active() ) {
				return false;
			}
			$exp = __hba_sanitize_absint( $exp );
			if ( $exp < 1 ) {
				$exp = ( 86400 * 30 );
			}
			switch( $this->type ) {
				case 'redis':
					break;

				case 'memcached':
					break;

				case 'memcache':
					break;

				case 'database':
					$bean = $this->_get_bean_for_key( $key );
					echo '<pre>';
					var_dump( $bean );
					echo '</pre>';
					exit();
					break;

				case 'filesystem':
					$fn = $this->_get_filesystem_cache_filename( $key );
					$co = new \stdClass;
					$co->expires = time() + $exp;
					$co->value = $value;
					$fpc = file_put_contents( $fn, serialize( $co ), LOCK_EX );
					return ( __hba_sanitize_absint( $fpc ) > 0 );
					break;
			}
			return false;
		}

		function get( string $key, $default = null ) {
			if ( ! $this->active() ) {
				return false;
			}
			switch( $this->type ) {
				case 'redis':
					break;

				case 'memcached':
					break;

				case 'memcache':
					break;

				case 'database':
					break;

				case 'filesystem':
					$fn = $this->_get_filesystem_cache_filename( $key );
					if ( file_exists( $fn ) && is_readable( $fn ) ) {
						$serialiedString = file_get_contents( $fn );
						$co = @unserialize( $serialiedString );
						if ( time() < __hba_sanitize_absint( __hba_get_object_property( 'expires', $co, 0 ) ) ) {
							return __hba_get_object_property( 'value', $co, 0 );
						}
					}
					break;
			}
			return $default;
		}

		function trash( string $key ) {
			if ( ! $this->active() ) {
				return false;
			}
			switch( $this->type ) {
				case 'redis':
					break;

				case 'memcached':
					break;

				case 'memcache':
					break;

				case 'database':
					break;

				case 'filesystem':
					$fn = $this->_get_filesystem_cache_filename( $key );
					if ( file_exists( $fn ) && is_writable( $fn ) ) {
						return unlink( $fn );
					}
					break;
			}
			return false;
		}

		function purge() {
			if ( ! $this->active() ) {
				return false;
			}
			switch( $this->type ) {
				case 'redis':
					break;

				case 'memcached':
					break;

				case 'memcache':
					break;

				case 'database':
					break;

				case 'filesystem':
					$dir = __hba_get_array_key( 'cachedir', $this->_settings );
					$files = scandir( $dir );
					if ( __hba_can_loop( $files ) ) {
						foreach ( $files as $file ) {
							if ( __hba_ending_matches( '.hbcf', $file ) ) {
								$absf = sprintf( '%s/%s', \Hummingbird\HummingbirdApp::_hba_strip_trailing_slash( $dir ), $file );
								$remove = unlink( $absf );
								if ( true !== $remove ) {
									return false;
								}
							}
						}
					}
					return true;
					break;
			}
			return false;
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

		private function _get_bean_for_key( string $key ) {
			$cacheKey = md5( $key );
			$bean = $this->hba->runDatabaseFunction( 'default', 'findOne', __hba_get_array_key( 'beanType', $this->_settings ), 'cacheKey = :cacheKey', array( 'cacheKey' => $cacheKey ) );
			if ( ! is_a( $bean, '\RedBeanPHP\OODBBean') ) {
				$bean = $this->hba->runDatabaseFunction( 'default', 'dispense', __hba_get_array_key( 'beanType', $this->_settings ) );
				$bean->cacheKey = $cacheKey;
			}
			echo '<pre>';
			print_r( $bean );
			echo '</pre>';
		}

		private function _get_filesystem_cache_filename( string $key ) {
			$dir = __hba_get_array_key( 'cachedir', $this->_settings );
			return sprintf( '%s/%s.hbcf', \Hummingbird\HummingbirdApp::_hba_strip_trailing_slash( $dir ), md5( $key ) );
		}

		private function redisCacheIsActive() {
			$hosts = array();
			foreach ( $this->servers as $server ) {
				$host = __hba_get_array_key( 'host', $server );
				$port = __hba_sanitize_absint( __hba_get_array_key( 'port', $server, 6379 ) );
				$s = sprintf( '%s:%d', $host, $port );
				array_push( $hosts, $s );
			}
			$this->_settings['cobj'] = new \RedisArray( $hosts, array(
				'lazy_connect' => true,
			) );
			$ping = $this->_settings['cobj']->ping();
			return ( __hba_can_loop( $ping ) );
		}

		private function memcachedCacheIsActive() {
			$this->_settings['cobj'] = new \Memcached();
			if ( __hba_can_loop( $this->servers ) ) {
				foreach ( $this->servers as $server ) {
					$host = __hba_get_array_key( 'host', $server );
					$port = __hba_sanitize_absint( __hba_get_array_key( 'port', $server, 11211 ) );
					$priority = __hba_sanitize_absint( __hba_get_array_key( 'priority', $server, 10 ) );
					if ( ! __hba_is_empty( $host ) && $port > 0 ) {
						$add = call_user_func( array( $this->_settings['cobj'], 'addServer' ), $host, $port, $priority );
						if ( true !== $add ) {
							return false;
						}
					}
				}
			}
			return true;
		}

		private function memcacheCacheIsActive() {
			$this->_settings['cobj'] = new \Memcache();
			if ( __hba_can_loop( $this->servers ) ) {
				foreach ( $this->servers as $server ) {
					$host = __hba_get_array_key( 'host', $server );
					$port = __hba_sanitize_absint( __hba_get_array_key( 'port', $server ) );
					$priority = __hba_sanitize_absint( __hba_get_array_key( 'priority', $server, 10 ) );
					if ( ! __hba_is_empty( $host ) && $port > 0 ) {
						$add = call_user_func( array( $this->_settings['cobj'], 'addServer' ), $host, $port, $priority );
						if ( true !== $add ) {
							return false;
						}
					}
				}
			}
			return true;
		}

		private function databaseCacheIsActive() {
			$beanPrefix = md5( $this->hba->getConfigSetting( 'application', 'name' ) . $this->hba->getBaseDir() );
			$beanPrefix = preg_replace( '/[0-9]/', '', $beanPrefix );
			$this->_settings['beanType'] = sprintf( '%sAppDBCacheItem', $beanPrefix );
			return true;
		}

		private function filesystemCacheIsActive() {
			$tmp = $this->hba->getConfigSetting( 'application', 'tmpDir' );
			$cacheDir = sprintf( '%s/hummingbirdfilecache/%s/', \Hummingbird\HummingbirdApp::_hba_strip_trailing_slash( $tmp ), md5( $this->hba->getConfigSetting( 'application', 'name' ) . $this->hba->getBaseDir() ) );
			if ( ! is_writable( $tmp ) ) {
				return false;
			}
			$cdr = \Hummingbird\HummingbirdApp::_hba_strip_trailing_slash( substr( $cacheDir, strlen( $tmp ) + 1 ) );
			$checkPath = $tmp . '/';
			$parts = explode( '/', $cdr );
			foreach ( $parts as $part ) {
				$checkPath .= $part . '/';
				if ( ! file_exists( $checkPath ) ) {
					$made = mkdir( $checkPath, 0775 );
					if ( false == $made ) {
						return false;
					}
				}
				if ( ! is_writable( $checkPath ) ) {
					return false;
				}
				if ( ! is_dir( $checkPath ) ) {
					@unlink( $checkPath );
					mkdir( $checkPath, 0775 );
				}
			}
			$this->_settings['cachedir'] = $cacheDir;
			return true;
		}

		protected function getType() {
			return $this->type;
		}
	}