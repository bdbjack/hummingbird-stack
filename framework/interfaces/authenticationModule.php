<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	interface HBS_Authentication_Module_Interface {
		public function isLoggedIn();
		public function validateCredentials( string $username, string $password );
		public function createUser( string $username, string $password, array $additional = array() );
	}