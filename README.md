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

An action is a hookable event which calls functions and methods which are associated with it. The concept is based on WordPress's hookable events API (known as actions and filters). [Read this for more information about WordPress's Action hooks](https://developer.wordpress.org/plugins/hooks/actions/).

Unlike WordPress, Hummingbird does not give you the ability to create your own action hooks outside of Hummingbird, however you can hook any function or class method you'd like into the existing event hooks.

**NOTE:** All action hooks are loaded by the `HummingbirdApp::run()` function. Actions will not occur before so that all pre-requisites can be loaded correctly.

#### Current Action Hooks

| Order | Hook | Description |
| ----- | ---- | ----------- |
| 1 | `init` | Loads all of the core controllers required for Hummingbird to run |
| 2 | `initDatabases` | Loads and begins configuration of all of the database controllers |
| 3 | `initCache` | Loads and configures the Hummingbird Caching Mechanism |
| 4 | `initSession` | Sets a PHP session handler if sessions are enabled |
| 5 | `initAuthentication` | Loads the authentication controller and tries to create or load an authentication session where possible |
| 6 | `initRouting` | Load routes and check which route matches the current path |
| 7 | `render` | Using the feedback controller, render content. **NOTE:** if `HummingbirdApp::run()` has `false` passed as the first argument, this action will not occur.

#### Hooking to an action

The `HummingbirdApp::addAction()` is used to call an action from a hook. The function accepts 4 arguments:

| Argument | Type | Description |
| -------- | ---- | ----------- |
| `key` | *string* | The hook which you want to hook the function into |
| `function` | [*callable*](http://php.net/manual/en/language.types.callable.php) | The function or method to be called |
| `priority` | *interger* | The priority defines the order the action is called in relation to other actions on the same hook. Actions are ordered in ascending order by priority |
| `passApp` | *boolean* | Whether the first argument passed to the function or method should be the instance of  `HummingbirdApp` which called the action |

#### Example Usage

```php
$hba->addAction( 'get_dashboard', 'render_system_dashboard' );
$hba->addAction( 'initDatabases', array( '\SomeClass', 'someMethod' );
```

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