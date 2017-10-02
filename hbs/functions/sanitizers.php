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
		if ( '/' !== substr( $input, -1 ) ) {
			$input .= '/';
		}
		return $input;
	}