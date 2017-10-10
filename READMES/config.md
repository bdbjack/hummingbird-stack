# Hummingbird Stack Framework

*A lightweight, fast and easy-to-use framework*

## Configuration

Application configuration is broken up into "sections" and "settings". For example, to check if the application has configured databases, you can run:

```php
<?php
	$hba->getConfigSetting( 'databases', 'enabled' );
```

## Per Section Configuration

By Default, Hummingbird has the following configuration sections. However, by using the `HBA::setConfig` you can extend to add as many additional configuration sections and settings as you would like.

### `application`

This section holds general configuration settings for the application.

| Setting | Variable Type | Description |
| ------- | ------------- | ----------- |
| `name` | *string* | The name of the application. Used in various functions, especially reporting & feedback |
| `timezone` | *string* | A string representing a PHP timezone. (See PHP's Documentation for more information)[http://php.net/manual/en/timezones.php] |
| `debug` | *boolean* | Whether or not debugging is enabled. Debugging will cause a process to stop if there are **ANY** errors in the code |
| `feedbackController` | instance of *Hummingbird\HummingbirdFeedbackControllerInterface* | A controller used to return feedback to a client |
| `errorController` | instance of *Hummingbird\HummingbirdErrorControllerInterface* | A controller used to handle system errors without interupting the execution of the script |
| `requestController` | intance of *Hummingbird\HummingbirdRequestControllerInterface* | A controller which handles information and functionality related to client request |
| `databaseController` | instance of *Hummingbird\HummingbirdDatabaseControllerInterface* | A controller which handles wraps and handles the interaction with a database |
| `cacheController` | instance of *Hummingbird\HummingbirdCacheControllerInterface* | A controller which intelligently attempts to handle caching by using the fastest caching mechanism available |
| `tmpDir` | *string* | A writable directory which is used for storing temporary files |
| `enableErrorCapture` | *boolean* | Enable using the error controller to handle errors instead of letting PHP handle it via its default settings |

### `authentication`



### `newrelic`



### `session`



### `databases`



### `memcache`



### `memcached`



### `redis`



### `smtp`


