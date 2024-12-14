# apache

This are some Apache related functions for the Ecxod Framework

### Install
```bash
php composer.phar require ecxod/apache
```

### function parseApacheMacroConfig() ###

I found no specific library for parsing Apache Macro configuration files.  
Most existing solutions focus on parsing .ini files or other common configuration formats.

The function fas the following capabilities:
 - ignoring comment lines starting with #
 -  can handle tabs and multiple spaces as separator

Use Case : 

```php
use Ecxod/Apache;

# This is the config file you want to parse
$configPath = '/etc/apache2/sites-enabled/001-SSLHost.conf';

$config = parseApacheMacroConfigLinear($configPath);

foreach ($config as $key => $value) {
    echo "$key: $value\n";
}

```