<?php

/**
 * Bootstrap para las clases de GenesisPhpTools
 * Este archivo se carga usando composer.json para asegurar la carga de las librerias requeridas
 */

$paths = [
    __DIR__ . '/../../vendor/autoload.php', // En caso de que GenesisPhpTools se clone directamente
    __DIR__ . '/../../../autoload.php', // En caso de que GenesisPhpTools sea una dependencia de composer
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        return;
    }
}

throw new \Exception('Composer autoloader could not be found. Install dependencies with `composer install` and try again.');
