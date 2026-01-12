<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WApiService
{
    protected string $baseUrl = 'https://api.w-api.app/v1';
    protected string $token;
    protected string $instanceId;
    protected string $groupId;

    public function __construct()
    {
        $this->token = config('services.wapi.token', env('WAPI_TOKEN', ''));
        $this->instanceId = config('services.wapi.instance_id', env('WAPI_INSTANCE_ID', ''));
        $this->groupId = config('services.wapi.group_id', env('WAPI_GROUP_ID', ''));
    }

    /**
     * Verifica se a configuração está completa
     */
    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->instanceId) && !empty($this->groupId);
    }

    /**
     * Verifica o status da instância
     */
    public function getInstanceStatus(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                ])
                ->get("{$this->baseUrl}/instance/status-instance", [
                    'instanceId' => $this->instanceId,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'connected' => $response->json('connected', false),
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('WApiService: Erro ao verificar status', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envia mensagem de texto
     */
    public function sendText(string $phone, string $message, int $delay = 5): array
    {
        try {
            Log::info('WApiService: Enviando texto', [
                'phone' => $phone,
                'message_length' => strlen($message),
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/message/send-text?instanceId={$this->instanceId}", [
                    'phone' => $phone,
                    'message' => $message,
                    'delayMessage' => $delay,
                    'disableTestMsg' => true,
                ]);

            Log::info('WApiService: Resposta envio texto', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'messageId' => $response->json('messageId'),
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('WApiService: Erro ao enviar texto', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica se a URL tem extensão de imagem válida para W-API
     */
    protected function hasValidImageExtension(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png']);
    }

    /**
     * Adiciona extensão .jpg à URL para compatibilidade com W-API
     * A W-API exige que URLs de imagem terminem com .jpg, .jpeg ou .png
     */
    protected function normalizeImageUrl(string $url): string
    {
        // Se já tem extensão válida, retorna a URL original
        if ($this->hasValidImageExtension($url)) {
            return $url;
        }

        // Adicionar .jpg antes dos query params (para URLs S3 do Bling)
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';

        // Adiciona .jpg ao final do path
        $newPath = $path . '.jpg';

        // Reconstrói a URL
        $newUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $newUrl .= $newPath;

        if (!empty($parsed['query'])) {
            $newUrl .= '?' . $parsed['query'];
        }

        return $newUrl;
    }

    /**
     * Envia imagem com legenda
     */
    public function sendImage(string $phone, string $imageUrl, string $caption = '', int $delay = 5): array
    {
        try {
            // Validar URL
            if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
                Log::warning('WApiService: URL de imagem inválida', ['url' => $imageUrl]);
                return [
                    'success' => false,
                    'error' => 'URL de imagem inválida',
                ];
            }

            // Normalizar URL para W-API (exige extensão .jpg/.png/.jpeg)
            $finalUrl = $this->normalizeImageUrl($imageUrl);

            // Adicionar parâmetro anti-cache para evitar que W-API sirva imagem errada
            $separator = strpos($finalUrl, '?') !== false ? '&' : '?';
            $finalUrl .= $separator . 't=' . time() . rand(1000, 9999);

            Log::info('WApiService: Enviando imagem', [
                'phone' => $phone,
                'url_original' => $imageUrl,
                'url_final' => $finalUrl,
                'caption' => $caption,
            ]);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/message/send-image?instanceId={$this->instanceId}", [
                    'phone' => $phone,
                    'image' => $finalUrl,
                    'caption' => $caption,
                    'delayMessage' => $delay,
                    'disableTestMsg' => true,
                ]);

            Log::info('WApiService: Resposta envio imagem', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'messageId' => $response->json('messageId'),
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('WApiService: Erro ao enviar imagem', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envia pedido completo para o grupo do WhatsApp
     * Formato: Título → Imagem 1 com legenda → Imagem 2 com legenda → ...
     */
    public function sendOrderToGroup(array $orderData): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'W-API não configurada. Verifique WAPI_TOKEN, WAPI_INSTANCE_ID e WAPI_GROUP_ID no .env',
            ];
        }

        try {
            $orderNumber = $orderData['order_number'] ?? 'N/A';
            $customerName = $orderData['customer_name'] ?? 'Cliente';
            $products = $orderData['products'] ?? [];
            $observations = $orderData['observations'] ?? '';
            $orderDate = $orderData['order_date'] ?? now()->format('d/m/Y');

            Log::info('WApiService: Iniciando envio de pedido para grupo', [
                'order' => $orderNumber,
                'customer' => $customerName,
                'products_count' => count($products),
                'group_id' => $this->groupId,
            ]);

            // 1. Enviar título do pedido (cabeçalho simples)
            $header = "*📦 Pedido #{$orderNumber}*\n";
            $header .= "{$customerName}\n\n";
            $header .= "{$orderDate}";

            if (!empty($observations)) {
                $header .= "\n\n*Obs:* {$observations}";
            }

            $textResponse = $this->sendText($this->groupId, $header);

            if (!$textResponse['success']) {
                Log::error('WApiService: Falha ao enviar título do pedido', $textResponse);
                return [
                    'success' => false,
                    'error' => 'Falha ao enviar título: ' . ($textResponse['error'] ?? 'Erro desconhecido'),
                ];
            }

            // Delay após o título (aumentado para evitar sobreposição)
            sleep(3); // 3 segundos

            // 2. Enviar cada produto com imagem e legenda
            $imagesSent = 0;
            $totalImages = 0;
            $imageErrors = [];
            $imageIndex = 0;

            foreach ($products as $product) {
                $imageUrl = $product['image'] ?? null;
                $description = $product['description'] ?? 'Item';
                $quantity = $product['quantity'] ?? 1;

                // Criar legenda no formato: Nome do produto + Qtd
                $caption = "{$description}\nQtd: {$quantity}";

                if (!empty($imageUrl) && $imageUrl !== 'INSIRA UMA FOTO' && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $totalImages++;

                    // Delay incremental para garantir ordem (máx 15s permitido pela API)
                    $delay = min(5 + ($imageIndex * 2), 15);
                    $imageResponse = $this->sendImage($this->groupId, $imageUrl, $caption, $delay);

                    if ($imageResponse['success']) {
                        $imagesSent++;
                    } else {
                        $imageErrors[] = [
                            'product' => $description,
                            'error' => $imageResponse['error'] ?? 'Erro desconhecido',
                        ];
                        Log::warning('WApiService: Falha ao enviar imagem', [
                            'product' => $description,
                            'error' => $imageResponse['error'] ?? 'Unknown error',
                        ]);
                    }

                    $imageIndex++;

                    // Delay entre envios para evitar rate limit e garantir ordem
                    sleep(4); // 4 segundos entre cada requisição
                }
            }

            Log::info('WApiService: Envio de pedido concluído', [
                'order' => $orderNumber,
                'images_sent' => $imagesSent,
                'total_images' => $totalImages,
            ]);

            return [
                'success' => true,
                'message' => 'Pedido enviado com sucesso!',
                'details' => [
                    'text_sent' => true,
                    'images_sent' => $imagesSent,
                    'total_images' => $totalImages,
                    'image_errors' => $imageErrors,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('WApiService: Erro ao enviar pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Busca grupos disponíveis
     */
    public function getGroups(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                ])
                ->get("{$this->baseUrl}/group/get-all-groups?instanceId={$this->instanceId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'groups' => $response->json('groups', []),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('WApiService: Erro ao buscar grupos', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
