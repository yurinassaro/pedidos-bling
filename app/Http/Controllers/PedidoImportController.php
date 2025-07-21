<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\PedidoImportService;
use App\Services\BlingAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;  // 👈 Adicionar esta linha

class PedidoImportController extends Controller
{
    protected PedidoImportService $importService;
    protected BlingAuthService $authService;

    public function __construct(PedidoImportService $importService, BlingAuthService $authService)
    {
        $this->importService = $importService;
        $this->authService = $authService;
    }

    /**
     * Função: index
     * Descrição: Exibe a tela de importação com lista de pedidos.
     * Parâmetros:
     *   - request (Request): Requisição HTTP
     * Retorno:
     *   - View: Tela de importação
     */
    public function index(Request $request)
    {
        if (!$this->authService->hasValidToken()) {
            return redirect()->route('bling.auth');
        }

        $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        try {
            // Listar pedidos não importados
            $pedidosNaoImportados = $this->importService->listarPedidosNaoImportados($startDate, $endDate);
            
            // Verificar sequência
            $sequencia = $this->importService->verificarSequenciaPedidos($startDate, $endDate);

            return view('pedidos.importacao', compact(
                'pedidosNaoImportados',
                'sequencia',
                'startDate',
                'endDate'
            ))->with('info', 'A API do Bling tem limite de 100 pedidos por página. Para períodos grandes, use o comando artisan.');

        } catch (\Exception $e) {
            return view('pedidos.importacao', [
                'pedidosNaoImportados' => [],
                'sequencia' => ['faltantes' => [], 'total' => 0],
                'startDate' => $startDate,
                'endDate' => $endDate,
                'error' => 'Erro ao buscar pedidos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Função: importar
     * Descrição: Realiza a importação dos pedidos selecionados.
     * Parâmetros:
     *   - request (Request): Requisição HTTP com período
     * Retorno:
     *   - JsonResponse: Resultado da importação
     */
    public function importar(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        try {
            $resultado = $this->importService->importarPedidosPorPeriodo(
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => "Importação concluída: {$resultado['sucesso']} pedidos importados"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao importar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Função: verificarNaoImportados
     * Descrição: API para verificar pedidos não importados via AJAX.
     * Parâmetros:
     *   - request (Request): Requisição HTTP com período
     * Retorno:
     *   - JsonResponse: Lista de pedidos não importados
     */
    public function verificarNaoImportados(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        try {
            $naoImportados = $this->importService->listarPedidosNaoImportados(
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $naoImportados,
                'total' => count($naoImportados)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
 * Função: indexPorNumero
 * Descrição: Exibe tela de importação por número.
 * Parâmetros:
 *   - request (Request): Requisição HTTP
 * Retorno:
 *   - View: Tela de importação por número
 */
// Em PedidoImportController.php - método indexPorNumero
    public function indexPorNumero(Request $request)
    {
        if (!$this->authService->hasValidToken()) {
            return redirect()->route('bling.auth');
        }

        $numeroInicial = (int) $request->get('numero_inicial', 8000);
        $numeroFinal = (int) $request->get('numero_final', 8001);

        try {
            // IMPORTANTE: Usar listarPedidosPorIntervalo, NÃO listarPedidosNaoImportados
            $dadosPedidos = $this->importService->listarPedidosPorIntervalo($numeroInicial, $numeroFinal);
            
            Log::info('Pedidos buscados por número', [
                'intervalo_solicitado' => "$numeroInicial - $numeroFinal",
                'total_encontrado' => count($dadosPedidos['orders'] ?? []),
                'numeros_encontrados' => array_column($dadosPedidos['orders'] ?? [], 'numero')
            ]);
            
            // Buscar pedidos já importados no banco
            $pedidosImportados = Pedido::whereBetween('numero', [$numeroInicial, $numeroFinal])
                                    ->pluck('numero', 'bling_id')
                                    ->toArray();
            
            // Separar pedidos não importados
            $pedidosNaoImportados = [];
            foreach ($dadosPedidos['orders'] ?? [] as $pedido) {
                if (!isset($pedidosImportados[$pedido['id']])) {
                    $pedidosNaoImportados[] = $pedido;
                }
            }
            
            // Verificar intervalos não importados
            $intervalosNaoImportados = $this->importService->verificarIntervalosNaoImportados(
                $numeroInicial, 
                $numeroFinal
            );
            
            // Estatísticas
            $totalNaoImportado = count($pedidosNaoImportados);
            $totalImportado = count($pedidosImportados);

            return view('pedidos.importacao-numero', compact(
                'pedidosNaoImportados',
                'intervalosNaoImportados',
                'numeroInicial',
                'numeroFinal',
                'totalNaoImportado',
                'totalImportado'
            ));

        } catch (\Exception $e) {
            Log::error('Erro ao verificar intervalos por número', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('pedidos.importacao-numero', [
                'pedidosNaoImportados' => [],
                'intervalosNaoImportados' => [],
                'numeroInicial' => $numeroInicial,
                'numeroFinal' => $numeroFinal,
                'totalNaoImportado' => 0,
                'totalImportado' => 0,
                'error' => 'Erro ao verificar pedidos: ' . $e->getMessage()
            ]);
        }
    }

        /**
     * Função: importarPorNumero
     * Descrição: Importa pedidos por intervalo de números.
     * Parâmetros:
     *   - request (Request): Requisição com intervalo
     * Retorno:
     *   - JsonResponse: Resultado da importação
     */
    public function importarPorNumero(Request $request): JsonResponse
    {
        // Verificar se tem token válido
        if (!$this->authService->hasValidToken()) {
            return response()->json([
                'success' => false,
                'message' => 'Token do Bling expirado. Por favor, faça login novamente.'
            ], 401);
        }

        $request->validate([
            'numero_inicial' => 'required|integer|min:1',
            'numero_final' => 'required|integer|gte:numero_inicial'
        ]);

        try {
            Log::info('Iniciando importação por número', [
                'numero_inicial' => $request->numero_inicial,
                'numero_final' => $request->numero_final
            ]);

            $resultado = $this->importService->importarPedidosPorIntervalo(
                $request->numero_inicial,
                $request->numero_final
            );

            Log::info('Resultado da importação', $resultado);

            $mensagem = "Importação concluída: ";
            if ($resultado['sucesso'] > 0) {
                $mensagem .= "{$resultado['sucesso']} pedidos importados";
            }
            if ($resultado['nao_encontrados'] > 0) {
                $mensagem .= ", {$resultado['nao_encontrados']} não encontrados no Bling";
            }
            if ($resultado['ja_existentes'] > 0) {
                $mensagem .= ", {$resultado['ja_existentes']} já existiam";
            }
            if ($resultado['erros'] > 0) {
                $mensagem .= ", {$resultado['erros']} erros";
            }

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => $mensagem
            ]);

        } catch (\Exception $e) {
            Log::error('Erro na importação por número', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'intervalo' => "{$request->numero_inicial} - {$request->numero_final}"
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao importar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }
}