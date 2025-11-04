<?php

// Manter os erros importantes, mas filtrar broken pipe
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Handler ESPECÍFICO para broken pipe
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Lista de erros para ignorar
    $ignorar = [
        'Broken pipe',
        'file_put_contents(): Write of'
    ];
    
    foreach ($ignorar as $erro) {
        if (strpos($errstr, $erro) !== false) {
            return true; // Ignora APENAS esses erros
        }
    }
    
    // Para TODOS os outros erros, mostra normalmente
    return false;
}, E_ALL);

// Resto do código...
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
