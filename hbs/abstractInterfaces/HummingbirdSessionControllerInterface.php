<?php
	namespace Hummingbird;

	interface HummingbirdSessionControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function close();
		public function destroy( string $sessionId );
		public function gc( int $maxlifetime );
		public function open( string $savePath, string $sessionName );
		public function read( string $sessionId );
		public function write( string $sessionId, string $sessionData );
	}