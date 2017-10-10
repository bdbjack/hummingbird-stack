# Hummingbird Stack Framework

*A lightweight, fast and easy-to-use framework*

## Credits

This framework integrates the following libraries:

* [giggsey/libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php)
* [openwall/phpass](http://www.openwall.com/phpass/)
* [phpmailer/phpmailer](https://github.com/PHPMailer/PHPMailer)
* [twilio/sdk](https://www.twilio.com/docs/libraries)
* [wisembly/elephant.io](https://github.com/Wisembly/elephant.io)
* [Redbean for PHP](https://redbeanphp.com/index.php)

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

For more information on configuration settings, read the [configuration documentation](../master/READMES/config.md)

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
```

Then set the configuration to the application as follows:

```php
<?php
	$hba->setConfig( $appSettings );
```

## Core Functionality

