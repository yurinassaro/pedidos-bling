<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiUrl = $_ENV['EVOLUTION_API_URL'];
$apiKey = $_ENV['EVOLUTION_API_KEY'];
$instance = $_ENV['EVOLUTION_INSTANCE_NAME'];

echo "=== REMOVENDO WEBHOOK N8N ===\n\n";

// Limpar webhook
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/webhook/set/$instance");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'enabled' => false,
    'url' => '',
    'webhook_by_events' => false,
    'events' => []
]));

$response = curl_exec($ch);
curl_close($ch);

echo "Resposta: $response\n";

