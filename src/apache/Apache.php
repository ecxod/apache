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
     * erzeugt eine array der Macro Namen und Macro Variablen.   
     * zB so :  
     * [  
     *      SSLHost1 => ["$domain", "$port", "$docroot", "$allowed", "$errorlevel"] ,  
     *      SSLHost2 => ["$domain", "$port", "$docroot", "$allowed", "$errorlevel"]  
     * ]   
     * 
     * @param string $directory
     * @return array|bool
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    function getMacroDefinitions(string $directory): array|bool
    {
        $macros = [];

        $directory ?? "/etc/apache2/conf-enabled";

        // prüfen ob $directory existiert
        if (!is_dir(filename: $directory) and !is_link(filename: $directory)) {
            return false;
        }

        $files = glob(pattern: $directory . '/*.conf');
        sort(array: $files);

        foreach ($files as $file) {
            $content = file_get_contents(filename: $file);
            $lines = explode(separator: "\n", string: $content);

            foreach ($lines as $line) {

                // removing trailing and leading <>, die Zeile darf sonst keine Klammern enthalten
                $line = str_replace(search: ["<", ">"], replace: "", subject: $line);

                if (strpos(haystack: trim(string: $line), needle: 'Macro ') === 0) {
                    $words = preg_split(pattern: '/\s+/', subject: $line, limit: -1, flags: PREG_SPLIT_NO_EMPTY);
                    // zB. <Macro SSLHost $domain $port $docroot ... 
                    $macroName = strval(value: $words[1]);
                    $macroVariables = $words;
                    // wir lassen die ersten beiden Elemente weg
                    array_splice(array: $macroVariables, offset: 0, length: 2);
                    $macros[$macroName] = $macroVariables;
                    break;
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
    function parseApacheMacroConfigLinear(string $filePath = "", array $keysArr = [], string $macro = "SSLHost"): array|bool
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
        $content = str_replace(search: $this->escape, replace: "", subject: $content);
        $lines = array_filter(array: array_map(callback: 'trim', array: explode(separator: PHP_EOL, string: $content)));

        $keyIndex = 0;
        foreach ($lines as $index => $line) {

            $line = trim(string: strval(value: $line));

            // Ignoriere Kommentare und leere Zeilen
            if (
                empty($line) ||
                preg_match(pattern: '/^(\s*)$/', subject: $line) ||
                preg_match(pattern: '/^(\s*)\#(\s*)/', subject: $line)
            ) {
                unset($lines[$index]);
                continue;
            }

            // Behandle Zeilenumbrüche mit "\"
            if (substr(string: $line, offset: -1) === $this->escape) {
                $currentline = rtrim(string: $line, characters: $this->escape);
                continue;
            } else {
                $currentline = str_replace(search: ",,", replace: ",", subject: $line);
            }

            if (!empty($currentline)) {
                $result[] = array_combine(
                    keys: $keysArr,
                    values: str_getcsv(
                        string: $currentline,
                        separator: $this->separator,
                        enclosure: $this->enclosure,
                        escape: $this->escape
                    )
                );
            }
        }

        return  $result;
    }
}
