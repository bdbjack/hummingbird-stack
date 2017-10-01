<?php
	namespace Hummingbird;

	abstract class HummingbirdAuthentcationControllerAbstract implements \Hummingbird\HummingbirdAuthenticationControllerInterface {
		protected $hba;

		function __construct( \Hummingbird\HummingbirdApp $hba ) {
			$this->hba = $hba;
		}

		function validate( string $username = '', string $password = '' ) {
			return false;
		}

		function isLoggedIn() {
			return false;
		}

		function getAuthFromHTTPBasic() {
			$authorization = $this->hba->runRequestFunction( 'getRequestHeaders', 'Authorization' );
			if ( ! __hba_is_empty( $authorization ) && __hba_beginning_matches( 'Basic ', $authorization ) ) {
				$auth64 = substr( $authorization, strlen( 'Basic ' ) );
				$authraw = base64_decode( $auth64 );
				list( $user, $pass ) = explode( ':', $authraw );
			}
			else {
				$user = '';
				$pass = '';
			}
			return array(
				'user' => $user,
				'pass' => $pass,
			);
		}

		function getAuthFromHeader() {
			return array(
				'user' => $this->hba->runRequestFunction( 'getRequestHeaders', 'X-Auth-User' ),
				'pass' => $this->hba->runRequestFunction( 'getRequestHeaders', 'X-Auth-Pass' ),
			);
		}

		function getAuthFromCookie() {
			$authorization = $this->hba->runRequestFunction( 'getCookie', '' );
			if ( ! __hba_is_empty( $authorization ) && __hba_beginning_matches( 'Basic ', $authorization ) ) {
				$authraw = $this->hashed_from( $authorization );
				list( $user, $pass ) = explode( ':', $authraw );
			}
			else {
				$user = '';
				$pass = '';
			}
			return array(
				'user' => $user,
				'pass' => $pass,
			);
		}

		function setAuthToCookie() {

		}

		function getAuthFromSession() {
			$sauk = sprintf( '%s_user', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			$sapk = sprintf( '%s_pass', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			return array(
				'user' => __hba_get_array_key( $sauk, $_SESSION, '' ),
				'pass' => __hba_get_array_key( $sapk, $_SESSION, '' ),
			);
		}

		function setAuthToSession() {

		}

		function getAuthFromCLI() {
			return array(
				'user' => '',
				'pass' => '',
			);
		}

		function getAuthSessionId() {

		}

		protected function hashed_to( $val ) {
			global $config;
			return base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, substr( md5( $this->hba->getConfigSetting( 'application', 'name' ) ), 0, 8 ), $val, MCRYPT_MODE_ECB ) );
		}

		protected function hashed_from( $val ) {
			global $config;
			return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, substr( md5( $this->hba->getConfigSetting( 'application', 'name' ) ), 0, 8 ), base64_decode( $val ), MCRYPT_MODE_ECB ) );
		}
	}