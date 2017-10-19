<?php
	namespace Hummingbird;

	abstract class HummingbirdFeedbackControllerAbstract implements \Hummingbird\HummingbirdFeedbackControllerInterface {
		protected $hba;
		protected $fbo;

		function __construct( \Hummingbird\HummingbirdApp $hba ) {
			$this->hba = $hba;
			$this->fbo = $this->generateFeedbackObject();
		}

		function getFeedbackType() {
			$returnMime = $this->getReturnMime();
			switch ( true ) {
				case __hba_matches_regex( $returnMime, 'json' ):
					return 'json';
					break;

				case __hba_matches_regex( $returnMime, 'html' ):
					return 'html';
					break;

				case __hba_matches_regex( $returnMime, 'xml' ):
					return 'xml';
					break;

				default:
					return 'plaintext';
					break;
			}
		}

		function outputFeedback( $exit = true ) {
			$fbt = $this->getFeedbackType();
			switch( $fbt ) {
				case 'html':
					http_response_code( $this->fbo->code );
					header( 'Content-Type: text/html' );
					echo $this->getHtmlFeedback();
					break;

				case 'json':
					http_response_code( $this->fbo->code );
					header( 'Content-Type: application/json' );
					echo $this->hba->doFilter( 'filterJSONFeedback', json_encode( $this->fbo, JSON_PRETTY_PRINT ) );
					break;

				case 'xml':
					http_response_code( $this->fbo->code );
					header( 'Content-Type: text/xml' );
					echo $this->hba->doFilter( 'filterXMLFeedback', $this->xml_encode( $this->fbo ) );
					break;

				default:
					http_response_code( $this->fbo->code );
					header( 'Content-Type: text/plain' );
					if ( method_exists( $this, 'getPlaintextFeedback' ) ) {
						echo $this->hba->doFilter( 'filterPLAINTEXTFeedback', $this->getPlaintextFeedback() );
					}
					else {
						$output = '';
						$output .= sprintf( 'Status: %s', $this->fbo->status ) . "\r\n";
						$output .= sprintf( 'Data: %s', print_r( $this->fbo->data, true ) ) . "\r\n";
						$output .= sprintf( 'Message: %s', $this->fbo->message ) . "\r\n";
						$output .= sprintf( 'Errors: %s', print_r( $this->fbo->errors, true ) ) . "\r\n";
						$output .= sprintf( 'Code: %d', $this->fbo->code ) . "\r\n";
						echo $output;
					}
					break;
			}
			if ( true == $exit ) {
				exit();
			}
		}

		function success( $data = null, string $message = '', array $errors = array(), int $status = 200 ) {
			$this->fbo = $this->generateFeedbackObject( 'SUCCESS', $data, $message, $errors, $status );
			return true;
		}

		function failure( $data = null, string $message = '', array $errors = array(), int $status = 400 ) {
			$this->fbo = $this->generateFeedbackObject( 'FAILURE', $data, $message, $errors, $status );
			return true;
		}

		function debug( $data = null ) {
			$this->fbo = $this->generateFeedbackObject( 'DEBUG', $data, 'Debug', array(), 200 );
			return true;
		}

		function redirect( string $location, int $delay = 0, int $type = 301, $follow = true ) {
			if ( headers_sent() || false == $follow ) {
				switch( $type ) {
					case 301:
						$msg = 'Moved Permanently';
						break;

					case 302:
						$msg = 'Temporary Redirect';
						break;

					case 307:
						$msg = 'Temporary Redirect';
						break;

					default:
						$msg = 'Redirect';
						break;
				}
				$this->fbo = $this->generateFeedbackObject( 'REDIRECT', $location, $msg, array(), $type );
				$this->outputFeedback();
			}
			else {
				if ( 0 == __hba_sanitize_absint( $delay ) ) {
					http_response_code( $type );
					header( sprintf( 'Location: %s', $location ) );
					exit();
				}
				else {
					http_response_code( $type );
					header( sprintf( 'Refresh: %d; url=%s', $delay, $location ) );
					exit();
				}
			}
		}

		function cli_echo( $input ) {
			$args = func_get_args();
			if ( ! is_object( $input ) && ! is_array( $input ) ) {
				echo call_user_func_array( 'sprintf', $args );
			}
			else {
				echo print_r( $input, true );
			}
			echo "\r\n";
		}

		protected function getHtmlFeedback() {
			$output = '';
			$output .= '<pre>' . "\r\n";
			$output .= sprintf( 'Status: %s', $this->fbo->status ) . "\r\n";
			$output .= sprintf( 'Data: %s', print_r( $this->fbo->data, true ) ) . "\r\n";
			$output .= sprintf( 'Message: %s', $this->fbo->message ) . "\r\n";
			$output .= sprintf( 'Errors: %s', print_r( $this->fbo->errors, true ) ) . "\r\n";
			$output .= sprintf( 'Code: %d', $this->fbo->code ) . "\r\n";
			$output .= '</pre>' . "\r\n";
			return $output;
		}

		protected function getReturnMime() {
			$return = 'text/plain';
			$headers = $this->parseHttpHeaders();
			$accepted = __hba_get_array_key( 'Accept', $headers, '*/*' );
			if ( false == strpos( $accepted, ',' ) ) {
				$mimes = array( $accepted );
			}
			else {
				$mimes = explode( ',', $accepted );
			}
			$first = array_shift( $mimes );
			if ( '*/*' !== trim( $first ) ) {
				$return = strtolower( $first );
			}
			return $return;
		}

		protected function parseHttpHeaders() {
			$_server = $_SERVER;
			if ( function_exists( 'getallheaders' ) ) {
				$headers = getallheaders();
				if ( __hba_can_loop( $headers ) ) {
					foreach ( $headers as $key => $value ) {
						$headers[ ucwords( $key ) ] = $value;
					}
				}
				return $headers;
			}
			$return = array();
			foreach ( $_server as $key => $value ) {
				if ( substr( strtoupper( $key ), 0, 5 ) == 'HTTP_' ) {
					$key = substr( $key, 0, 5 );
					$key = str_replace( '_', ' ', $key );
					$key = ucwords( strtolower( $key ) );
					$key = str_replace( ' ', '-', $key );
					$key = ucwords( $key );
					$return[ $key ] = $value;
				}
			}
			return $return;
		}

		protected function generateFeedbackObject( string $status = 'FAILURE', $data = null, string $message = '', array $errors = array(), int $code = 0 ) {
			$obj = new \stdClass;
			$obj->status = $status;
			$obj->data = $data;
			$obj->message = $message;
			$obj->errors = $errors;
			$obj->code = $code;
			return $obj;
		}

		protected function xml_encode( $input ) {
			$xml = new \SimpleXMLElement('<?xml version="1.0"?><feedback></feedback>');
			$xmla = json_decode( json_encode( $input ), true );
			$this->array_to_xml( $xmla, $xml );
			return $this->pretty_print_xml( $xml );
		}

		protected function array_to_xml( $data, &$xml, $id = null ) {
			if ( __hba_can_loop( $data ) ) {
				foreach ( $data as $key => $value ) {
					if ( is_numeric( $key ) ) {
						$key = sprintf( 'item_%s', $key );
					}
					$this->makeXMLKeySafe( $key );
					if ( is_array( $value ) ) {
						if ( __hba_beginning_matches( 'item_', $key ) ) {
							$itemId = str_replace( 'item_', '', $key );
							$key = 'item';
							$subnode = $xml->addChild( $key );
							$subnode->addAttribute( 'id', $itemId );
							$this->array_to_xml( $value, $subnode );
						}
						else {
							$subnode = $xml->addChild( $key );
							$this->array_to_xml( $value, $subnode );
						}
					}
					else {
						if ( __hba_beginning_matches( 'item_', $key ) ) {
							$itemId = str_replace( 'item_', '', $key );
							$key = 'item';
							$child = $xml->addChild( $key, htmlspecialchars( $value ) );
							$child->addAttribute( 'id', $itemId );
						}
						else {
							$child = $xml->addChild( $key, htmlspecialchars( $value ) );
						}
					}
				}
			}
		}

		protected function makeXMLKeySafe( &$key ) {
			$key = trim( $key );
			if ( preg_match("/[^A-Za-z0-9]/", $key ) ) {
				$key = ucwords( $key );
				$key = lcfirst( $key );
			}
			$key = preg_replace( '/[^A-Za-z0-9]/', '', $key );
			return $key;
		}

		protected function pretty_print_xml( $xml ) {
			if ( ! is_a( $xml, '\SimpleXMLElement' ) ) {
				return '';
			}
			$dom = new \DOMDocument( "1.0" );
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML( $xml->asXML() );
			return $dom->saveXML();
		}
	}