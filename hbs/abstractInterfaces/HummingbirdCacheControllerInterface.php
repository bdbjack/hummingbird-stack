<?php
	namespace Hummingbird;

	interface HummingbirdCacheControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function active();
		public function set( string $key, $value = null, int $exp = 0 );
		public function get( string $key, $default = null );
		public function trash( string $key );
		public function purge();
	}