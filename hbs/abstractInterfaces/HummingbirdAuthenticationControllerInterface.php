<?php
	namespace Hummingbird;

	interface HummingbirdAuthenticationControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function validate( string $username = '', string $password = '' );
		public function isLoggedIn();
		public function getCurrentUserId();
		public function getAuthFromHTTPBasic();
		public function getAuthFromHeader();
		public function getAuthFromCookie();
		public function setAuthToCookie( string $username = '', string $password = '' );
		public function getAuthFromSession();
		public function setAuthToSession( string $username = '', string $password = '' );
		public function getAuthFromCLI();
		public function getAuthSessionId();
		public function createAuthSession( $userId = null, bool $store = true );
		public function destroyAuthSession();
	}