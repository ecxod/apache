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


```sh
cat /etc/apache2/sites-enabled/001-SSLHost.conf
```
that may look like this : 
```txt
# KEY1  KEY2  KEYn
#
# This is a comment
# Next line is wrapped with the character "\"
VALUEa1    VALUEa2    \
    VALUEan
VALUEb1    VALUEb2    VALUEbn
VALUEc1    VALUEc2    VALUEcn
```



Use Case : 
```php
use Ecxod/Apache;

# This are the Keys
$keysArr = ["KEY1", "KEY2", "KEYn"];

# This is the config file you want to parse
$configPath = '/etc/apache2/sites-enabled/001-SSLHost.conf';

$result = parseApacheMacroConfigLinear($configPath);

print_r($result);

```

