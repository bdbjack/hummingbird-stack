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

## Core Functionality

### Adding Routes

A route is a combination of an HTTP Request method and a path which triggers an associated action. Routes can be either statically defined (i.e. `/this/is/a/path/`) or can use regular expressions to dynamically capture information from the path and pass it through to the route controller (i.e. `/this/([^/].*/a/([^/].*/`).

Adding routes are accomplished using the `HummingbirdApp::addRoute` function. The function accepts 7 arguments:

| Argument | Type | Description |
| -------- | ---- | ----------- |
| `method` | *string* | The method of the request. All HTTP methods are supported, as well as `CLI` which is used for command line requests. |
| `pattern`  | *string* | The regular expression or statically defined path for which the route will be activated |
| `action`  | *string* | The name of the action to be called. **NOTE:** Hummingbird slightly modifies the action to match *both* the method and the action when running `HummingbirdApp::doAction` |
| `authRequired` | *boolean* | Whether or not a user must be logged in for the action to run. If the user isn't logged in they will be redirected to a login page where possible. |
| `redirectAuthenticated` | *boolean* | Redirect users who are logged in to the base URI of the system. This is useful for login and registration pages where logged in users shoudn't be able to interact with the forms.
| `title` | *string* | The title of the page / endpoint.
| `overwrite` | *boolean* | If the pattern already exists in routes buffer, allow it to be overwritten.

#### Example of adding a Route

```php
$hba->addRoute( 'GET', '/', 'dashboard', true, false, 'System Dashboard', true );
```

### Adding Actions

### Interacting with a request

### Interacting with databases

### Interacting with the cache

### Interacting with external HTTP services

### Interacting with SMTP

### Handling Authentication

### Generating Feedback

### Errors and Debugging

### The Phone Number Utility

### The IP Address Utility