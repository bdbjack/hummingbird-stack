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

		function __construct( \Hummingbird\HummingbirdApp $hba, string $key, string $type = 'sqlite', string $host = '', int $port = 0, string $name = '/tmp/dbfile.db', string $user = '', string $pass = '', string $prefix = '', bool $frozen = false, bool $readonly = false ) {
			$this->hba = $hba;
			$this->key = $key;
			$this->type = $type;
			$this->host = $host;
			$this->port = $port;
			$this->user = $user;
			$this->pass = $pass;
			$this->prefix = $prefix;
			$this->frozen = ( true == $frozen );
			$this->readonly = ( true == $readonly );
		}

		function getResults() {

		}


	}