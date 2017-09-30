<?php
	namespace Hummingbird;

	interface HummingbirdDatabaseControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba, string $key, string $type = 'sqlite', string $host = '', int $port = 0, string $name = '/tmp/dbfile.db', string $user = '', string $pass = '', string $prefix = '', bool $frozen = false, bool $readonly = false );
		public function isConnected();
		public function isReadOnly();
		public function getDBPrefix();
		public function db_dispense( $type, $param = null );
		public function db_dispense_all( $type, $param = null );
		public function db_load( $type, $id = 0 );
		public function db_load_all( $type, $ids = array() );
		public function db_find( $type, $query = null, $vars = array() );
		public function db_find_one( $type, $query = null, $vars = array() );
		public function db_find_all( $type, $query = null, $vars = array() );
		public function db_count( $type, $query = null, $vars = array() );
		public function db_wipe( $type );
	}