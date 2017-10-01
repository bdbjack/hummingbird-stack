<?php
	namespace Hummingbird;

	interface HummingbirdAuthenticationControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function validate( string $username = '', string $password = '' );
		public function isLoggedIn();
		public function getAuthFromHTTPBasic();
		public function getAuthFromHeader();
		public function getAuthFromCookie();
		public function setAuthToCookie();
		public function getAuthFromSession();
		public function setAuthToSession();
		public function getAuthFromCLI();
		public function getAuthSessionId();
	}