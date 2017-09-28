<?php
	namespace Hummingbird;

	interface HummingbirdFeedbackControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function getFeedbackType();
		public function outputFeedback( $exit = true );
		public function success( $data = null, string $message = '', array $errors = array(), int $status = 200 );
		public function failure( $data = null, string $message = '', array $errors = array(), int $status = 400 );
		public function debug( $data = null );
		public function redirect( string $location, int $delay = 0, int $type = 301 );
	}