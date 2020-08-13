<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if(!function_exists('reporte_pdf')){
    function reporte_pdf($orientacion, $html_encabezado, $tbody){
        $CI =& get_instance();

        $CI->load->library('Class_pdf');
        $pdf = new Class_pdf($orientacion, 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Inmobiliaria Ro-ye');
        $pdf->SetTitle('Reporte Ro-ye');
        $pdf->SetSubject('Reporte Ro-ye');
        $pdf->SetKeywords('Ro-ye, Reportes');

        $pdf->SetHeaderData('', PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, $html_encabezado);
        
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(10, 30, 10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 10);
        // set font
        // $pdf->SetFont('dejavusans', '', 13);
        // $pdf->SetFont('freeserif', '', 13);
        $pdf->SetFont('helvetica', '', 13);
        $pdf->AddPage($orientacion);       

        /*
            [BEGIN] CUERPO PDF
         */        
        $pdf->writeHTML($tbody, true, false, true, false, '');
        /*
            [END] CUERPO PDF
         */        

        $str_pdf = $pdf->Output('', 'S');
        return base64_encode($str_pdf);
    }
}

if(!function_exists('reporte_excel')){
	function reporte_excel($columnas, $encabezado, $data, $nombre_hoja, $filename) {
		$CI =& get_instance();
        $CI->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();

        // Se asignan las propiedades del libro
        $objPHPExcel->getProperties()->setCreator("Inmobiliaria Ro-ye") //Autor
                ->setLastModifiedBy("Administrador") //Ultimo usuario que lo modificÃ³
                ->setTitle("Inmobiliaria Ro-ye")
                ->setSubject("Reporte Inmobiliaria Ro-ye")
                ->setCategory("Reporte Excel");

        $objsheet = $objPHPExcel->setActiveSheetIndex(0);

        $style_titles = [
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'FFFFFF'), 
            ],
            'font' => [
                'bold' => true,
                'color' => array('rgb' => '000000')
            ]
        ];

        $style_titles2 = [
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'AB3EAA'), 
            ],
            'font' => [
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF')
            ]
        ];
        
        // Se agregan los titulos del reporte
        $col = 0;
        foreach ($columnas as $titulo) {
            $objsheet->setCellValueByColumnAndRow($col, 2, $titulo);
            $col++;
        }        

        $ultima_letra = PHPExcel_Cell::stringFromColumnIndex($col - 1);
        
        $objsheet->getStyle('A2:'.$ultima_letra.'2')->applyFromArray($style_titles2);

        $logo = 'assets/img/logo_roye.jpg';            
            $objDrawing = new PHPExcel_Worksheet_Drawing();
            $objDrawing->setName('Logo');
            $objDrawing->setDescription('Logo');
            $objDrawing->setPath($logo);
            $objDrawing->setResizeProportional(true);
            $objDrawing->setWidth(80);
            $objDrawing->setCoordinates('A1');
            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

        $objsheet->mergeCells('C1:'.$ultima_letra.'1');
        $objsheet->setCellValue('C1', strtoupper($encabezado));
        $objsheet->getStyle('A1:'.$ultima_letra.'1')->applyFromArray($style_titles);
        $objsheet->getRowDimension(1)->setRowHeight(45);
         
        $fila = 3; //Empieza desde la fila 3
       
        foreach ($data as $key => $value) {  
            $letra = 'A'; 
            for($i = 0; $i < count($value); $i++){                
                $objsheet->setCellValue($letra . $fila, $value[$i]);
                $letra++;
            }                                                                       
            $fila++;
        }
                
        // Se asigna el nombre a la hoja
        $objPHPExcel->getActiveSheet()->setTitle(strtoupper($nombre_hoja));
        // Se activa la hoja para que sea la que se muestre cuando el archivo se abre
        $objPHPExcel->setActiveSheetIndex(0);
        // Inmovilizar paneles 
        //$objPHPExcel->getActiveSheet(0)->freezePane('A4');
        $objPHPExcel->getActiveSheet(0)->freezePaneByColumnAndRow(0, 3);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');        

        //* Genera una salida de archivo excel en base64 para poder descargar
        ob_start();
        $objWriter->save("php://output");
        $xlsData = ob_get_contents();
        ob_end_clean();
        
        return base64_encode($xlsData);
    }
}