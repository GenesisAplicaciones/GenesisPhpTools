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

        return $procesedData;
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
}
