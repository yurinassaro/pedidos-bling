<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiUrl = $_ENV['EVOLUTION_API_URL'];
$apiKey = $_ENV['EVOLUTION_API_KEY'];
$instance = $_ENV['EVOLUTION_INSTANCE_NAME'];

echo "=== DESATIVANDO CONFIGURAÇÕES PROBLEMÁTICAS ===\n\n";

// Configurações para desabilitar
$settings = [
    'reopen_conversation' => false,
    'conversation_pending' => false,
    'import_contacts' => false,
    'import_messages' => false,
    'chatwoot_auto_create' => false,
    'chatwoot_organization' => null,
    'chatwoot_sign_msg' => false
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/chatwoot/settings/$instance");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($settings));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Resposta: $response\n";

// Tentar também remover integração
echo "\n\n=== REMOVENDO INTEGRAÇÃO CHATWOOT ===\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/chatwoot/delete/$instance");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apiKey"]);

$response = curl_exec($ch);
curl_close($ch);

echo "Delete response: $response\n";