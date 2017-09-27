<?php
	require_once './hbs/hbs.php';

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