<?php

declare(strict_types=1);

namespace Ecxod\Apache;

use Predis;
use Throwable;
use XMLWriter;

/** 
 * @package Ecxod\Apache 
 */
class Apache
{

    public string $escape;
    public string $enclosure;
    public string $separator;
    public string $conf_enabled;

    public function __construct()
    {
        $this->escape       = "\\";
        $this->enclosure    = "\'";
        $this->separator    = ",";
        $this->conf_enabled = "/etc/apache2/conf-enabled";
    }

    protected function redisClient()
    {

        $workspace = realpath(dirname($_SERVER['DOCUMENT_ROOT']));
        // error_log("WS=" . $workspace);

        set_include_path(get_include_path() . PATH_SEPARATOR . $workspace);
        $currentIncludePath = get_include_path();
        // error_log("CIP=" . $currentIncludePath);

        require "$workspace/vendor/autoload.php";

        try{
            $client = new Predis\Client([ 
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
            ]);            
            $client->connect();
        }catch(Throwable $e){
            error_log($e);
        }
        return $client;
    }


    /** 
     * erzeugt eine Array die die KonfigurationsdateienNamen enthält.  
     * Wenn keine Endungen (zB ".conf") gefunden, dann eine leere Array. 
     * (Nur die Namen)   
     * 
     * @param string $directory
     * @return array
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    public function walkThrueFolderAndReturnArrayOfFileNames(string $directory): array
    {

        // prüfen ob $directory existiert
        if(!is_dir(filename: $directory) and !is_link(filename: $directory))
        {
            $files = [];
        }

        $files = glob(pattern: "$directory/*.conf");
        if(!empty($files))
        {
            sort(array: $files);
        }
        else
        {
            $files = [];
        }

        return $files;
    }

    /** 
     * erzeugt eine array die die Konfigurationsdateien enthält. 
     * (die ganzen Dateien)   
     * 
     * @param string $directory
     * @return array
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    public function walkThrueFolderAndReturnFilesInAArray(string $directory): array
    {
        $allFilesInAArray = [];

        $files = $this->walkThrueFolderAndReturnArrayOfFileNames(directory: $directory);

        foreach($files as $file)
        {
            $content                                   = file_get_contents(filename: $file);
            $allFilesInAArray[ basename(path: $file) ] = strval(value: $content);
        }
        return $allFilesInAArray;
    }


    /**
     * erzeugt eine array der Macro Namen und Macro Variablen.   
     * zB so :  
     * [  
     *      SSLHost1 => ["$domain", "$port", "$docroot", "$allowed", "$errorlevel"] ,  
     *      SSLHost2 => ["$domain", "$port", "$docroot", "$allowed", "$errorlevel"]  
     * ]   
     * 
     * @param string $directory
     * @return array
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    public function getMacroDefinitions(string $directory): array
    {
        $macros = [];

        $files = $this->walkThrueFolderAndReturnArrayOfFileNames(directory: $directory);
        if(!empty($files))
        {
            foreach($files as $file)
            {
                $content = file_get_contents(filename: $file);
                $lines   = explode(separator: "\n", string: $content);

                foreach($lines as $line)
                {

                    // removing trailing and leading <>, die Zeile darf sonst keine Klammern enthalten
                    $line = str_replace(search: [ "<", ">" ], replace: "", subject: $line);

                    if(strpos(haystack: trim(string: $line), needle: 'Macro ') === 0)
                    {
                        $words = preg_split(pattern: '/\s+/', subject: $line, limit: -1, flags: PREG_SPLIT_NO_EMPTY);
                        // zB. <Macro SSLHost $domain $port $docroot ... 
                        $macroName      = strval(value: $words[1]);
                        $macroVariables = $words;
                        // wir lassen die ersten beiden Elemente weg
                        array_splice(array: $macroVariables, offset: 0, length: 2);
                        $macros[ $macroName ] = $macroVariables;
                        break;
                    }
                }
            }
        }

        return $macros;
    }


    /**
     * liest eine Apache2 Macro-Konfigurationsdatei ein, die durch Leerzeichen getrennten Variablen  
     * und der möglichen Zeilenumbrüche ("\") enthält.  
     * 
     * @param string $filePath - Apache2 Macro-Konfigurationsdatei 
     * @param array $keysArr -  zB. ["KEY1", "KEY2", "KEYn"];
     * @return array|bool 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    public function parseApacheMacroConfigLinear(
        string $filePath = "",
        array $keysArr = [],
        string $macro = "SSLHost"
    ): array|bool {
        $redisClient      = $this->redisClient();
        $cacheKey   = 'apache_macro_config_' . md5($filePath . json_encode($keysArr) . $macro);
        $cachedData = $redisClient->get($cacheKey);

        $currentline = '';
        if($cachedData)
        {
            return json_decode(json: $cachedData, associative: true);
        }

        if(empty($filePath))
        {
            error_log("Error: Configuration file not set or empty.");
            return false;
        }
        elseif(!file_exists($filePath))
        {
            error_log("Error: Configuration File '$filePath' does not exist.");
            return false;
        }
        elseif(empty($keysArr))
        {
            error_log("Error: Variable \$keys' is empty.");
            return false;
        }

        $result  = [];
        $content = file_get_contents($filePath);
        $content = preg_replace('/\s{2,}/', $this->separator, $content);
        $content = str_replace($this->escape, "", $content);
        $lines   = array_filter(array_map('trim', explode(PHP_EOL, $content)));

        $keyIndex = 0;
        foreach($lines as $index => $line)
        {

            $line = trim(string: strval(value: $line));

            // Ignoriere Kommentare und leere Zeilen
            if(
                empty($line)
                or
                preg_match(pattern: '/^(\s*)$/', subject: $line)
                or
                preg_match(pattern: '/^\#(\s*)/', subject: $line)
                or
                preg_match(pattern: '/^(\s*)\#(\s*)/', subject: $line)
            )
            {
                unset($lines[ $index ]);
                continue;
            }

            // Behandle Zeilenumbrüche mit "\"
            if(substr(string: $line, offset: -1) === $this->escape)
            {
                $currentline = rtrim(string: $line, characters: $this->escape);
                continue;
            }
            else
            {
                $currentline = str_replace(search: ",,", replace: ",", subject: $line);
            }

            if(!empty($currentline))
            {
                $keyval = array_filter(
                    array: str_getcsv(
                        string: $currentline,
                        separator: $this->separator,
                        enclosure: $this->enclosure,
                        escape: $this->escape
                    )
                );

                switch(count(value: $keyval))
                {
                    // if count($keyval) == count($keysArr)
                    case count(value: $keysArr):
                        $result[] = array_combine(
                            keys: $keysArr,
                            values: $keyval
                        );
                        break;
                    default:
                        echo '<pre>';
                        print_r(value: $keysArr);
                        print_r(value: $keyval);
                        echo '</pre>';
                        die('count($keyval){=' . count(value: $keyval) . '} != count($keysArr){=' . count(value: $keysArr) . '}');
                }

            }
        }

        // Cache speichern
        $redisClient->set(key: $cacheKey, value: json_encode($result), expireResolution: 3600); // 1 Stunde Cache
        return $result;
    }


    /**
     * Returns the macro parameters like this :  
     * ```php
     * $macroParameters = [  
     *      "Django"=>["$domain","$port","$PROJECT_NAME","$allowed","$loglevel","$USER",...],  
     *      "DjangoSSL"=>["$keyname","$domain","$port","$PROJECT_NAME","$allowed",...],  
     *      "SSLHost"=>["$domain","$port","$docroot","$allowed","$errorlevel"],  
     *      "SSLSubHost"=>["$domain","$port","$docroot","$keyname","$logfile","$allowed"]  
     * ];
     * ```
     * @return string[][]
     */
    public function extractMacroParameters(): array
    {
        $directory = $this->conf_enabled;
        $files     = scandir($directory);
        $result    = [];

        foreach($files as $file)
        {
            $filePath = $directory . '/' . $file;
            if(is_file($filePath))
            {
                $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach($lines as $line)
                {
                    if(preg_match('/^<Macro\s/', $line))
                    {
                        // Entferne Zeilenumbrüche und trimme die Zeile
                        $line = trim($line);
                        // Entferne das Wort "Macro" und die Klammern
                        $line = preg_replace('/^<Macro\s+|\s*>$/', '', $line);
                        // Ersetze doppelte Leerzeichen durch ein einzelnes Leerzeichen
                        $line = preg_replace('/\s+/', ' ', $line);
                        // Zerlege die Zeile in Teile
                        $linexp = explode(' ', $line);
                        // Erstelle ein assoziatives Array mit dem ersten Element als Schlüssel und dem Rest als Array von Werten
                        $key = array_shift($linexp);
                        // Check if the key already exists
                        if(!array_key_exists($key, $result))
                        {
                            $result[ $key ] = $linexp;
                        }
                        else
                        {
                            \Sentry\captureMessage("Key alrready exists = " . $result[ $key ] . PHP_EOL . ", LINE = " . $line);
                        }
                    }
                }
            }
        }

        return $result;
    }


    public function extractMacroParametersWithCache()
    {
        $redisClient = $this->redisClient();

        $cacheKey           = 'macro_parameters';
        $cacheTimestampsKey = 'macro_parameters_timestamps';

        $cachedData       = $redisClient->get($cacheKey);
        $cachedTimestamps = $redisClient->get($cacheTimestampsKey);

        $directory         = $this->conf_enabled;
        $files             = scandir($directory);
        $currentTimestamps = [];

        foreach($files as $file)
        {
            $filePath = $directory . '/' . $file;
            if(is_file($filePath))
            {
                $currentTimestamps[ $file ] = filemtime($filePath);
            }
        }

        if($cachedData && $cachedTimestamps)
        {
            $cachedTimestamps = json_decode($cachedTimestamps, true);
            if($cachedTimestamps === $currentTimestamps)
            {
                return json_decode($cachedData, true);
            }
        }

        $result = $this->extractMacroParameters();
        $redisClient->set($cacheKey, json_encode($result));
        $redisClient->set($cacheTimestampsKey, json_encode($currentTimestamps));

        return $result;
    }








    /**
     * Funktion zum Lesen der Konfigurationsdatei
     * 
     * @param mixed $filename 
     * @return array 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    public function readConfigFile(string $filename = null): array
    {
        $config    = [];
        $directory = $this->conf_enabled;

        if(file_exists(realpath($directory . DIRECTORY_SEPARATOR . $filename)) and !empty($filename))
        {

            $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach($lines as $line)
            {
                if(strpos($line, '#') === 0 || trim($line) === '')
                    continue;

                [ $key, $value ]      = explode('=', $line, 2);
                $config[ trim($key) ] = trim($value);
            }
        }

        return $config;
    }


    /**
     * Funktion zum Konvertieren des Arrays zu XML
     * 
     * @param mixed $array 
     * @param mixed $xmlWriter 
     * @return void 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    public function arrayToXml(array $array, $xmlWriter)
    {
        foreach($array as $key => $value)
        {
            if(is_array(value: $value))
            {
                $xmlWriter->startElement($key);
                $this->arrayToXml(array: $value, xmlWriter: $xmlWriter);
                $xmlWriter->endElement();
            }
            else
            {
                $xmlWriter->writeElement($key, $value);
            }
        }
    }


    /**
     * Funktion zum Konvertieren des Arrays zu JSON
     * 
     * @param array $array
     * @return string
     */
    public function arrayToJson(array $array): string
    {
        return strval(json_encode(value: $array, flags: JSON_PRETTY_PRINT));
    }



    /**
     * Hauptfunktion
     * 
     * @param string $configFile 
     * @param string $output 
     * @return array|bool|string|void 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    public function processConfig($configFile = 'path/to/your/apache/config/file', $output = "array"): array|bool|string
    {

        $outputarr = [ 'array', 'xml', 'json' ];

        if(
            file_exists(filename: $configFile)
            and
            in_array(needle: $output, haystack: $outputarr)
        )
        {
            // Lesen der Konfigurationsdatei als Array
            $configArray = $this->readConfigFile(filename: $configFile);
            if($output === "array")
            {
                return $configArray;
            }
            if($output === "xml")
            {
                // // Ausgabe als XML
                // echo "XML:\n";
                // $xmlWriter = new XmlWriter();
                // $xmlWriter->openMemory();
                // $xmlWriter->startDocument('1.0', 'UTF-8');
                // $xmlWriter->startElement('configuration');

                // $this->arrayToXml($configArray, $xmlWriter);
                // $xmlWriter->endElement();
                // echo $xmlWriter->outputMemory();
                return false;
            }
            if($output === "json")
            {
                // // Ausgabe als JSON
                return $this->arrayToJson($configArray);
            }
        }

        return true;
    }
}
