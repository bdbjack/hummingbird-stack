# Hummingbird Stack Framework

*A lightweight, fast and easy-to-use framework*

## Configuration

Application configuration is broken up into "sections" and "settings". For example, to check if the application has configured databases, you can run:

```php
<?php
	$hba->getConfigSetting( 'databases', 'enabled' );
```

