<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Html_Feedback extends Feedback_Abstract {
		protected $mime = 'text/html';

		public function asOutput( bool $exit = true ) {
			header( sprintf( 'Content-Type: %s', $this->mime ) );
			http_response_code( 200 );
			$this->loadTemplateFile( 'header' );
			$action = HC::getArrayKey( 'action', $this->data, 'error' );
			$this->loadTemplateFile( $action );
			$this->loadTemplateFile( 'footer' );
			if ( true == $exit ) {
				exit();
			}
		}

		private function loadTemplateFile( $tpl ) {
			$tplf = sprintf( '%s/templates/%s.tpl.php', HC::stripTrailingSlash( ABSPATH ), $tpl );
			if ( file_exists( $tplf ) ) {
				require_once $tplf;
				return true;
			}
			return false;
		}
	}