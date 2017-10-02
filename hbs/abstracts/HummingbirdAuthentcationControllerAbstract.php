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
			$ac = sprintf( '%s_ac', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			$authorization = $this->hba->runRequestFunction( 'getCookie', $ac );
			if ( ! __hba_is_empty( $authorization ) ) {
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

		function setAuthToCookie( string $username = '', string $password = '' ) {
			$ac = sprintf( '%s_ac', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			$as = sprintf( '%s:%s', $username, $password );
			$hash = $this->hashed_to( $as );
			return $this->hba->runRequestFunction( 'setCookie', $ac, $hash );
		}

		function getAuthFromSession() {
			$sauk = sprintf( '%s_user', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			$sapk = sprintf( '%s_pass', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			return array(
				'user' => __hba_get_array_key( $sauk, $_SESSION, '' ),
				'pass' => __hba_get_array_key( $sapk, $_SESSION, '' ),
			);
		}

		function setAuthToSession( string $username = '', string $password = '' ) {
			$sauk = sprintf( '%s_user', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			$sapk = sprintf( '%s_pass', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			$_SESSION[ $sauk ] = $username;
			$_SESSION[ $sapk ] = $password;
			return $this->hba->getConfigSetting( 'session', 'enabled' );
		}

		function getAuthFromCLI() {
			$vars = getopt( '', array( 'user:', 'pass:' ) );
			return array(
				'user' => __hba_get_array_key( 'user', $vars ),
				'pass' => __hba_get_array_key( 'pass', $vars ),
			);
		}

		function getAuthSessionId() {
			$sk = sprintf( '%s_auth_session', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			$vars = getopt( '', array( 'authsession:' ) );
			$auth = __hba_get_array_key( $sk, $_SESSION, '' );
			if ( __hba_is_empty( $auth ) ) {
				$auth = $this->hashed_from( $this->hba->runRequestFunction( 'getCookie', $sk ) );
			}
			if ( __hba_is_empty( $auth ) ) {
				$auth = $this->hba->runRequestFunction( 'getRequestHeaders', 'X-Auth-Session' );
			}
			if ( __hba_is_empty( $auth ) ) {
				$auth = __hba_get_array_key( 'authsession', $vars, '' );
			}
			return $auth;
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