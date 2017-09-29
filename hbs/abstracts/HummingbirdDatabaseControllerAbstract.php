<?php
	namespace Hummingbird;

	abstract class HummingbirdDatabaseControllerAbstract implements \Hummingbird\HummingbirdDatabaseControllerInterface {
		protected $hba;
		private $key;
		private $type;
		private $host;
		private $port;
		private $name;
		private $user;
		private $pass;
		private $prefix;
		private $frozen = false;
		private $readonly = false;
		private $upn;

		function __construct( \Hummingbird\HummingbirdApp $hba, string $key, string $type = 'sqlite', string $host = '', int $port = 0, string $name = '/tmp/dbfile.db', string $user = '', string $pass = '', string $prefix = '', bool $frozen = false, bool $readonly = false ) {
			$this->hba = $hba;
			$this->key = $key;
			$this->type = $type;
			$this->host = $host;
			$this->port = $port;
			$this->name = $name;
			$this->user = $user;
			$this->pass = $pass;
			$this->prefix = $prefix;
			$this->frozen = ( true == $frozen );
			$this->readonly = ( true == $readonly );
			switch ( $type ) {
				case 'sqlite':
					$this->upn = sprintf( 'sqlite:%s', $this->name );
					break;

				default:
					$this->upn = sprintf( '%s:host=%s;port=%d;dbname=%s', $this->type, $this->host, $this->port, $this->name );
					break;
			}
			if ( 'default' == $key && $this->isRedBean() ) {
				\R::setup( $this->upn, $this->user, $this->pass, $this->frozen );
				$rbh = new \Hummingbird\HummingbirdBeanHelper;
				$redbean = \R::getRedBean();
				if ( is_object( $redbean ) ) {
					$redbean->setBeanHelper( $rbh );
				}
				\R::ext( 'sysDispense', array( $this, 'db_dispense' ) );
				\R::ext( 'sysDispenseAll', array( $this, 'db_dispense_all' ) );
				\R::ext( 'sysLoad', array( $this, 'db_load' ) );
				\R::ext( 'sysLoadAll', array( $this, 'db_load_all' ) );
				\R::ext( 'sysFind', array( $this, 'db_find' ) );
				\R::ext( 'sysFindOne', array( $this, 'db_find_one' ) );
				\R::ext( 'sysFindAll', array( $this, 'db_find_all' ) );
				\R::ext( 'sysCount', array( $this, 'db_count' ) );
				\R::ext( 'sysWipe', array( $this, 'db_wipe' ) );
			}
			else if ( $this->isRedBean() ) {
				try {
					\R::addDatabase( $key, $this->upn, $this->user, $this->pass, $this->frozen );
				}
				catch ( \Exception $e ) {}
			}
			else {
				$setupFunction = sprintf( '%s_init', strtolower( $this->type ) );
				call_user_func( array( $this, $setupFunction ) );
			}
		}

		function isConnected() {
			if ( $this->isRedBean() ) {
				return ! ( false === \R::testConnection() );
			}
			else {
				$f = sprintf( '%s_is_connected', strtolower( $this->type ) );
				return call_user_func( array( $this, $f ) );
			}
		}

		private function isRedBean() {
			return ( in_array( $this->type, array( 'sqlite', 'mysql', 'pgsql' ) ) );
		}

		private function db_dispense( $type, $param = null ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			if ( ! is_empty( $param ) ) {
				return \R::getRedBean()->dispense( $ot, $param );
			}
			return \R::getRedBean()->dispense( $ot );
		}

		private function db_dispense_all( $type, $param = null ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			if ( ! is_empty( $param ) ) {
				return \R::getRedBean()->dispenseAll( $ot, $param );
			}
			return \R::getRedBean()->dispenseAll( $ot );
		}

		private function db_load( $type, $id = 0 ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			return \R::getRedBean()->load( $ot, $id );
		}

		private function db_load_all( $type, $ids = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			return \R::getRedBean()->loadAll( $ot, $ids );
		}

		private function db_find( $type, $query = null, $vars = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			return \R::getRedBean()->find( $ot, array(), $query, $vars );
		}

		private function db_find_one( $type, $query = null, $vars = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$res = \R::sysFind( $type, $query, $vars );
			if ( ! can_loop( $res ) ) {
				return null;
			}
			$reskeys = array_keys( $res );
			return $res[ $reskeys[0] ];
		}

		private function db_find_all( $type, $query = null, $vars = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			return \R::getRedBean()->find( $ot, array(), $query, $vars );
		}

		private function db_count( $type, $query = null, $vars = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			if ( ! can_loop( $vars ) ) {
				return \R::getRedBean()->count( $ot, $query );
			}
			return \R::getRedBean()->count( $ot, $query, $vars );
		}

		private function db_wipe( $type ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			return \R::getRedBean()->wipe( $ot );
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
			if ( $this->isRedBean() ) {
				\R::selectDatabase( $this->key );
				return forward_static_call_array( array( '\R', $name ), $arguments );
			}
			else {
				$f = sprintf( '%s_%s', strtolower( $this->type ), $name );
				return call_user_func_array( array( $this, $f ), $arguments );
			}
		}

		public static function __callStatic( string $name, array $arguments = array() ) {
			return false;
		}
	}