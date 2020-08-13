<?php
require __DIR__ . '/../src/reports/SpreadSheetTemplate.php';

echo('Iniciando prueba...');

$example_row = (object)[
    'id' => 1,
    'text' => 'UNRO',
    'text1' => 'xsds',
    'text2' => 'UNRfrfdfO',
    'text3' => 'UNfdfdRO',
];
$datos_reporte = [$example_row, $example_row, $example_row ];

$columnas = [];

$template = new SpreadSheetTemplate([
    'logo' => 'assets/logo.png',
    'solution_name' => 'MY COMPANY'
]);
$template->generar_reporte_excel(
    "PRUEBA",
    'ANOMAXDXDXD',
    $datos_reporte,
    true,
    $columnas,
);
