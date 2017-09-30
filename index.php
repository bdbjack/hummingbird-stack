<?php
	require_once realpath( dirname( __FILE__ ) ) . '/hbs/hbs.php';

	try {
		$hba = new \Hummingbird\HummingbirdApp();
		$hba->setConfig( array(
			'redis' => array(
				'enabled' => false,
				'servers' => array(
					array(
						'host' => '127.0.0.1',
						'port' => 6379,
					),
				),
			),
			'memcached' => array(
				'enabled' => false,
				'servers' => array(
					array(
						'host' => '127.0.0.1',
						'port' => 11211,
					),
				),
			),
			'memcache' => array(
				'enabled' => false,
				'servers' => array(
					array(
						'host' => '127.0.0.1',
						'port' => 11211,
					),
				),
			),
			'databases' => array(
				'enabled' => true,
				'servers' => array(
					'default' => array(
						'type' => 'mysql',
						'host' => 'localhost',
						'port' => 3306,
						'name' => 'di',
						'user' => 'di',
						'pass' => 'U5BEeQHbUhokJCr4',
						'prefix' => 'hba_',
					),
				),
			),
		) );
		$hba->run();
	}
	catch ( Exception $e ) {
		echo '<pre>';
		print_r( $e->getMessage() );
		echo '</pre>';
	}

	echo '<pre>';
	var_dump( $hba->runCacheFunction( 'set', 'test', 'test123' ) );
	echo '</pre>';

	echo '<pre>';
	var_dump( $hba->runCacheFunction( 'get', 'test' ) );
	echo '</pre>';

	echo '<pre>';
	var_dump( $hba->runCacheFunction( 'trash', 'test' ) );
	echo '</pre>';

	echo '<pre>';
	var_dump( $hba->runCacheFunction( 'set', 'test', 'test456' ) );
	echo '</pre>';

	echo '<pre>';
	var_dump( $hba->runCacheFunction( 'get', 'test' ) );
	echo '</pre>';

	echo '<pre>';
	var_dump( $hba->runCacheFunction( 'purge' ) );
	echo '</pre>';

	echo '<pre>';
	var_dump( $hba->runCacheFunction( 'get', 'test' ) );
	echo '</pre>';

	echo '<pre>';
	print_r( $hba );
	echo '</pre>';