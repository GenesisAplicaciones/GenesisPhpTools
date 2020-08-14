<?php

namespace GenesisPhpTools;

class SpreadSheetTemplate
{
    private $_instance_config = [
        'logo' => null,
        'solution_name' => 'EXAMPLE'
    ];

    public function __construct($instance_config)
    {
        $this->_instance_config = $instance_config;
    }

    /**
     * Genera un reporte en excel con ub formato estándar y fuerza su descarga de manera opcional.
     * Los títulos de las columnas de generan automáticamente a partir de los nombres de los campos en $datos_reporte,
     * pero es posible especificar otros con el parametro $columnas['titulos'].
     * 
     * @param string $tipo_de_reporte Cadena para indicar de que es el reporte (productos, cuentas, usuarios, etc).
     * @param string $texto_usuario Cadena con datos para identificar al usuario (como el RFC o razon social).
     * @param array $datos_reporte Array con los datos traidos de la BD.
     * @param boolean $forzar_descarga Indica si se forzará la descarga. Útil para cuando no se quiere forzar directamente y se quiere retornar para codificar en base64.
     * @param array $columnas["excluidas"] Contiene un array con los nombres de las columnas excluidas de visualizarse (según los nombres de $datos_reporte). Ej. [ "excluidas" => ['id_cuenta', 'id_usuario'] ]
     * @param array $columnas["renombradas"] Cambia los nombre de uno o más columnas especificando su nombre original. Ej. [ "renombradas" => [ "control_access" => "Acceso al portal" ] ]
     * @param array $columnas["titulos"] Se usa para sobreescribir TODOS los títulos. Contiene un array simple con ellos. Ej. [ "titulos" => ['Cuenta', 'Nombre', 'Usuario'] ]
     * 
     * @return PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    function generar_reporte_excel($tipo_de_reporte, $texto_usuario, $datos_reporte, $forzar_descarga = false, $columnas = null)
    {
        ini_set("memory_limit", "512M");
        //Para dar formato a los datos
        PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder());
        //Creacion del objeto
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Se asignan las propiedades del libro
        $spreadsheet->getProperties()->setCreator("TimbraXML") //Autor
            ->setLastModifiedBy($this->_instance_config['solution_name']) //Ultimo usuario que lo modificó
            ->setTitle("Reporte " . $this->_instance_config['solution_name'])
            ->setSubject("Reporte de " . $tipo_de_reporte)
            ->setCategory("Reporte Excel");

        //Obteniendo la hoja de trabajo actual (la primera, por defecto).
        $objsheet = $spreadsheet->getActiveSheet();

        // Se agregan los titulos del reporte
        $col = 0;
        $fila = 2;
        if (isset($columnas['titulos']) && $columnas['titulos']) {
            foreach ($columnas['titulos'] as $titulo) {
                $col++;
                $objsheet->setCellValueByColumnAndRow($col, $fila, $titulo);
            }
        } else {
            if ($datos_reporte) {
                foreach ($datos_reporte[0] as $key => $value) {
                    if (isset($columnas['excluidas'])) {
                        if (!in_array($key, $columnas['excluidas'])) {
                            $col++;
                            $titulo = isset($columnas['renombradas'][$key]) ? $columnas['renombradas'][$key] :  ucfirst(str_replace("_", " ", $key));
                            $objsheet->setCellValueByColumnAndRow($col, $fila, $titulo);
                        }
                    } else {
                        $col++;
                        $titulo = isset($columnas['renombradas'][$key]) ? $columnas['renombradas'][$key] :  ucfirst(str_replace("_", " ", $key));
                        $objsheet->setCellValueByColumnAndRow($col, $fila, $titulo);
                    }
                }
            }
        }
        // Escribiendo los datos del reporte en el excel
        foreach ($datos_reporte as $i => $row) {
            $fila++;
            $col = 0;
            foreach ($row as $key => $value) {
                if (isset($columnas['excluidas'])) {
                    if (!in_array($key, $columnas['excluidas'])) {
                        $col++;
                        $colChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $objsheet->setCellValue($colChar . $fila, $value);
                    }
                } else {
                    $col++;
                    $colChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $objsheet->setCellValue($colChar . $fila, $value);
                }
            }
        }
        if (!$datos_reporte) {
            $objsheet->setCellValue('A' . $fila, 'Sin registros');
        }
        $ultima_letra = $colChar ?: 'E';

        //configuracion de estilos
        $estilos_cabecera = array(
            'alignment' => array(
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ),
            'fill' => array(
                'type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => array('rgb' => 'FFFFFF'),
            ),
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => '000000')
            )
        );

        $estilos_titulos = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => [
                    'argb' => 'FFA0A0A0',
                ],
                'endColor' => [
                    'argb' => 'FFA0A0A0',
                ],
            ],
        ];

        $cuerpo_tabla = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FFA0A0A0'],
                ],
            ],
        ];


        // Se asigna el nombre a la hoja
        $spreadsheet->getActiveSheet()->setTitle('Reporte de ' . strtolower($tipo_de_reporte));
        // Inmovilizar paneles 
        $spreadsheet->getActiveSheet(0)->freezePaneByColumnAndRow(0, 3);
        $alto_cabecera = 27;
        //agregando a la cabecera del excel una imagen
        if ($this->_instance_config['logo']) {
            $objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $objDrawing->setName('Logo');
            $objDrawing->setDescription('Logo');
            $objDrawing->setPath($this->_instance_config['logo']);
            $objDrawing->setResizeProportional(true);
            $objDrawing->setHeight($alto_cabecera + 5);
            $objDrawing->setCoordinates('A1');
            $objDrawing->setWorksheet($objsheet);
        }
        //dandole formato a la cabecera
        $objsheet->mergeCells('A1:B1');
        $objsheet->mergeCells('C1:' . $ultima_letra . '1');
        $objsheet->setCellValue('C1', "REPORTE DE " . strtoupper($tipo_de_reporte) . " | " . $texto_usuario);
        $objsheet->getStyle('A1:' . $ultima_letra . '1')->applyFromArray($estilos_titulos);
        $objsheet->getRowDimension(1)->setRowHeight($alto_cabecera);

        //configurando ancho de las columnas
        for ($i = 'A'; $i <= $ultima_letra; $i++) {
            // $objsheet->getColumnDimension($i)->setAutoSize(true);
            $objsheet->getColumnDimension($i)->setWidth(20);
        }

        //aplicando estilos
        $objsheet->getStyle('A1:' . $ultima_letra . '1')->applyFromArray($estilos_cabecera);
        $objsheet->getStyle('A2:' . $ultima_letra . '2')->applyFromArray($estilos_titulos);
        $objsheet->getStyle('A2:' . $ultima_letra . $fila)->applyFromArray($cuerpo_tabla);
        $objsheet->getStyle('D3:D' . $fila)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        if ($forzar_descarga) {
            $filename = "REPORTE_" . strtoupper($tipo_de_reporte) . "(" . $texto_usuario . ").xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }

        return $spreadsheet;
    }
}
