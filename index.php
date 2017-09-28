<?php
	require_once realpath( dirname( __FILE__ ) ) . '/hbs/hbs.php';

	try {
		$hba = new \Hummingbird\HummingbirdApp();
		$hba->setConfig( array(
			'twilio' => array(
				'TWILIO_ACCOUNT_SID' => '',
				'TWILIO_AUTH_TOKEN' => '',
				'TWILIO_TWIML_APP_SID' => '',
			),
			'maxmind' => array(
				'user' => '',
				'license' => '',
			),
		) );
		$hba->run();
	}
	catch ( Exception $e ) {
		echo '<pre>';
		print_r( $e->getMessage() );
		echo '</pre>';
	}

	//$hba->runRequestFunction( 'setCookie', 'test', '123' );

	echo '<pre>';
	print_r( $hba );
	echo '</pre>';
	echo "\r\n";
	echo '<pre>';
	print_r( $hba->runRequestFunction( 'getCurrentPath' ) );
	echo '</pre>';
	echo "\r\n";
	echo '<pre>';
	print_r( $hba->runRequestFunction( 'getCurrentURL' ) );
	echo '</pre>';
	echo "\r\n";
	echo '<pre>';
	print_r( $hba->runRequestFunction( 'getURLFromPath', '/debug/config/test/', array( 'testing' => '123' ) ) );
	echo '</pre>';
	echo "\r\n";