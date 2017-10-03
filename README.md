# Hummingbird Stack Framework

*A lightweight, fast and easy-to-use framework*

## Using Hummingbird

Hummingbird stack is best used as a git submodule. To add it to another Git repository simply run the following from within the project:

```
git submodule add https://github.com/bdbjack/hummingbird-stack.git
```

Then, you should include the /hummingbird-stack/hbs/hbs.php file in your project as close to the top as possible:

```php
<?php
	require_once realpath( dirname( __FILE__ ) ) . '/hummingbird-stack/hbs/hbs.php';
```

Initialize the application as follows

```php
<?php
	try {
		$hba = new \Hummingbird\HummingbirdApp();
	}
	catch ( Exception $e ) {
		// Handle your errors here
	}
```

And once you have setup your app using the API's below, run the application as follows:

```php
<?php
	try {
		$hba->run();
	}
	catch ( Exception $e ) {
		// Handle your errors here
	}
```

## Configurable Settings

By default, the application comes with the following settings:

```php
<?php
	$settings = array(
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
			'controller' => '\Hummingbird\HummingbirdDefaultEmailController',
			'host' => 'localhost',
			'port' => 25,
			'auth' => false,
			'user' => null,
			'pass' => null,
			'encrypt' => null,
			'senders' => array(),
		),
	);
```

Most settings can be overwritten by simply using the `HummingbirdApp::setConfig` API, but certain settings such as adding databases should use their own specific API's.