<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportService
{
    protected $blingService;

    public function __construct(BlingService $blingService)
    {
        $this->blingService = $blingService;
    }

    public function importOrders($startDate, $endDate)
    {
        try {
            // Busca as ordens no Bling
            $orders = $this->blingService->getOrdersWithProductImages($startDate, $endDate);
            $importedCount = 0;

            DB::beginTransaction();

            foreach ($orders as $orderData) {
                // Verifica se o pedido já existe
                $existingOrder = Pedido::where('bling_id', $orderData['id'])->first();
                if ($existingOrder) {
                    continue;
                }

                // Cria o pedido
                $pedido = Pedido::create([
                    'bling_id' => $orderData['id'],
                    'numero' => $orderData['numero'],
                    'cliente_nome' => $orderData['contato']['nome'],
                    'observacoes_internas' => $orderData['observacoesInternas'] ?? null,
                    'data_pedido' => $orderData['data'],
                    'situacao' => $orderData['situacao']['id'] ?? 0
                ]);

                // Cria os produtos associados
                foreach ($orderData['itens'] as $item) {
                    Produto::create([
                        'pedido_id' => $pedido->id,
                        'bling_produto_id' => $item['produto']['id'] ?? '',
                        'descricao' => $item['descricao'] ?? '',
                        'quantidade' => $item['quantidade'] ?? 0,
                        'imagem_url' => $item['imagem'] ?? null
                    ]);
                }

                $importedCount++;
            }

            DB::commit();

            // Retorna JSON com sucesso
            return response()->json([
                'status' => 'success',
                'message' => 'Pedidos importados com sucesso.',
                'imported_count' => $importedCount
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro na importação: ' . $e->getMessage());

            // Retorna JSON de erro
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao importar pedidos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}