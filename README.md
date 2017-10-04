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

Most of the application's default settings can be overwritten by simply using the `HummingbirdApp::setConfig` API, but certain settings such as adding databases should use their own specific API's.

The following is an example application which has been renamed, has `Memcached` setup, uses PHP Sessions, and uses a local MySQL / MariaDB database.

```php
<?php
	$appSettings = array(
		'application' => array(
			'name' => 'Example Application',
			'debug' => false,
		),
		'newrelic' => array(
			'enabled' => true,
			'apmName' => 'Example Application',
		),
		'session' => array(
			'enabled' => true,
		),
		'databases' => array(
			'enabled' => true,
			'servers' => array(
				'default' => array(
					'type' => 'mysql',
					'host' => 'localhost',
					'port' => 3306,
					'name' => 'example',
					'user' => 'root',
					'pass' => '',
					'prefix' => 'exp_',
					'frozen' => false,
					'readonly' => false,
				),
			),
		),
		'memcached' => array(
			'enabled' => true,
			'servers' => array(
				array(
					'host' => 'localhost',
					'port' => 11211,
					'priority' => 10,
				),
			),
		),
	);

	/** Set the configuration in the App */
	$hba->setConfig( $appSettings );
```

