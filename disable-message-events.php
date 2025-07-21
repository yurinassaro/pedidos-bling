<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiUrl = $_ENV['EVOLUTION_API_URL'];
$apiKey = $_ENV['EVOLUTION_API_KEY'];
$instance = $_ENV['EVOLUTION_INSTANCE_NAME'];

echo "Desativando eventos de mensagem...\n";

// Configurar para NÃO escutar eventos
$settings = [
    'reject_call' => false,
    'msg_call' => false,
    'groups_ignore' => true,
    'always_online' => false,
    'read_messages' => false,
    'read_status' => false,
    'sync_full_history' => false
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/instance/settings/$instance");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($settings));

$response = curl_exec($ch);
curl_close($ch);
