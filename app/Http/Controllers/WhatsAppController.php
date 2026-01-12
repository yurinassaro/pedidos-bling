<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\WApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhatsAppController extends Controller
{
    protected WApiService $wApiService;

    public function __construct(WApiService $wApiService)
    {
        $this->wApiService = $wApiService;
    }

    /**
     * Envia um pedido específico para o grupo do WhatsApp
     */
    public function sendOrder(Request $request, Pedido $pedido): JsonResponse
    {
        try {
            // Anti-spam: verificar se não foi enviado recentemente
            $lockKey = "whatsapp_lock_{$pedido->id}";
            if (Cache::has($lockKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aguarde 30 segundos antes de reenviar'
                ], 429);
            }

            // Bloquear por 30 segundos
            Cache::put($lockKey, true, 30);

            // Carregar itens do pedido
            $pedido->load('itens');

            Log::info('WhatsAppController: Iniciando envio de pedido', [
                'pedido_id' => $pedido->id,
                'numero' => $pedido->numero,
                'cliente' => $pedido->cliente_nome,
                'itens_count' => $pedido->itens->count(),
            ]);

            // Preparar dados do pedido
            // Usar APP_URL para garantir URL pública (ngrok)
            $baseUrl = rtrim(config('app.url'), '/');

            $products = [];
            foreach ($pedido->itens as $item) {
                // Prioridade: imagem_personalizada > imagem_local > imagem_original
                if ($item->imagem_personalizada) {
                    $imageUrl = $baseUrl . '/storage/' . $item->imagem_personalizada;
                } elseif ($item->imagem_local) {
                    $imageUrl = $baseUrl . '/storage/' . $item->imagem_local;
                } else {
                    $imageUrl = $item->imagem_original;
                }

                $products[] = [
                    'description' => $item->descricao,
                    'quantity' => number_format($item->quantidade, 0),
                    'image' => $imageUrl,
                ];
            }

            $orderData = [
                'order_number' => $pedido->numero,
                'customer_name' => $pedido->cliente_nome,
                'order_date' => $pedido->data_pedido ? $pedido->data_pedido->format('d/m/Y') : now()->format('d/m/Y'),
                'observations' => $pedido->observacoes_internas,
                'products' => $products,
            ];

            // Enviar via W-API
            $result = $this->wApiService->sendOrderToGroup($orderData);

            if ($result['success']) {
                // Marcar como enviado
                $pedido->marcarEnviadoWhatsApp();

                Log::info('WhatsAppController: Pedido enviado com sucesso', [
                    'pedido_id' => $pedido->id,
                    'numero' => $pedido->numero,
                    'details' => $result['details'] ?? [],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido enviado para o WhatsApp com sucesso!',
                    'details' => $result['details'] ?? [],
                    'enviado_em' => $pedido->data_envio_whatsapp?->format('d/m/Y H:i'),
                ]);
            }

            Log::error('WhatsAppController: Falha ao enviar pedido', [
                'pedido_id' => $pedido->id,
                'error' => $result['error'] ?? 'Erro desconhecido',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar pedido para WhatsApp',
                'error' => $result['error'] ?? 'Erro desconhecido',
            ], 500);

        } catch (\Exception $e) {
            Log::error('WhatsAppController: Exceção ao enviar pedido', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifica o status da conexão com a W-API
     */
    public function checkStatus(): JsonResponse
    {
        try {
            if (!$this->wApiService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'configured' => false,
                    'message' => 'W-API não configurada. Configure WAPI_TOKEN, WAPI_INSTANCE_ID e WAPI_GROUP_ID no .env',
                ]);
            }

            $status = $this->wApiService->getInstanceStatus();

            return response()->json([
                'success' => $status['success'],
                'configured' => true,
                'connected' => $status['connected'] ?? false,
                'data' => $status['data'] ?? null,
                'error' => $status['error'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista os grupos disponíveis
     */
    public function listGroups(): JsonResponse
    {
        try {
            $result = $this->wApiService->getGroups();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar grupos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
