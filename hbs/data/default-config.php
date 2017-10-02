<?php
	namespace Hummingbird;

	$defaultConfig = array(
		'application' => array(
			'name' => 'Hummingbird Application',
			'timezone' => 'UTC',
			'debug' => true,
			'language' => 'EN',
			'feedbackController' => '\Hummingbird\HummingbirdDefaultFeedbackController',
			'errorController' => '\Hummingbird\HummingbirdDefaultErrorController',
			'requestController' => '\Hummingbird\HummingbirdDefaultRequestController',
			'databaseController' => '\Hummingbird\HummingbirdDefaultDatabaseController',
			'cacheController' => '\Hummingbird\HummingbirdDefaultCacheController',
			'tmpDir' => realpath( '/tmp/' ),
			'enableErrorCapture' => true,
		),
		'authentication' => array(
			'enabled' => false,
			'controller' => '\Hummingbird\HummingbirdDefaultAuthenticationController',
			'allowHTTPBasicAuth' => false,
			'allowHTTPHeaderAuth' => false,
			'allowHTTPCookieAuth' => false,
			'allowSessionAuth' => false,
			'allowCLIAuth' => false,
			'authRedirectUri' => '/login/',
		),
		'newrelic' => array(
			'enabled' => true,
			'apmName' => 'Hummingbird Application',
			'apmLicense' => null,
		),
		'session' => array(
			'enabled' => false,
			'controller' => '\Hummingbird\HummingbirdDefaultSessionController',
			'cookieName' => 'PHPSESSID',
		),
		'databases' => array(
			'enabled' => false,
			'servers' => array(
				'default' => array(
					'type' => 'sqlite',
					'host' => '',
					'port' => null,
					'name' => '/tmp/dbfile.db',
					'user' => null,
					'pass' => null,
					'prefix' => '',
					'frozen' => false,
					'readonly' => false,
				),
			),
		),
		'memcache' => array(
			'enabled' => false,
			'servers' => array(),
		),
		'memcached' => array(
			'enabled' => false,
			'servers' => array(),
		),
		'redis' => array(
			'enabled' => false,
			'servers' => array(),
		),
		'smtp' => array(
			'enabled' => true,
			'host' => 'localhost',
			'port' => 25,
			'auth' => false,
			'user' => null,
			'pass' => null,
			'encrypt' => null,
			'senders' => array(),
		),
	);