<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    /**
     * Função: index
     * Descrição: Lista pedidos com filtros de status e número.
     * Parâmetros:
     *   - request (Request): Requisição HTTP
     * Retorno:
     *   - View: Lista de pedidos
     */
    public function index(Request $request)
    {     
        $query = Pedido::with('itens');

        // Filtro por status
        if ($request->has('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        // Filtro por número (a partir de)
        if ($request->has('numero_inicial') && $request->numero_inicial) {
            $query->where('numero', '>=', $request->numero_inicial);
        }

        // Filtro por período
        if ($request->has('data_inicial') && $request->data_inicial) {
            $query->whereDate('data_pedido', '>=', $request->data_inicial);
        }

        if ($request->has('data_final') && $request->data_final) {
            $query->whereDate('data_pedido', '<=', $request->data_final);
        }

        $pedidos = $query->orderBy('numero', 'asc')->paginate(12);
        // Contadores para os cards de status
        $contadores = [
            'aberto' => Pedido::aberto()->count(),
            'em_producao' => Pedido::emProducao()->count(),
            'finalizado' => Pedido::finalizado()->count(),
        ];
        
        return view('pedidos.index', compact('pedidos', 'contadores'));
    }

    /**
     * Função: show
     * Descrição: Exibe detalhes de um pedido específico.
     * Parâmetros:
     *   - pedido (Pedido): Model do pedido
     * Retorno:
     *   - View: Detalhes do pedido
     */
    public function show(Pedido $pedido)
    {
        $pedido->load('itens');
        return view('pedidos.show', compact('pedido'));
    }

    /**
     * Função: alterarStatus
     * Descrição: Altera o status do pedido via AJAX.
     * Parâmetros:
     *   - request (Request): Requisição com novo status
     *   - pedido (Pedido): Model do pedido
     * Retorno:
     *   - JsonResponse: Confirmação da alteração
     */
    public function alterarStatus(Request $request, Pedido $pedido): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:aberto,em_producao,finalizado'
        ]);

        try {
            $statusAnterior = $pedido->status;
            
            // Usar os métodos específicos do model para registrar as datas
            if ($request->status === 'em_producao' && $statusAnterior !== 'em_producao') {
                $pedido->setEmProducao();
            } elseif ($request->status === 'finalizado' && $statusAnterior !== 'finalizado') {
                $pedido->setFinalizado();
            } else {
                $pedido->status = $request->status;
                $pedido->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso',
                'novo_status' => $pedido->status,
                'data_producao' => $pedido->data_producao?->format('d/m/Y H:i'),
                'data_finalizacao' => $pedido->data_finalizacao?->format('d/m/Y H:i')
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao alterar status do pedido', [
                'pedido_id' => $pedido->id,
                'erro' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status'
            ], 500);
        }
    }

    /**
     * Função: atualizarImagem
     * Descrição: Atualiza a imagem personalizada de um item do pedido.
     * Parâmetros:
     *   - request (Request): Requisição com nova imagem
     *   - pedido (Pedido): Model do pedido
     *   - item (PedidoItem): Model do item
     * Retorno:
     *   - JsonResponse: URL da nova imagem
     */
    public function atualizarImagem(Request $request, Pedido $pedido, PedidoItem $item): JsonResponse
    {
        // Desabilitar qualquer saída de erro/aviso
        error_reporting(0);
        
        $request->validate([
            'imagem' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        // Verificar se o item pertence ao pedido
        if ($item->pedido_id !== $pedido->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item não pertence a este pedido'
            ], 403);
        }

        try {
            // Remover imagem anterior se existir
            if ($item->imagem_personalizada && Storage::exists($item->imagem_personalizada)) {
                Storage::delete($item->imagem_personalizada);
            }

            // Salvar nova imagem
            $path = $request->file('imagem')->store('pedidos/imagens', 'public');
            $item->imagem_personalizada = $path;
            $item->save();

            // Limpar qualquer output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagem atualizada com sucesso',
                'url' => '/serve-image.php?path=' . $path
            ]);

        } catch (\Exception $e) {
            // Limpar qualquer output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            Log::error('Erro ao atualizar imagem', [
                'item_id' => $item->id,
                'erro' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar imagem'
            ], 500);
        }
    }

    /**
     * Função: removerImagemPersonalizada
     * Descrição: Remove a imagem personalizada, voltando a usar a original.
     * Parâmetros:
     *   - pedido (Pedido): Model do pedido
     *   - item (PedidoItem): Model do item
     * Retorno:
     *   - JsonResponse: Confirmação da remoção
     */
    public function removerImagemPersonalizada(Pedido $pedido, PedidoItem $item): JsonResponse
    {
        if ($item->pedido_id !== $pedido->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item não pertence a este pedido'
            ], 403);
        }

        try {
            if ($item->imagem_personalizada) {
                if (Storage::exists($item->imagem_personalizada)) {
                    Storage::delete($item->imagem_personalizada);
                }
                $item->imagem_personalizada = null;
                $item->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagem personalizada removida',
                'url_original' => $item->imagem_original
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover imagem'
            ], 500);
        }
    }

    /**
     * Função: destroy
     * Descrição: Exclui um pedido e todos os seus itens.
     * Parâmetros:
     *   - pedido (Pedido): Model do pedido
     * Retorno:
     *   - JsonResponse: Confirmação da exclusão
     */
    public function destroy(Pedido $pedido): JsonResponse
    {
        try {
            // Remover imagens personalizadas dos itens
            foreach ($pedido->itens as $item) {
                if ($item->imagem_personalizada && Storage::exists($item->imagem_personalizada)) {
                    Storage::delete($item->imagem_personalizada);
                }
            }

            // Deletar itens e pedido (cascade)
            $numero = $pedido->numero;
            $pedido->delete();

            Log::info('Pedido excluído', [
                'pedido_id' => $pedido->id,
                'numero' => $numero
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pedido #{$numero} excluído com sucesso"
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir pedido', [
                'pedido_id' => $pedido->id,
                'erro' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir pedido'
            ], 500);
        }
    }

    /**
     * Função: destroyMultiple
     * Descrição: Exclui múltiplos pedidos de uma vez.
     * Parâmetros:
     *   - request (Request): Requisição com array de IDs
     * Retorno:
     *   - JsonResponse: Resumo das exclusões
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'pedidos' => 'required|array',
            'pedidos.*' => 'exists:pedidos,id'
        ]);

        try {
            $pedidos = Pedido::whereIn('id', $request->pedidos)->get();
            $excluidos = 0;
            $erros = 0;

            foreach ($pedidos as $pedido) {
                try {
                    // Remover imagens personalizadas
                    foreach ($pedido->itens as $item) {
                        if ($item->imagem_personalizada && Storage::exists($item->imagem_personalizada)) {
                            Storage::delete($item->imagem_personalizada);
                        }
                    }

                    $pedido->delete();
                    $excluidos++;

                } catch (\Exception $e) {
                    Log::error('Erro ao excluir pedido em lote', [
                        'pedido_id' => $pedido->id,
                        'erro' => $e->getMessage()
                    ]);
                    $erros++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$excluidos} pedido(s) excluído(s) com sucesso" . ($erros > 0 ? ", {$erros} erro(s)" : ""),
                'excluidos' => $excluidos,
                'erros' => $erros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir múltiplos pedidos', [
                'erro' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir pedidos'
            ], 500);
        }
    }
}