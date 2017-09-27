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
		),
		'authentication' => array(
			'enabled' => false,
			'controller' => '\Hummingbird\HummingbirdDefaultAuthenticationController',
			'allowHTTPBasicAuth' => false,
			'allowHTTPHeaderAuth' => false,
			'allowHTTPCookieAuth' => false,
			'authRedirectUri' => '/login/',
		),
		'newrelic' => array(
			'enabled' => true,
			'apmName' => 'Hummingbird Application',
			'apmLicense' => null,
		),
		'session' => array(
			'enabled' => false,
			'controllerPriority' => array(
				'\Hummingbird\HummingbirdRedisSessionController',
				'\Hummingbird\HummingbirdMemCachedSessionController',
				'\Hummingbird\HummingbirdMemCacheSessionController',
				'\Hummingbird\HummingbirdDatabaseSessionController',
				'\Hummingbird\HummingbirdFileSessionController',
			),
		),
		'databases' => array(
			'enabled' => true,
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