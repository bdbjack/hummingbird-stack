<?php
	require_once realpath( dirname( __FILE__ ) ) . '/hbs/hbs.php';

	try {
		$hba = new \Hummingbird\HummingbirdApp();
	}
	catch ( Exception $e ) {
		echo '<pre>';
		print_r( $e->getMessage() );
		echo '</pre>';
	}


	$hba->setConfig( array(
		'authentication' => array(
			'enabled' => true,
			'allowHTTPBasicAuth' => true,
			'allowHTTPHeaderAuth' => true,
			'allowHTTPCookieAuth' => true,
			'allowSessionAuth' => true,
			'allowCLIAuth' => true,
		),
	) );

	try {
		$hba->run();
	}
	catch ( Exception $e ) {
		echo '<pre>';
		print_r( $e->getMessage() );
		echo '</pre>';
	}

	echo 'BASIC' . "\r\n";
	echo '<pre>';
	print_r( $hba->runAuthenticationFunction( 'getAuthFromHTTPBasic' ) );
	echo '</pre>';


	echo 'Headers' . "\r\n";
	echo '<pre>';
	print_r( $hba->runAuthenticationFunction( 'getAuthFromHeader' ) );
	echo '</pre>';

	echo 'Cookie' . "\r\n";
	echo '<pre>';
	print_r( $hba->runAuthenticationFunction( 'getAuthFromCookie' ) );
	echo '</pre>';

	echo 'Session' . "\r\n";
	echo '<pre>';
	print_r( $hba->runAuthenticationFunction( 'getAuthFromSession' ) );
	echo '</pre>';

