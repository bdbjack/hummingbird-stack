<?php
	namespace Hummingbird;

	abstract class HummingbirdSessionControllerAbstract implements \Hummingbird\HummingbirdSessionControllerInterface {
		protected $hba;

		function __construct( \Hummingbird\HummingbirdApp $hba ) {
			$this->hba = $hba;
		}

		function close() {
			// do nothing because we're depending on the cache
		}

		function destroy( string $sessionId ) {
			$cacheKey = md5( sprintf( 'session_%s', $sessionId ) );
			return $this->hba->runCacheFunction( 'trash', $cacheKey );
		}

		function gc( int $maxlifetime ) {
			// do nothing because we're depending on the cache
		}

		function open( string $savePath, string $sessionName ) {
			// do nothing because we're depending on the cache
		}

		function read( string $sessionId ) {
			$cacheKey = md5( sprintf( 'session_%s', $sessionId ) );
			return $this->hba->runCacheFunction( 'get', $cacheKey );
		}

		function write( string $sessionId, string $sessionData ) {
			$cacheKey = md5( sprintf( 'session_%s', $sessionId ) );
			return $this->hba->runCacheFunction( 'set', $cacheKey, $sessionData );
		}

		function getSessionSaveHandlerCallbackArray() {
			return array(
				array( $this, 'open' ),
				array( $this, 'close' ),
				array( $this, 'read' ),
				array( $this, 'write' ),
				array( $this, 'destroy' ),
				array( $this, 'gc' ),
			);
		}
	}