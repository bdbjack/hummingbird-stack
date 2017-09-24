<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Json_Feedback extends Feedback_Abstract {
		protected $mime = 'application/json';

		public function asOutput( bool $exit = true ) {
			header( sprintf( 'Content-Type: %s', $this->mime ) );
			http_response_code( $this->code );
			echo json_encode( $this, JSON_PRETTY_PRINT );
			if ( true == $exit ) {
				exit();
			}
		}
	}