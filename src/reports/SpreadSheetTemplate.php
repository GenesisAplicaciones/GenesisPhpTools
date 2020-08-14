<?php

namespace GenesisPhpTools;

class SpreadSheetTemplate
{
    private $_doc_config = [
        'logo' => null,
        'logo_wide' => null,
        'logo_square' => null,
        'solution_name' => 'example2',
        'title' => null,
        'subject' => null,
        'category' => null,
        'last_modified_by' => null,
        'color_title_bg' => 'FFFFFF',
        'color_title_font' => '000000',
        'color_headers_bg' => 'FFA0A0A0', //color de background del heaader
        'color_table_border' => 'FFA0A0A0',
    ];

    public function __construct($doc_config = null)
    {
        // Si se para un array de doc_config, solamente actualizar los campos necesarios para no afectar a los que ya existen por defecto que no se especificaron en el nuevo doc_config.
        if($doc_config) {
            foreach ($doc_config as $key => $value) {
                $this->_doc_config[$key] = $value;
            }
        }
        // Si se pasa un logo_wide o logo_square especificamente, se conserva su valor, pero si solo se pasa un logo sin especificar, este se usa para los otros logos de diferente relacion de aspecto
        $this->_doc_config['logo_wide'] = $this->_doc_config['logo_wide'] ?: $this->_doc_config['logo'];
        $this->_doc_config['logo_square'] = $this->_doc_config['logo_square'] ?: $this->_doc_config['logo'];
        
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
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder());
        //Creacion del objeto
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Se asignan las propiedades del libro
        $spreadsheet->getProperties()->setCreator("TimbraXML") //Autor
            ->setLastModifiedBy($this->_doc_config['last_modified_by'] ?: $this->_doc_config['solution_name']) //Ultimo usuario que lo modificó
            ->setTitle($this->_doc_config['title'] ?: "Reporte de " . $this->_doc_config['solution_name'])
            ->setSubject($this->_doc_config['subject'] ?: "Reporte de " . $tipo_de_reporte)
            ->setCategory($this->_doc_config['category'] ?: "Reporte Excel");

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
        $estilos_titulo = array(
            'alignment' => array(
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ),
            'fill' => array(
                'type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => array('rgb' => $this->_doc_config['color_title_bg']),
            ),
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => $this->_doc_config['color_title_font'])
            )
        );

        $estilos_cabeceras = [
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
                    'argb' => $this->_doc_config['color_headers_bg'],
                ],
                'endColor' => [
                    'argb' => $this->_doc_config['color_headers_bg'],
                ],
            ],
        ];

        $cuerpo_tabla = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    'color' => ['argb' => $this->_doc_config['color_table_border']],
                ],
            ],
        ];


        // Se asigna el nombre a la hoja
        $spreadsheet->getActiveSheet()->setTitle('Reporte de ' . strtolower($tipo_de_reporte));
        // Inmovilizar paneles 
        $spreadsheet->getActiveSheet(0)->freezePaneByColumnAndRow(0, 3);
        $alto_cabecera = 27;
        //agregando a la cabecera del excel una imagen
        if ($this->_doc_config['logo']) {
            $objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $objDrawing->setName('Logo');
            $objDrawing->setDescription('Logo');
            $objDrawing->setPath($this->_doc_config['logo']);
            $objDrawing->setResizeProportional(true);
            $objDrawing->setHeight($alto_cabecera + 5);
            $objDrawing->setCoordinates('A1');
            $objDrawing->setWorksheet($objsheet);
        }
        //dandole formato a la cabecera
        $objsheet->mergeCells('A1:B1');
        $objsheet->mergeCells('C1:' . $ultima_letra . '1');
        $objsheet->setCellValue('C1', "REPORTE DE " . strtoupper($tipo_de_reporte) . " | " . $texto_usuario);
        $objsheet->getStyle('A1:' . $ultima_letra . '1')->applyFromArray($estilos_cabeceras);
        $objsheet->getRowDimension(1)->setRowHeight($alto_cabecera);

        //configurando ancho de las columnas
        for ($i = 'A'; $i <= $ultima_letra; $i++) {
            // $objsheet->getColumnDimension($i)->setAutoSize(true);
            $objsheet->getColumnDimension($i)->setWidth(20);
        }

        //aplicando estilos
        $objsheet->getStyle('A1:' . $ultima_letra . '1')->applyFromArray($estilos_titulo);
        $objsheet->getStyle('A2:' . $ultima_letra . '2')->applyFromArray($estilos_cabeceras);
        $objsheet->getStyle('A2:' . $ultima_letra . $fila)->applyFromArray($cuerpo_tabla);
        $objsheet->getStyle('D3:D' . $fila)
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        if ($forzar_descarga) {
            $filename = "REPORTE_" . strtoupper($tipo_de_reporte) . "(" . $texto_usuario . ").xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }

        return $spreadsheet;
    }
}
