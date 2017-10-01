<?php
	require_once realpath( dirname( __FILE__ ) ) . '/hbs/hbs.php';

	try {
		$hba = new \Hummingbird\HummingbirdApp();
		$hba->run();
	}
	catch ( Exception $e ) {
		echo '<pre>';
		print_r( $e->getMessage() );
		echo '</pre>';
	}

	echo '<pre>';
	print_r( $hba );
	echo '</pre>';