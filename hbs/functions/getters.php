<?php
	function __hba_get_array_key( $key, $array = array(), $default = null ) {
		return ( is_array( $array ) && array_key_exists( $key, $array ) ) ? $array[ $key ] : $default;
	}

	function __hba_get_defined_value( $key, $default = null ) {
		return ( defined( $key ) && ! __hba_is_empty( constant( $key ) ) ) ? constant( $key ) : $default;
	}

	function __hba_get_object_property( $key, $obj, $default = null ) {
		return ( is_object( $obj ) && property_exists( $obj, $key ) ) ? $obj->{$key} : $default;
	}

	function __hba_get_written_similarity_percent( string $string1, string $string2, bool $caseInsensative = true ) {
		if ( true == $caseInsensative ) {
			$string1 = strtolower( $string1 );
			$string2 = strtolower( $string2 );
		}
		$ratio = __hba_levenshtein_ratio( $string1, $string2 );
		return $ratio / 100;
	}

	function __hba_get_sounding_similarity_percent( string $string1, string $string2 ) {
		$mps1 = metaphone( $string1 );
		$mps2 = metaphone( $string2 );
		$ratio = __hba_levenshtein_ratio( $mps1, $mps2 );
		return $ratio / 100;
	}

	function __hba_get_country_info_by_name( string $name ) {
		global $__hba_countries;
		$liklihood = array();
		if ( __hba_can_loop( $__hba_countries ) ) {
			foreach ( $__hba_countries as $iso => $data ) {
				$liklihood[ $iso ] = __hba_get_sounding_similarity_percent( __hba_get_array_key( 'name', $data ), $name );
			}
		}
		arsort( $liklihood );
		$ordered = array_keys( $liklihood );
		foreach ( $ordered as $iso ) {
			$cn = __hba_get_array_key( 'name', __hba_get_array_key( $iso, $__hba_countries, array() ), 'Unknown' );
			if ( false !== strpos( strtolower( $cn ), strtolower( $name ) ) ) {
				$liklihood[ $iso ] = $liklihood[$iso] + 0.5;
			}
		}
		arsort( $liklihood );
		$ordered = array_keys( $liklihood );
		return __hba_get_array_key( array_shift( $ordered ), $__hba_countries, array() );
	}

	function __hba_guess_country_from_phone_number( string $phone ) {
		global $__hba_countries;
		$similarity = array();
		if ( __hba_can_loop( $__hba_countries ) ) {
			foreach ( $__hba_countries as $iso => $data ) {
				if ( 'UK' !== $iso ) {
					$prefix = __hba_get_array_key( 'prefix', $data );
					if ( __hba_beginning_matches( $prefix, $phone ) && strlen( $prefix ) > 0 ) {
						$score = 0;
						if ( \Hummingbird\HummingbirdPhoneInterface::isValid( $phone, $iso ) ) {
							$score = $score + 1;
						}
						if ( \Hummingbird\HummingbirdPhoneInterface::isMobile( $phone, $iso ) ) {
							$score = $score + 1;
						}
						$similarity[ $iso ] = $score;
					}
				}
			}
		}
		arsort( $similarity );
		$keys = array_keys( $similarity );
		return __hba_can_loop( $keys ) ? array_shift( $keys ) : 'XX';
	}