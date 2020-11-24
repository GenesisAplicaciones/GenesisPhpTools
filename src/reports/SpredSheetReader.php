<?php

namespace GenesisPhpTools;

class SpredSheetReader
{
    public function __construct()
    {
    }

    public static function getData($input_file_path, $expected_columns = false)
    {
        // obteniendo array de del excel, pero con formato tal cual lo devuelve la funcion
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($input_file_path);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        if (count($sheetData) <= 1) {
            return FALSE;
        }

        // obtenido la fila que contiene las keys
        $file_columns = array_shift($sheetData);
        if (!self::columns_are_valid($file_columns, $expected_columns)) {
            return FALSE;
        }

        // cambiando el formato del array para que sea por filas y key=>value, es decir, ya listo para hacer un insert en el BD
        $procesedData = [];
        foreach ($sheetData as $row) {
            if (!array_filter($row)) {
                continue;
            } // si todos los valores de la fila estan vacios, omitirlos
            $newRow = [];
            foreach ($row as $key => $value) {
                $newRow[$file_columns[$key]] = trim($value);
            }
            $procesedData[] = $newRow;
        }

        // quitando campos nulos
        return self::cleanData($procesedData);
    }

    public static function columns_are_valid($file_columns, $expected_columns)
    {
        if (!$expected_columns) {
            return true;
        } // si no se especifican $expected_columns, se da por hecho que es valido
        $isValid = false; // inicializado a false para que si $file_columns esta vacio y el foreach no se ejecuta, devuelva false la funcion
        foreach ($file_columns as $col) {
            $isValid = in_array($col, $expected_columns);
            if (!$isValid) {
                break;
            }
        }
        return $isValid;
    }

    public static function filesIsSpreadSheet($fileType)
    {
        return
            $fileType == "application/vnd.ms-excel" ||
            $fileType == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
    }

    public static function cleanData($procesedData) {
        foreach ($procesedData as &$row) {
            foreach ($row as $key => $value) {
                if(self::empty_field($value)) {
                    unset($row[$key]);
                }
            }
        }
        return $procesedData;
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
	public static function empty_field(&$field, $exceptions = [0, '0']) { //el '&' es para pasar el parametro como referencia y permitir pasar datos nulls o sin definir, y manejalos dentro de la función
		// si no está definido, está vacío
		if(!isset($field)) {
			return true;
		}
		// en este punto se sabe que si está definido el valor
		// checar si es igual a una de las excepciones, y si es una de ellas, retornar false indicando que no está vacío
		if(in_array($field, $exceptions, true)) { // se ejecuta in_array en modo estricto para que sea exactamente igual al valor indicado en $exceptions
			return false;
		}
		// finalmente, se le hace trim para quitar espacios en blanco y luego checar si está vacío con la funcion empty
		return empty(trim($field));
	}
}
