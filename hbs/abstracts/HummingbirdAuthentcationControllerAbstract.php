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
			return ( ! __hba_is_empty( $this->getCurrentUserId() ) );
		}

		function getCurrentUserId() {
			$si = $this->getAuthSessionId();
			$ck = sprintf( '%s_authsession_%s', md5( $this->hba->getConfigSetting( 'application', 'name' ) ), $si );
			$ci = $this->hba->runCacheFunction( 'get', $ck );
			if (
				is_object( $ci )
				&& property_exists( $ci, 'id' )
				&& $si == $ci->id
				&& property_exists( $ci, 'userIP' )
				&& $ci->userIP == $this->hba->runRequestFunction( 'getCurrentUserIP' )
				&& property_exists( $ci, 'userAgent' )
				&& $ci->userAgent == $this->hba->runRequestFunction( 'getRequestHeaders', 'User-Agent' )
				&& property_exists( $ci, 'sessionTime' )
				&& $ci->sessionTime <= time()
				&& time() <= $ci->sessionTime + ( 86400 * 30 )
			) {
				return $ci->userId;
			}
			return null;
		}

		function getAuthFromHTTPBasic() {
			if ( true !== $this->hba->getConfigSetting( 'authentication', 'enabled' ) || true !== $this->hba->getConfigSetting( 'authentication', 'allowHTTPBasicAuth' ) ) {
				return false;
			}
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
			if ( true !== $this->hba->getConfigSetting( 'authentication', 'enabled' ) || true !== $this->hba->getConfigSetting( 'authentication', 'allowHTTPHeaderAuth' ) ) {
				return false;
			}
			return array(
				'user' => $this->hba->runRequestFunction( 'getRequestHeaders', 'X-Auth-User' ),
				'pass' => $this->hba->runRequestFunction( 'getRequestHeaders', 'X-Auth-Pass' ),
			);
		}

		function getAuthFromCookie() {
			if ( true !== $this->hba->getConfigSetting( 'authentication', 'enabled' ) || true !== $this->hba->getConfigSetting( 'authentication', 'allowHTTPCookieAuth' ) ) {
				return false;
			}
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
			if ( true !== $this->hba->getConfigSetting( 'authentication', 'enabled' ) || true !== $this->hba->getConfigSetting( 'authentication', 'allowSessionAuth' ) ) {
				return false;
			}
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
			if ( true !== $this->hba->getConfigSetting( 'authentication', 'enabled' ) || true !== $this->hba->getConfigSetting( 'authentication', 'allowCLIAuth' ) ) {
				return false;
			}
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
				$cookie = $this->hba->runRequestFunction( 'getCookie', $sk );
				$auth = ( __hba_is_empty( $cookie ) ) ? '' : $this->hashed_from( $cookie );
			}
			if ( __hba_is_empty( $auth ) ) {
				$auth = $this->hba->runRequestFunction( 'getRequestHeaders', 'X-Auth-Session' );
			}
			if ( __hba_is_empty( $auth ) ) {
				$auth = __hba_get_array_key( 'authsession', $vars, '' );
			}
			return $auth;
		}

		function createAuthSession( $userId = null, bool $store = true ) {
			if ( __hba_is_empty( $userId ) ) {
				return false;
			}
			$aso = new \stdClass();
			$aso->id = md5( time() * rand( 1, 100 ) );
			$aso->userId = $userId;
			$aso->userIP = $this->hba->runRequestFunction( 'getCurrentUserIP' );
			$aso->userAgent = $this->hba->runRequestFunction( 'getRequestHeaders', 'User-Agent' );
			$aso->sessionTime = time();
			$ck = sprintf( '%s_authsession_%s', md5( $this->hba->getConfigSetting( 'application', 'name' ) ), $aso->id );
			$storedInCache = $this->hba->runCacheFunction( 'set', $ck, $aso );
			if ( true == $storedInCache && true == $store ) {
				$sk = sprintf( '%s_auth_session', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
				$_SESSION[ $sk ] = $aso->id;
				$this->hba->runRequestFunction( 'setCookie', $sk, $this->hashed_to( $aso->id ) );
			}
			return $aso->id;
		}

		function destroyAuthSession() {
			$si = $this->getAuthSessionId();
			$ck = sprintf( '%s_authsession_%s', md5( $this->hba->getConfigSetting( 'application', 'name' ) ), $si );
			$sk = sprintf( '%s_auth_session', md5( $this->hba->getConfigSetting( 'application', 'name' ) ) );
			$ci = $this->hba->runCacheFunction( 'trash', $ck );
			unset( $_SESSION[ $sk ] );
			$this->hba->runRequestFunction( 'unsetCookie', $sk );
			return true;
		}

		protected function hashed_to( $val ) {
			global $config;
			return base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, substr( md5( $this->hba->getConfigSetting( 'application', 'name' ) ), 0, 16 ), $val, MCRYPT_MODE_ECB ) );
		}

		protected function hashed_from( $val ) {
			global $config;
			return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, substr( md5( $this->hba->getConfigSetting( 'application', 'name' ) ), 0, 16 ), base64_decode( $val ), MCRYPT_MODE_ECB ) );
		}
	}