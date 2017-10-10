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



### `session`



### `databases`



### `memcache`



### `memcached`



### `redis`



### `smtp`

