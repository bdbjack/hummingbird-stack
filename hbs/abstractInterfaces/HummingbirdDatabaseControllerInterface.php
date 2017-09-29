<?php
	namespace Hummingbird;

	interface HummingbirdDatabaseControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba, string $key, string $type = 'sqlite', string $host = '', int $port = 0, string $name = '/tmp/dbfile.db', string $user = '', string $pass = '', string $prefix = '', bool $frozen = false, bool $readonly = false );
		public function getResults();
	}