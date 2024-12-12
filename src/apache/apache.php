<?php

declare(strict_types=1);

namespace Ecxod\Apache;

/** 
 * @package Ecxod\Apache 
 */
class Apache
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
     * @return array|bool 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    function parseApacheMacroConfig(string $filePath = null): array|bool
    {
        // Check if the file exists
        if (!file_exists($filePath)) {
            error_log("Error: Configuration File '$filePath' does not exist.");
            echo "Configuration file not found. Check the error log for details.";
            return false;
        } elseif (empty($filePath)) {
            error_log("Error: Variable \$filePath' is empty.");
            echo "Configuration file unknown. Check the error log for details.";
            return false;
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



    /**
     * liest eine Apache2 Macro-Konfigurationsdatei mit PHP ein.
     * Diese Funktion liest die durch Leerzeichen getrennten Variablen und der möglichen Zeilenumbrüche mit "\".
     * Beispielzeile :
     * # This is a comment
     * KEY1       KEY2       KEYn
     * VALUEa1    VALUEa2    VALUEan
     * VALUEb1    VALUEb2    VALUEbn
     * VALUEc1    VALUEc2    VALUEcn
     * 
     * @param string $filePath 
     * @return array|bool 
     * @author Christian <c@zp1.net>
     * @link https://github.com/ecxod/apache
     * @license MIT
     * @version 1.0.0
     */
    function parseApacheMacroConfigLinear(string $filePath = null): array|bool
    {
        if (!file_exists($filePath)) {
            error_log("Error: Configuration File '$filePath' does not exist.");
            echo "Configuration file not found. Check the error log for details.";
            return false;
        } elseif (empty($filePath)) {
            error_log("Error: Variable \$filePath' is empty.");
            echo "Configuration file unknown. Check the error log for details.";
            return false;
        }

        $content = file_get_contents($filePath);
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        $result = [];
        $keys = [];
        $currentline = '';

        foreach ($lines as $index => $line) {
            $line = trim($line);

            // Ignoriere Kommentare und leere Zeilen
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            // Behandle Zeilenumbrüche mit "\"
            if (substr($line, -1) === '\\') {
                $currentline .= rtrim($line, '\\') . ' ';
                continue;
            }

            $currentline .= $line;

            if ($index === 0) {
                $keys = preg_split('/\s+/', $currentline);
            } else {
                $values = preg_split('/\s+/', $currentline);
                if (count($values) === count($keys)) {
                    $result[] = array_combine($keys, $values);
                }
            }
        }

        return $result;
    }
}
