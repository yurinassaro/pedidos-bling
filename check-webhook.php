<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiUrl = $_ENV['EVOLUTION_API_URL'];
$apiKey = $_ENV['EVOLUTION_API_KEY'];
$instance = $_ENV['EVOLUTION_INSTANCE_NAME'];

echo "=== VERIFICANDO CONFIGURAÇÕES DA INSTÂNCIA ===\n\n";

// 1. Status da instância
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/instance/fetchInstances");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apiKey"]);
$response = curl_exec($ch);
curl_close($ch);

echo "Instâncias:\n";
$instances = json_decode($response, true);
foreach ($instances as $inst) {
    if ($inst['instance']['instanceName'] === $instance) {
        echo json_encode($inst, JSON_PRETTY_PRINT) . "\n";
    }
}

// 2. Configurações de webhook
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/webhook/find/$instance");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apiKey"]);
$response = curl_exec($ch);
curl_close($ch);

echo "\n\nWebhooks:\n";
echo $response . "\n";

// 3. Configurações de eventos
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/instance/settings/$instance");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apiKey"]);
$response = curl_exec($ch);
curl_close($ch);

echo "\n\nConfigurações:\n";
echo $response . "\n";