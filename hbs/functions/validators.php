<?php
	function __hba_can_loop( $data ) {
		return ( is_array( $data ) && count( $data ) > 0 );
	}

	function __hba_is_loopable( $data ) {
		return ( is_array( $data ) && count( $data ) > 0 );
	}

	function __hba_is_associative_array( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		return ( array_keys( $array ) !== range( 0, count( $array ) - 1 ));
	}

	function __hba_is_empty( $var ) {
		if ( is_object( $var ) ) {
			return false;
		}
		if ( is_array( $var ) && __hba_can_loop( $var ) ) {
			return false;
		}
		return ( empty( $var ) || is_null( $var ) || ( ! is_array( $var ) && ! is_object( $var ) && 0 == strlen( $var ) ) );
	}

	function __hba_is_bool_val( $var ) {
		return ( 0 === $var || 1 === $var || '0' === $var || '1' === $var || true === $var || false === $var );
	}

	function __hba_beginning_matches( $beginning, $match ) {
		return ( $beginning == substr( $match, 0, strlen( $beginning ) ) );
	}

	function __hba_ending_matches( $end, $match ) {
		return ( $end == substr( $match, ( -1 * strlen( $end ) ) ) );
	}

	function __hba_is_beginning_matches( $beginning, $match ) {
		return ( $beginning == substr( $match, 0, strlen( $beginning ) ) );
	}

	function __hba_is_ending_matches( $end, $match ) {
		return ( $end == substr( $match, ( -1 * strlen( $end ) ) ) );
	}

	function __hba_is_between( $val, $start, $end ) {
		return ( $val >= $start && $val <= $end );
	}

	function __hba_is_decimal( $val ) {
		return ( is_numeric( $val ) && floor( $val ) != $val );
	}

	function __hba_is_instance_of( $class, string $interface ) {
		if ( ! is_object( $class ) && ! class_exists( $class ) ) {
			return false;
		}
		$implements = class_implements( $class );
		return array_key_exists( $interface, $implements );
	}

	function __hba_is_cli() {
		return ( 'cli' == php_sapi_name() );
	}

	function __hba_matches_regex( string $string, string $pattern ) {
		return ( $string == $pattern || intval( preg_match( __hba_sanitize_regex( $pattern ), $string, $matches ) ) > 0 );
	}

	function __hba_is_ip( $input ) {
		$input = filter_var( $input, FILTER_VALIDATE_IP );
		return ( true == filter_var( $input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) || true == filter_var( $input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) );
	}

	function __hba_levenshtein_ratio( string $string1, string $string2 ) {
		$maxLen = ( strlen( $string1 ) > strlen( $string2 ) ) ? strlen( $string1 ) : strlen( $string2 );
		return round( ( 1 - levenshtein( $string1, $string2 ) / $maxLen ) * 100 );
	}

	function __hba_written_similar( string $string1, string $string2, float $threshold = 0.80, bool $caseInsensative = true ) {
		$percent = __hba_get_written_similarity_percent( $string1, $string2, $caseInsensative );
		return ( $percent >= $threshold );
	}

	function __hba_sound_similar( string $string1, string $string2, float $threshold = 0.80 ) {
		$percent = __hba_get_sounding_similarity_percent( $string1, $string2 );
		return ( $percent >= $threshold );
	}