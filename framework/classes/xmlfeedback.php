<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	class Xml_Feedback extends Feedback_Abstract {
		protected $mime = 'text/xml';

		public function asOutput( bool $exit = true ) {
			header( sprintf( 'Content-Type: %s', $this->mime ) );
			http_response_code( $this->code );
			echo $this->xml_encode( $this );
			if ( true == $exit ) {
				exit();
			}
		}

		private function xml_encode( $input ) {
			$xml = new SimpleXMLElement('<?xml version="1.0"?><feedback></feedback>');
			$xmla = json_decode( json_encode( $input ), true );
			$this->array_to_xml( $xmla, $xml );
			return $this->pretty_print_xml( $xml );
		}

		private function array_to_xml( $data, &$xml, $id = null ) {
			if ( HC::canLoop( $data ) ) {
				foreach ( $data as $key => $value ) {
					if ( is_numeric( $key ) ) {
						$key = sprintf( 'item_%s', $key );
					}
					if ( is_array( $value ) ) {
						$subnode = $xml->addChild( $key );
						$this->array_to_xml( $value, $subnode );
					}
					else {
						if ( false !== strpos( $key, 'item_' ) ) {
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

		private function pretty_print_xml( $xml ) {
			if ( ! is_a( $xml, 'SimpleXMLElement' ) ) {
				return '';
			}
			$dom = new DOMDocument( "1.0" );
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dom->loadXML( $xml->asXML() );
			return $dom->saveXML();
		}
	}