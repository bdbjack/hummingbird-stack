<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	abstract class Feedback_Abstract implements Feedback_Interface {
		public $status = 'FAILURE';
		public $data = array();
		public $message = 'Nothing Happened';
		public $errors = array(
			'nothingHappened' => 'Nothing Happened',
		);
		public $code = 0;
		protected $mime = 'text/plain';

		public function asOutput( bool $exit = true ) {
			header( sprintf( 'Content-Type: %s', $this->mime ) );
			http_response_code( $this->code );
			$output = '';
			$output .= sprintf( 'Status: %s', $this->status ) . "\r\n";
			$output .= sprintf( 'Data: %s', print_r( $this->data, true ) ) . "\r\n";
			$output .= sprintf( 'Message: %s', $this->message ) . "\r\n";
			$output .= sprintf( 'Errors: %s', print_r( $this->errors, true ) ) . "\r\n";
			$output .= sprintf( 'Code: %d', $this->code ) . "\r\n";
			echo $output;
			if ( true == $exit ) {
				exit();
			}
		}

		public static function SUCCESS( $data, $message = null, array $errors = array(), int $status = 200, bool $output = false ) {
			$c = get_called_class();
			$obj = new $c;
			$obj->status = 'SUCCESS';
			$obj->data = $data;
			$obj->message = $message;
			$obj->errors = $errors;
			$obj->code = HC::absInt( $status );
			if ( true == $output ) {
				$obj->asOutput();
			}
			return $obj;
		}

		public static function FAILURE( $data, $message = null, array $errors = array(), int $status = 400, bool $output = false ) {
			$c = get_called_class();
			$obj = new $c;
			$obj->status = 'FAILURE';
			$obj->data = $data;
			$obj->message = $message;
			$obj->errors = $errors;
			$obj->code = HC::absInt( $status );
			if ( true == $output ) {
				$obj->asOutput();
			}
			return $obj;
		}

	}