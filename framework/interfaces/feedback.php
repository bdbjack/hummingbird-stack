<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	interface Feedback_Interface {
		public function asOutput( bool $exit = true );
		public static function SUCCESS( $data, $message = null, array $errors = array(), int $status = 200, bool $output = false );
		public static function FAILURE( $data, $message = null, array $errors = array(), int $status = 400, bool $output = false );
		public static function DEBUG( $data, $message = null, array $errors = array(), int $status = 400, bool $output = false );
	}