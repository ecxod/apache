<?php

declare(strict_types=1);

namespace Ecxod\Apache;

/** 
 * @package Ecxod\Apache 
 */
class Apache
{

    public $escape;
    public $enclosure;
    public $separator;

    function __construct()
    {
        $this->escape = "\\";
        $this->enclosure = "\'";
        $this->separator = ",";
    }

    /**
     * liest eine Apache2 Macro-Konfigurationsdatei mit PHP ein.
     * Diese Funktion liest die durch Leerzeichen getrennten Variablen und der möglichen Zeilenumbrüche mit "\".
     * Beispielzeile :
     * WERT1 WERT2 WERT3 WERT4 WERT5 
     * oder
     * WERT1 WERT2 WERT3 \
     * WERT4 WERT5 
     * 
     * @param string $filePath - Apache2 Macro-Konfigurationsdatei 
     * @return array|bool 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    function parseApacheMacroConfig(string $filePath = ""): array|bool
    {
        // Check if the file exists
        if (empty($filePath)) {
            error_log("Error: Configuration file not set or empty.");
            return false;
        } else if (!file_exists($filePath)) {
            error_log("Error: Configuration File '$filePath' does not exist.");
            return false;
        }

        $content = file_get_contents(filename: $filePath);
        $lines = explode(separator: PHP_EOL, string: $content);
        $result = [];
        $currentValue = '';

        foreach ($lines as $line) {
            $line = trim(strval($line));

            // Ignoriere Kommentare und leere Zeilen
            if (empty($line) || preg_match('/^(\s+)\#/',$line)) {
                continue;
            }

            // Behandle Zeilenumbrüche mit "\"
            if (substr(string: $line, offset: -1) === $this->escape) {
                $currentValue .= rtrim(string: $line, characters: $this->escape);
                continue;
            }

            $currentValue .= $line;

            // Trenne Schlüssel und Wert
            $parts = preg_split('/\s+/', $currentValue, 2);
            if (count($parts) === 2) {
                $result[$parts[0]] = $parts[1];
            }

            $currentValue = '';
        }

        return $result;
    }



    /**
     * liest eine Apache2 Macro-Konfigurationsdatei ein.  
     * Diese Funktion liest die durch Leerzeichen getrennten Variablen und der möglichen Zeilenumbrüche mit "\".  
     * Beispielzeile :  
     * \# This is a comment  
     * VALUEa1    VALUEa2    \  
     *               VALUEan  
     * VALUEb1    VALUEb2    VALUEbn  
     * VALUEc1    VALUEc2    VALUEcn  
     * 
     * @param string $filePath - Apache2 Macro-Konfigurationsdatei 
     * @param array $keysArr -  zB. ["KEY1", "KEY2", "KEYn"];
     * @return array|bool 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    function parseApacheMacroConfigLinear(string $filePath = "", array $keysArr = [], string $macro="SSLHost"): array|bool
    {

        $currentline = '';
        if (empty($filePath)) {
            error_log("Error: Configuration file not set or empty.");
            return false;
        } elseif (!file_exists($filePath)) {
            error_log("Error: Configuration File '$filePath' does not exist.");
            return false;
        } elseif (empty($keysArr)) {
            error_log("Error: Variable \$keys' is empty.");
            return false;
        }

        $content = file_get_contents(filename: $filePath);
        $content = preg_replace(pattern: '/\s{2,}/', replacement: $this->separator, subject: $content);
        $content = str_replace(search: $this->escape ,replace: "  ", subject: $content);
        $lines = array_filter(array: array_map(callback: 'trim', array: explode( separator: PHP_EOL, string: $content)));


        foreach ($lines as $index => $line) {

            $line = trim(string: strval(value: $line));

            // Ignoriere Kommentare und leere Zeilen
            if (empty($line) || preg_match(pattern: '/^(\s*)\#(\s*)/',subject: $line)) {
                continue;
            }

            // Behandle Zeilenumbrüche mit "\"
            if (substr(string: $line, offset: -1) === $this->escape) {
                $currentline = rtrim(string: $line, characters: $this->escape);
                continue;
            } 
            else {
                $currentline = "$line ";
            }
            $lines[$index] = "$currentline ";

            if(!empty($currentline)) $data[] = str_getcsv(string: $currentline, separator: $this->separator, enclosure: $this->enclosure, escape: $this->escape);
        }


        return  $data;
    }
}
