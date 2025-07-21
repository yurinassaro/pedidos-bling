<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Cache;

class WhatsAppController extends Controller
{
    protected $evolutionApiUrl;
    protected $evolutionApiKey;
    protected $instanceName;
    protected $whatsappGroupId;

    public function __construct()
    {
        $this->evolutionApiUrl = env('EVOLUTION_API_URL', 'http://localhost:8080');
        $this->evolutionApiKey = env('EVOLUTION_API_KEY');
        $this->instanceName = env('EVOLUTION_INSTANCE_NAME', 'pedidos');
        $this->whatsappGroupId = env('WHATSAPP_GROUP_ID');
    }

    /**
     * Envia o pedido para o WhatsApp - SIMPLIFICADO
     * Sem verificação de status, sem salvar no banco
     */
    public function sendOrderToWhatsApp(Request $request)
    {
        try {
            $orderNumber = $request->input('order_number');
            $customerName = $request->input('customer_name');
            $products = $request->input('products');
            
            // ANTI-SPAM: Verificar se não foi enviado recentemente
            $lockKey = "whatsapp_lock_{$orderNumber}";
            if (Cache::has($lockKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aguarde antes de reenviar'
                ], 429);
            }
            
            // Bloquear por 30 segundos
            Cache::put($lockKey, true, 30);

            // Log para debug
            Log::info('Iniciando envio de pedido', [
                'order' => $orderNumber,
                'customer' => $customerName,
                'products_count' => count($products)
            ]);
            
            // Montar mensagem
            $uniqueId = uniqid();
            $message = "*{$orderNumber} - {$customerName}*\n";
            $message .= "Data: " . now()->format('d/m/Y') . "\n";
            $message .= "ID: {$uniqueId}\n\n"; // Isso previne reenvio idêntico
            
            // Enviar mensagem de texto
            $textResponse = $this->sendTextMessage($message);

            if (!$textResponse['success']) {
                Log::error('Falha ao enviar mensagem de texto', $textResponse);
                throw new \Exception($textResponse['error'] ?? 'Falha ao enviar mensagem de texto');
            }

            // Contador de imagens enviadas com sucesso
            $imagesSent = 0;
            $totalImages = 0;

            // Enviar imagens dos produtos
            foreach ($products as $product) {
                if (!empty($product['image']) && $product['image'] !== 'INSIRA UMA FOTO') {
                    $totalImages++;
                    
                    $imageMessage = "📦 *{$product['description']}*\n";
                    $imageMessage .= "Quantidade: {$product['quantity']}";
                    
                    $imageResponse = $this->sendImageMessage($product['image'], $imageMessage);
                    
                    if ($imageResponse['success']) {
                        $imagesSent++;
                    } else {
                        Log::warning('Falha ao enviar imagem', [
                            'product' => $product['description'],
                            'error' => $imageResponse['error'] ?? 'Unknown error'
                        ]);
                    }
                    
                    // Pequeno delay entre envios para evitar bloqueio
                    usleep(500000); // 0.5 segundos
                }
            }

            Log::info('Envio concluído', [
                'order' => $orderNumber,
                'images_sent' => $imagesSent,
                'total_images' => $totalImages
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido enviado com sucesso',
                'details' => [
                    'text_sent' => true,
                    'images_sent' => $imagesSent,
                    'total_images' => $totalImages
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar pedido para WhatsApp', [
                'error' => $e->getMessage(),
                'order' => $orderNumber ?? 'Unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envia mensagem de texto via Evolution API
     */
    private function sendTextMessage($text)
    {
        try {
            Log::info('Enviando mensagem de texto', [
                'group' => $this->whatsappGroupId,
                'text_length' => strlen($text)
            ]);

            $response = Http::timeout(30)->withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->evolutionApiUrl}/message/sendText/{$this->instanceName}", [
                'number' => $this->whatsappGroupId,
                'text' => $text,
                'delay' => 0
            ]);

            Log::info('Resposta do envio de texto', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'messageId' => $response->json('key.id') ?? null
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem de texto', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envia imagem via Evolution API
     */
    private function sendImageMessage($imageUrl, $caption = '')
    {
        try {
            // Verificar se a URL da imagem é válida
            if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
                Log::warning('URL de imagem inválida', ['url' => $imageUrl]);
                return [
                    'success' => false,
                    'error' => 'URL de imagem inválida'
                ];
            }

            Log::info('Enviando imagem', [
                'url' => $imageUrl,
                'caption' => $caption
            ]);

            $response = Http::timeout(60)->withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->evolutionApiUrl}/message/sendMedia/{$this->instanceName}", [
                'number' => $this->whatsappGroupId,
                'mediatype' => 'image',
                'media' => $imageUrl,
                'caption' => $caption
            ]);

            Log::info('Resposta do envio de imagem', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200) // Primeiros 200 caracteres
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'messageId' => $response->json('key.id') ?? null
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao enviar imagem', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}