<?php
	namespace Hummingbird;

	interface HummingbirdNoSQLControllerInterface {
		public function __construct( \Hummingbird\HummingbirdDatabaseControllerInterface $dbc );
		public function dispense( string $type );
		public function load( string $type, string $id );
		public function loadAll( string $type, array $ids );
		public function find( $type, $query );
		public function findOne( $type, $query );
		public function store( \Hummingbird\noSQLObject $object );
		public function storeAll( $objects );
		public function trash( \Hummingbird\noSQLObject $object );
		public function trashAll( $objects );
		public function wipe( $type );
		public function nuke();
	}