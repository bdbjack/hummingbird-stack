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

		public function asOutput() {
			return false;
		}

		public static function SUCCESS( $data, $message = null, array $errors = array(), int $status = 200, bool $output = false ) {
			$c = get_called_class();
			$obj = new $c;
			$obj->status = 'SUCCESS';
			$obj->data = $data;
			$obj->message = $message;
			$obj->errors = $errors;
			$obj->code = absint( $status );
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
			$obj->code = absint( $status );
			if ( true == $output ) {
				$obj->asOutput();
			}
			return $obj;
		}

	}