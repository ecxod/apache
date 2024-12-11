# apache

This are some Apache related functions for the Ecxod Framework

Because I found no specific library for parsing Apache Macro configuration files. 
Most existing solutions focus on parsing .ini files or other common configuration formats.

Use Case : 

```php

$configPath = '/pfad/zur/apache/macro/config.conf';
$config = parseApacheMacroConfig($configPath);

foreach ($config as $key => $value) {
    echo "$key: $value\n";
}

```