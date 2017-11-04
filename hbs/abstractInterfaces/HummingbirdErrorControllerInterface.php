<?php
	namespace Hummingbird;

	interface HummingbirdErrorControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function handleError( int $errno, string $errstr, string $errfile = '', int $errline = 0 );
		public function handleException( $ex );
		public function handlePHP7Exception( $ex );
		public function getExceptionHandlerFunctionName();
		public function setLogFile( string $file );
		public function writeToLogFile( string $msg = '' );
		public function reportToNewRelic( string $message, $exception = null );
		public function showFeedback();
		public function throwException( $msg );
	}