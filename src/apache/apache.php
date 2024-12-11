<?php

declare(strict_types=1);

namespace Ecxod\Apache;

/** 
 * @package Ecxod\Apache 
 */
class Apache extends \PHPUnit\Framework\TestCase
{
    /**
     * liest eine Apache2 Macro-Konfigurationsdatei mit PHP ein.
     * Diese Funktion liest die durch Leerzeichen getrennten Variablen und der möglichen Zeilenumbrüche mit "\".
     * Beispielzeile :
     * WERT1 WERT2 WERT3 WERT4 WERT5 
     * oder
     * WERT1 WERT2 WERT3 \
     * WERT4 WERT5 
     * 
     * @param string $filePath 
     * @return array 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    function parseApacheMacroConfig(string $filePath = null)
    {
        // Check if the file exists
        if (!file_exists($filePath)) {
            error_log("Error: Configuration File '$filePath' does not exist.");
            die("Configuration file not found. Check the error log for details.");
        } elseif (empty($filePath)) {
            error_log("Error: Variable \$filePath' is empty.");
            die("Configuration file unknown. Check the error log for details.");
        }

        $content = file_get_contents(filename: $filePath);
        $lines = explode(separator: "\n", string: $content);
        $result = [];
        $currentKey = '';
        $currentValue = '';

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignoriere Kommentare und leere Zeilen
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            // Behandle Zeilenumbrüche mit "\"
            if (substr(string: $line, offset: -1) === '\\') {
                $currentValue .= rtrim(string: $line, characters: '\\');
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
}
