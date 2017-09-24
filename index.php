<?php
	define( 'ABSPATH', sprintf( '%s/', dirname( __FILE__ ) ) );
	define( 'FRAMEWORKVERSION', '0.1.0' );

	##
	# Get the absolute locations of the config & core files
	##
	$config_file_abs = sprintf( '%s/config.php', substr( ABSPATH, 0, strlen( ABSPATH ) - 1 ) );
	$core_file_abs = sprintf( '%s/framework/core.php', substr( ABSPATH, 0, strlen( ABSPATH ) - 1 ) );

	##
	# Check to see that the files exist
	##
	if ( ! file_exists( $config_file_abs ) ) {
		exit( 'Fatal Error: Config File not found or accessible' );
	}
	if ( ! file_exists( $core_file_abs ) ) {
		exit( 'Fatal Error: Hummingbird Framework Core not found' );
	}

	##
	# Inclue the files in the process
	##
	require_once $config_file_abs;
	require_once $core_file_abs;

	##
	# Load the core
	##
	$HC = HC::init( $__hbs_config );
	exit();