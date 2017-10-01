<?php
	namespace Hummingbird;

	interface HummingbirdAuthenticationControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function validate( string $username = '', string $password = '' );
		public function getAuthFromHTTPBasic();
		public function getAuthFromHeader();
		public function getAuthFromCookie();
		public function getAuthFromSession();
		public function getAuthFromCLI();
	}