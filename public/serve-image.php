<?php
error_reporting(0);
ignore_user_abort(true);
// Caminho base do storage
$storagePath = __DIR__ . '/../storage/app/public/';

// Pegar o caminho da imagem da URL
$imagePath = $_GET['path'] ?? '';

// Validar se o caminho está dentro de pedidos/imagens
if (!preg_match('/^pedidos\/imagens\/[\w\-\.]+\.(jpg|jpeg|png|gif|webp)$/i', $imagePath)) {
    http_response_code(403);
    die('Acesso negado');
}

// Caminho completo do arquivo
$fullPath = $storagePath . $imagePath;

// Verificar se o arquivo existe
if (!file_exists($fullPath)) {
    http_response_code(404);
    die('Arquivo não encontrado');
}

// Determinar o tipo MIME
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp'
];

$extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Enviar headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=3600');

// Enviar o arquivo
readfile($fullPath);