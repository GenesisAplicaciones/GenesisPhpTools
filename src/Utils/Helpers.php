<?php

namespace GenesisPhpTools\Utils;

class Helpers
{


    /**
     * Decode data from Base64URL
     * @param string $data
     * @param boolean $strict
     * @return boolean|string
     */
    static function base64url_decode($data, $strict = false)
    {
        // Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
        $b64 = strtr($data, '-_', '+/');

        // Decode Base64 string and return the original data
        return base64_decode($b64, $strict);
    }

    static function str_to_utf8($string)
    {
        $tmp = $string;
        $count = 0;
        while (mb_detect_encoding($tmp) == "UTF-8") {
            $tmp = utf8_decode($tmp);
            $count++;
        }
        for ($i = 0; $i < $count - 1; $i++) {
            $string = utf8_decode($string);
        }
        return utf8_encode($string);
    }

    static function fix_utf8($string)
    {
        if (mb_detect_encoding($string) == "UTF-8") {
            return \ForceUTF8\Encoding::fixUTF8($string);
        } else {
            return $string;
        }
    }

    static function to_utf8($string)
    {
        if (mb_detect_encoding($string) == "UTF-8") {
            return \ForceUTF8\Encoding::fixUTF8($string);
        } else {
            return \ForceUTF8\Encoding::toUTF8($string);
        }
    }

    static function force_utf8($string)
    {
        return \ForceUTF8\Encoding::toUTF8($string);
    }

    /**
     * Funcion para eliminar carpetas con contenido
     */
    static function removeDirectory($path)
    {
        $files = glob($path . DIRECTORY_SEPARATOR . '*');
        foreach ($files as $file) {
            is_dir($file) ? removeDirectory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }

    /**
     * Función que extiende al implode() haciendo que si un valor del arreglo pasado es nulo, no lo toma en cuenta.
     * Esto es especialmente útil con datos traigo de una BD, pues nunca se sabe cuando si alguno de los valores en el arreglo será nulo.
     * Cuando a un implode normal se le llama:
     * 	implode(', ', [null, 'valor1'])
     * regresa: ", valor1"
     * Mientras que este metodo regresa: "valor1"
     * Además, permite agregar un valor por defecto para cuando todos los elementos sean nulos.
     */
    static function implode_not_nulls($glue, $array, $default_text = NULL)
    {
        $array_without_nulls = [];
        foreach ($array as $key => $value) {
            if ($value) {
                $array_without_nulls[] = $value;
            }
        }
        if ($default_text && empty($array_without_nulls)) {
            $array_without_nulls =  [$default_text];
        }
        return implode($glue, $array_without_nulls);
    }


    /**
     * @param String $cadena Cadena que se quiere verificar si es un RFC.
     * @return Boolean TRUE si es RFC y FALSE si no.
     */
    static function esRFC(String $cadena)
    {
        return preg_match('/(*UTF8)^(' . REGEX_RFC . ')$/', $cadena); // se le agrega (*UTF8) para que matchee con caracteres como Ñ o & especificados en el regex
    }

    /**
     * @param String $cadena Cadena que se quiere verificar si es un UUID.
     * @return Boolean TRUE si es UUID y FALSE si no.
     */
    static function esUUID(String $cadena)
    {
        return preg_match('/^([a-f0-9A-F]{8}-[a-f0-9A-F]{4}-[a-f0-9A-F]{4}-[a-f0-9A-F]{4}-[a-f0-9A-F]{12})|([0-9]{3}-[0-9]{2}-[0-9]{9})$/', $cadena);
    }

    /**
     * Poniendo temporalmente un error handler custom para que los warnings que se
     * generan abajo se vuelvan excepciones y pueda usarse un bloque try-catch
     * Se restaura con la funcion nativa restore_error_handler().
     */
    static function set_strict_error_handler()
    {
        set_error_handler(static function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        });
    }

    /**
     * Función para validar si están vacíos campos enviados por el usuario.
     * A diferencia de la funcion empty() nativa, si marca como vacías cadenas tipo "   " 
     * y también permite definir como excepciones valores que normalmente serían 
     * tomados como vacíos (por defecto 0 y '0').
     * 
     * @param $field El campo a validar si está vacío.
     * @param Array $exceptions El array con los valores que normalmente se considerarían vacíos que se desea que se consideren como "llenos".
     */
    static function empty_field(&$field, $exceptions = [0, '0']) // el '&' es para pasar el parametro como referencia y permitir pasar datos nulls o sin definir, y manejalos dentro de la función
    {
        // si no está definido, está vacío
        if (!isset($field)) {
            return true;
        }
        // en este punto se sabe que si está definido el valor
        // checar si es igual a una de las excepciones, y si es una de ellas, retornar false indicando que no está vacío
        if (in_array($field, $exceptions, true)) { // se ejecuta in_array en modo estricto para que sea exactamente igual al valor indicado en $exceptions
            return false;
        }
        // finalmente, se le hace trim para quitar espacios en blanco
        if (is_string($field)) {
            $field = trim($field);
        }

        // y luego se checa si está vacío con la funcion empty
        return empty($field);
    }

    static function is_field_true(&$field, $true_values = [1, "1", "true", "TRUE", 'True']) // el '&' es para pasar el parametro como referencia y permitir pasar datos nulls o sin definir, y manejalos dentro de la función
    { 
        // si no está definido, retornar false
        if (!isset($field)) {
            return false;
        }
        // en este punto se sabe que si está definido el valor
        // se ejecuta in_array en modo estricto para checar si $field es exactamente igual a los valores considerados como $true_values
        if (in_array(trim($field), $true_values, true)) {
            return true;
        }
        // si no se fue igual a los valores considerados como true, retornar false
        return false;
    }
}
