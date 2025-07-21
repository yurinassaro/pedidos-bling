<?php
// Arquivo: test-whatsapp.php
// Coloque na raiz do projeto e execute: php test-whatsapp.php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== TESTE DE CONEXÃO WHATSAPP ===\n\n";

// Pegar configurações
$apiUrl = $_ENV['EVOLUTION_API_URL'] ?? 'http://localhost:8080';
$apiKey = $_ENV['EVOLUTION_API_KEY'] ?? '';
$instance = $_ENV['EVOLUTION_INSTANCE_NAME'] ?? 'pedidos';
$groupId = $_ENV['WHATSAPP_GROUP_ID'] ?? '';

echo "Configurações:\n";
echo "- API URL: $apiUrl\n";
echo "- API Key: " . (empty($apiKey) ? "❌ NÃO CONFIGURADA" : "✅ Configurada") . "\n";
echo "- Instance: $instance\n";
echo "- Group ID: $groupId\n\n";

if (empty($apiKey)) {
    echo "❌ ERRO: API Key não está configurada no .env\n";
    exit(1);
}

if (empty($groupId)) {
    echo "❌ ERRO: Group ID não está configurado no .env\n";
    exit(1);
}

// Teste 1: Verificar status da instância
echo "Teste 1: Verificando status da instância...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/instance/connectionState/$instance");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erro de conexão: $error\n";
    echo "Verifique se a Evolution API está rodando em $apiUrl\n";
    exit(1);
}

echo "HTTP Code: $httpCode\n";
echo "Resposta: $response\n\n";

if ($httpCode !== 200) {
    echo "❌ Problema na conexão com a API\n";
    exit(1);
}

// Teste 2: Enviar mensagem simples
echo "Teste 2: Enviando mensagem de teste...\n";

$message = "🧪 Teste de conexão Laravel + WhatsApp\n";
$message .= "Data: " . date('d/m/Y H:i:s');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/message/sendText/$instance");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'number' => $groupId,
    'text' => $message
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Resposta: $response\n\n";

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Mensagem enviada com sucesso!\n";
    echo "Verifique o grupo no WhatsApp\n";
} else {
    echo "❌ Erro ao enviar mensagem\n";
    
    // Tentar decodificar erro
    $errorData = json_decode($response, true);
    if (isset($errorData['message'])) {
        echo "Erro: " . $errorData['message'] . "\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
?>