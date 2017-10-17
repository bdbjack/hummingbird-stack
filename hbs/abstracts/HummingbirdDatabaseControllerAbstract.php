<?php
	namespace Hummingbird;

	abstract class HummingbirdDatabaseControllerAbstract implements \Hummingbird\HummingbirdDatabaseControllerInterface {
		protected $hba;
		protected $key;
		protected $type;
		protected $host;
		protected $port;
		protected $name;
		protected $user;
		protected $pass;
		protected $prefix;
		protected $frozen = false;
		protected $readonly = false;
		protected $upn;
		protected $client;

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

				case 'elasticsearch':
					$protocol = ( 'http' == strtolower( $this->prefix ) ) ? 'http' : 'https';
					if ( ! __hba_is_empty( $this->user ) && ! __hba_is_empty( $this->pass ) ) {
						$this->upn = sprintf( '%s://%s:%s@%s:%d', $protocol, $this->user, $this->pass, $this->host, $this->port );
					}
					else {
						$this->upn = sprintf( '%s://%s:%d', $protocol, $this->host, $this->port );
					}
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
				\R::ext( 'prefixedDispense', array( $this, 'db_dispense' ) );
				\R::ext( 'prefixedDispenseAll', array( $this, 'db_dispense_all' ) );
				\R::ext( 'prefixedLoad', array( $this, 'db_load' ) );
				\R::ext( 'prefixedLoadAll', array( $this, 'db_load_all' ) );
				\R::ext( 'prefixedFind', array( $this, 'db_find' ) );
				\R::ext( 'prefixedFindOne', array( $this, 'db_find_one' ) );
				\R::ext( 'prefixedFindAll', array( $this, 'db_find_all' ) );
				\R::ext( 'prefixedCount', array( $this, 'db_count' ) );
				\R::ext( 'prefixedWipe', array( $this, 'db_wipe' ) );
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

		function isReadOnly() {
			return $this->readonly;
		}

		function isRedBean() {
			return ( in_array( $this->type, array( 'sqlite', 'mysql', 'pgsql' ) ) );
		}

		function getDBPrefix() {
			return $this->prefix;
		}

		function db_dispense( $type, $param = null ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			if ( ! is_empty( $param ) ) {
				return \R::getRedBean()->dispense( $ot, $param );
			}
			return \R::getRedBean()->dispense( $ot );
		}

		function db_dispense_all( $type, $param = null ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			if ( ! is_empty( $param ) ) {
				return \R::getRedBean()->dispenseAll( $ot, $param );
			}
			return \R::getRedBean()->dispenseAll( $ot );
		}

		function db_load( $type, $id = 0 ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			return \R::getRedBean()->load( $ot, $id );
		}

		function db_load_all( $type, $ids = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			return \R::getRedBean()->loadAll( $ot, $ids );
		}

		function db_find( $type, $query = null, $vars = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			return \R::getRedBean()->find( $ot, array(), $query, $vars );
		}

		function db_find_one( $type, $query = null, $vars = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			$res = \R::prefixedFind( $type, $query, $vars );
			if ( ! can_loop( $res ) ) {
				return null;
			}
			$reskeys = array_keys( $res );
			return $res[ $reskeys[0] ];
		}

		function db_find_all( $type, $query = null, $vars = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			return \R::getRedBean()->find( $ot, array(), $query, $vars );
		}

		function db_count( $type, $query = null, $vars = array() ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			if ( ! can_loop( $vars ) ) {
				return \R::getRedBean()->count( $ot, $query );
			}
			return \R::getRedBean()->count( $ot, $query, $vars );
		}

		function db_wipe( $type ) {
			$ot = sprintf( '%s%s', $this->prefix, $type );
			$ot = $this->fixBeanType( $ot );
			return \R::getRedBean()->wipe( $ot );
		}

		function getParam( $key ) {
			if (
				'hba' !== $key
				&& 'pass' !== $key
				&& 'upn' !== $key
			) {
				return $this->{$key};
			}
			return false;
		}

		function noSQL() {
			$nscc = $this->hba->getConfigSetting( 'databases', 'nosqlController' );
			if ( __hba_is_instance_of( $nscc, 'Hummingbird\HummingbirdNoSQLControllerInterface' ) ) {
				return new $nscc( $this );
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
			switch ( true ) {
				case $this->isRedBean():
					\R::selectDatabase( $this->key );
					if ( ! __hba_is_empty( $this->prefix ) && true !== $this->readonly && in_array( $name, array(
						'dispense','dispenseAll','load','loadAll','find','findOne','findAll','count','wipe',
					) ) ) {
						$name = sprintf( 'prefixed%s', ucfirst( $name ) );
					}
					return forward_static_call_array( array( '\R', $name ), $arguments );
					break;

				case 'elasticsearch' == $this->type && is_a( $this->client, '\Elasticsearch\Client' ):
					return call_user_func_array( array( $this->client, $name ), $arguments );
					break;

				default:
					$f = sprintf( '%s_%s', strtolower( $this->type ), $name );
					if ( method_exists( $this, $f ) ) {
						return call_user_func_array( array( $this, $f ), $arguments );
					}
					break;
			}
			return false;
		}

		static function __callStatic( string $name, array $arguments = array() ) {
			return false;
		}

		private function fixBeanType( $input ) {
			return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $input ) );
		}

		private function elasticsearch_init() {
			if ( class_exists( '\Elasticsearch\ClientBuilder' ) ) {
				$cb = \Elasticsearch\ClientBuilder::create();
				$cb->setHosts( array( $this->upn ) );
				$client = $cb->build();
				if ( is_a( $client, '\Elasticsearch\Client' ) ) {
					$this->client = $client;
					$indexParams = array( 'index' => $this->name );
					try {
						$indexExists = $this->client->indices()->exists( $indexParams );
					}
					catch ( \Exception $e ) {
						$indexExists = false;
					}
					if ( false == $indexExists ) {
						try {
							$create = $this->client->indices()->create( $indexParams );
						}
						catch ( \Exception $e ) {
							throw new \Exception( sprintf( 'Could not create index "%s"', $this->name ), 1 );
						}
					}
				}
			}
			else {
				throw new \Exception( 'Missing Elasticsearch Libraries', 1 );
			}
		}
	}