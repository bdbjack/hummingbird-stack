<?php
	function __hba_sanitize_absint( $input ) {
		if ( ! is_numeric( $input ) || is_empty( $input ) ) {
			return 0;
		}
		$int = intval( $input );
		if ( $int < 0 ) {
			$int = $int * -1;
		}
		return $int;
	}

	function __hba_sanitize_url( $url ) {
		$url = filter_var( $url, FILTER_SANITIZE_URL );
		if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
			return null;
		}
		return $url;
	}

	function __hba_sanitize_email( $url ) {
		$url = filter_var( $url, FILTER_SANITIZE_EMAIL );
		return $url;
	}

	function __hba_sanitize_phone( $phone ) {
		$phone = trim( $phone );
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		return $phone;
	}

	function __hba_sanitize_sql_input( $input ) {
		try {
			$pdo = new PDO( 'sqlite:fakepdo.db' );
		}
		catch( Exception $e ) {}
		if ( isset( $pdo ) && is_a( $pdo, 'PDO' ) ) {
			$input = $pdo->quote( $input );
		}
		if ( 2 !== substr_count( $input, "'" ) ) {
			$input = sprintf( "'%s'", $input );
		}
		return $input;
	}

	function __hba_sanitize_currency( $amount, $currency = 'USD', $format = '%.2n' ) {
		switch ( strtoupper( $currency ) ) {
			case 'EUR':
				setlocale( LC_MONETARY, 'de_DE.utf8' );
				break;

			default:
				setlocale( LC_MONETARY, 'en_US.utf8' );
				break;
		}
		$amount = floatval( $amount );
		return money_format( $format, $amount );
	}

	function __hba_sanitize_regex( $input ) {
		$input = str_replace( '/', '\/', $input );
		if ( '/' !== substr( $input, 0, 1 ) ) {
			$input = '/' . $input;
		}
		if ( '/' !== substr( $input, -1 ) || '\/' == substr( $input, -2 )  ) {
			$input .= '/';
		}
		return $input;
	}

	function __hba_sanitize_country( $input ) {
		global $__hba_countries;
		$iso2s = array();
		$iso3s = array();
		$names = array();
		$return = 'XX';
		## let's try to figure out what kind of input we have here.
		## possible inputs are:
		## iso2, iso3, name, and phone number
		if ( can_loop( $__hba_countries ) ) {
			foreach ( $__hba_countries as $iso => $info ) {
				$iso2s[ $iso ] = get_array_key( 'iso', $info, 'XX' );
				$iso3s[ get_array_key( 'iso3', $info, 'XXX' ) ] = get_array_key( 'iso', $info, 'XX' );
				$iso3s[ get_array_key( 'name', $info, 'Anonymous' ) ] = get_array_key( 'iso', $info, 'XX' );
			}
		}
		if ( strlen( $input ) == 2 && array_key_exists( strtoupper( $input ), $iso2s ) ) {
			$return = get_array_key( strtoupper( $input ), $iso2s );
		}
		else if ( strlen( $input ) == 3 && array_key_exists( strtoupper( $input ), $iso3s ) ) {
			$return = get_array_key( strtoupper( $input ), $iso3s );
		}
		else if ( array_key_exists( strtoupper( $input ), $names ) ) {
			$return = get_array_key( strtoupper( $input ), $names );
		}
		else if ( preg_match( '/[a-zA-Z]/', $input ) > 0 ) {
			$input = trim( preg_replace( '/[^a-zA-Z ,\\\']/', '', $input ) );
			$return = __hba_get_country_info_by_name( $input );
		}
		else if ( preg_match( '/[0-9]/', $input ) > 0 ) {
			$input = __hba_sanitize_phone( $input );
			$return = __hba_guess_country_from_phone_number( $input );
		}
		if ( is_array( $return ) ) {
			$return = get_array_key( 'iso', $return, 'XX' );
		}
		return $return;
	}