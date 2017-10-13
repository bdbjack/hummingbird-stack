# Hummingbird Stack Framework

*A lightweight, fast and easy-to-use framework*

## Configuration

Application configuration is broken up into "sections" and "settings". The sections are primarily for segmentation, while the settings are used in various parts of the application, either in the default controllers or in custom controllers which are used to extend the application.
It is recommended that you extend and modify the application by changing the values for various controller settings.

**NOTE**
Because Hummingbird is namespaced, you will need to use `\` before any non-namespaced classes. For example `stdClass` needs to be called as `\stdClass`.

## Per Section Configuration

By Default, Hummingbird has the following configuration sections. However, by using the `\Hummingbird\HummingbirdApp::setConfig` you can extend to add as many additional configuration sections and settings as you would like.

### `application`

This section holds general configuration settings for the application.

| Setting | Variable Type | Description | Default |
| ------- | ------------- | ----------- | ------- |
| `name` | *string* | The name of the application. Used in various functions, especially reporting & feedback | `Hummingbird Application` |
| `timezone` | *string* | A string representing a PHP timezone. [See PHP's Documentation for more information](http://php.net/manual/en/timezones.php) | `UTC` |
| `debug` | *boolean* | Whether or not debugging is enabled. Debugging will cause a process to stop if there are **ANY** errors in the code | `true` |
| `feedbackController` | *string* class name of instance of *Hummingbird\HummingbirdFeedbackControllerInterface* | A controller used to return feedback to a client | `\Hummingbird\HummingbirdDefaultFeedbackController` |
| `errorController` | *string* class name of instance of *Hummingbird\HummingbirdErrorControllerInterface* | A controller used to handle system errors without interupting the execution of the script | `\Hummingbird\HummingbirdDefaultErrorController` |
| `requestController` | intance of *Hummingbird\HummingbirdRequestControllerInterface* | A controller which handles information and functionality related to client request | `\Hummingbird\HummingbirdDefaultRequestController` |
| `databaseController` | *string* class name of instance of *Hummingbird\HummingbirdDatabaseControllerInterface* | A controller which handles wraps and handles the interaction with a database | `\Hummingbird\HummingbirdDefaultDatabaseController` |
| `cacheController` | *string* class name of instance of *Hummingbird\HummingbirdCacheControllerInterface* | A controller which intelligently attempts to handle caching by using the fastest caching mechanism available | `\Hummingbird\HummingbirdDefaultCacheController` |
| `tmpDir` | *string* | A writable directory which is used for storing temporary files | `/tmp/` |
| `enableErrorCapture` | *boolean* | Enable using the error controller to handle errors instead of letting PHP handle it via its default settings | `true` |

### `authentication`

This secton sets configuration for authentication. While applications don't need to use authentication, and you don't need to use the application's authentication, it is highly recommended that you do

| Setting | Variable Type | Description | Default |
| ------- | ------------- | ----------- | ------- |
| `enabled` | *boolean* | Enable or Disable the need for authentication. This mostly affects routing rules | `false` |
| `controller` | *string* class name instance of *Hummingbird\HummingbirdAuthenticationControllerInterface* | The controller which is used for handling authentication. *It is recommended that the default controller be extended and the new class replaced here* | `\Hummingbird\HummingbirdDefaultAuthenticationController` |
| `allowHTTPBasicAuth` | *boolean* | Allow sending credentials via HTTP Basic Auth | `false` |
| `allowHTTPHeaderAuth` | *boolean* | Allow sending credentials via HTTP Header | `false` |
| `allowHTTPCookieAuth` | *boolean* | Allow capturing credentials from HTTP Cookie | `false` |
| `allowSessionAuth` | *boolean* | Allow capturing credentials from PHP Session | `false` |
| `allowCLIAuth` | *boolean* | Allow Capturing Authentication from CLI arguments | `false` |
| `authRedirectUri` | *string* | The URI which a regular web session is redirected to if not authenticated | `/login/` |

### `newrelic`

[NewRelic](https://newrelic.com/) is a full-stack monitoring solution which shows high-resolution breakdowns of your application's performance.
While [NewRelic](https://newrelic.com/) isn't a requirement for the application to run, it is highly recommended both for development and live environments.
Hummingbird further increases your integration with NewRelic by adding additional relevant information which will make it easier to identify and resolve issue and increase over-all performance.

| Setting | Variable Type | Description | Default |
| ------- | ------------- | ----------- | ------- |
| `enabled` | *bool* | Enable or Disable NewRelic if the [NewRelic PHP Agent](https://docs.newrelic.com/docs/agents/php-agent/getting-started/introduction-new-relic-php#installation) is installed | `true` |
| `apmName` | *string* | The name of the APM to be shown in NewRelic | `Hummingbird Application` |
| `apmLicense` | *string* | The license key of the APM to be used. This should only be used in cases where the APM belongs to an account different than the one configured in the PHP Agent | `null` |

### `session`

Not all applications require use of a session. WordPress for example, recommends *AGAINST* using a Session, instead depending on various cookies for authentication.
One of the reasons for this is that PHP's default Session Handler reads and writes session files from the server's local hard drive, which is slow. Hummingbird has it's own Session handler which uses the fastest caching controller available to handle Session requests.

| Setting | Variable Type | Description | Default |
| ------- | ------------- | ----------- | ------- |
| `enabled` | *bool* | Enable or Disable the PHP Session | `false` |
| `controller` | *string* class name of instance of *Hummingbird\HummingbirdSessionControllerInterface* | A controller which intelligently handles reading and writing from the session | `\Hummingbird\HummingbirdDefaultSessionController` |

### `databases`

Databases are the most ubiquitous method to store and retrieve information for dynamic web applications. Hummingbird uses's [RedBean for PHP](https://redbeanphp.com/index.php) in order to simplify interaction with several types of databases.
The database types which are currently supported are:

* MySQL / MariaDB
* PosgreSQL (note: this requires that you have PHP's pgsql extension installed and configured)
* SQLite

| Setting | Variable Type | Description | Default |
| ------- | ------------- | ----------- | ------- |
| `enabled` | *bool* | Enable or Disable database usage | `false` |
| `servers` | *bool* | An array of databases which can be accessed by the database controller | *see below* |

The array of servers is an associative array with the key being an identifier for the specific database, and the value being an array of configuration information.

**NOTE** If `true == $hba->getConfigSetting( 'databases', 'enabled' )`, you must have a database with the key of `default`.
**NOTE** It is possible to add / modify databases programmatically using `HummingbirdApp::addDatabase` both before the configuration is loaded, and after. [Click Here](../master/hbs/hbs.php#L191) to see the usage for `HummingbirdApp::addDatabase`.

### `memcache`



### `memcached`



### `redis`



### `smtp`


