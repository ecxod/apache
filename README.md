# apache

This are some Apache related functions for the Ecxod Framework

### Install
```bash
php composer.phar require ecxod/apache
```

### function parseApacheMacroConfig() ###

I found no specific library for parsing Apache Macro configuration files.  
Most existing solutions focus on parsing .ini files or other common configuration formats.

The function *parseApacheMacroConfigLinear* fas the following capabilities:
 - ignoring comment lines starting with a "#" like : /(\s*)\\#(\s*)/
 - can handle tabs and multiple spaces as separator
 - can handle wrapped lines with backslash in shell-style mode such as: "\\"

Let's admit the following config file
```sh
cat /etc/apache2/sites-enabled/001-SSLHost.conf
```
that may look like this : 
```txt
# This is a comment
# Next line is wrapped with the character "\"
VALUEa1    VALUEa2    \
    VALUEan
VALUEb1    VALUEb2    VALUEbn
VALUEc1    VALUEc2    VALUEcn
```

Use Case : 
```php
use Ecxod\Apache\Apache;

# This are the Keys (must match to number of columns in the config)
$keysArr = ["KEY1", "KEY2", "KEYn"];

# This is the config file you want to parse
$configPath = '/etc/apache2/sites-enabled/001-SSLHost.conf';

$parseConfig = new Apacche;
$result = $parseConfig->parseApacheMacroConfigLinear($configPath);

print_r($result);

```
result ölooks like this

```txt
 Array
(
    [0] => Array
        (
            [KEY1] => VALUEa1
            [KEY2] => VALUEa2
            [KEYn] => VALUEan
        )
    [1] => Array
        (
            [KEY1] => VALUEb1
            [KEY2] => VALUEb2
            [KEYn] => VALUEbn
        )
    [2] => Array
        (
            [KEY1] => VALUEc1
            [KEY2] => VALUEc2
            [KEYn] => VALUEcn
        )
)
```

