<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PedidoImportService
{
    protected BlingService $blingService;

    public function __construct(BlingService $blingService)
    {
        $this->blingService = $blingService;
    }

    /**
     * Função: importarPedidosPorPeriodo
     * Descrição: Importa pedidos do Bling para o banco de dados local em um período específico.
     * Parâmetros:
     *   - startDate (string): Data inicial no formato Y-m-d
     *   - endDate (string): Data final no formato Y-m-d
     * Retorno:
     *   - array: Resumo da importação com sucesso, erros e pedidos já existentes
     */
    public function importarPedidosPorPeriodo(string $startDate, string $endDate): array
    {
        $resultado = [
            'sucesso' => 0,
            'erros' => 0,
            'ja_existentes' => 0,
            'detalhes' => []
        ];

        try {
            // Buscar pedidos do Bling
            $pedidosBling = $this->blingService->getOrdersWithProductImages($startDate, $endDate);
            
            // Se o retorno tem a estrutura com 'orders' e 'missingSequence'
            if (isset($pedidosBling['orders'])) {
                $pedidosBling = $pedidosBling['orders'];
            }

            foreach ($pedidosBling as $pedidoBling) {
                $resultadoImportacao = $this->importarPedido($pedidoBling);
                
                if ($resultadoImportacao['status'] === 'sucesso') {
                    $resultado['sucesso']++;
                } elseif ($resultadoImportacao['status'] === 'existente') {
                    $resultado['ja_existentes']++;
                } else {
                    $resultado['erros']++;
                }

                $resultado['detalhes'][] = $resultadoImportacao;
            }

        } catch (\Exception $e) {
            Log::error('Erro ao importar pedidos', [
                'erro' => $e->getMessage(),
                'periodo' => "$startDate a $endDate"
            ]);
            throw $e;
        }

        return $resultado;
    }

    /**
     * Função: importarPedido
     * Descrição: Importa um pedido individual do Bling.
     * Parâmetros:
     *   - pedidoBling (array): Dados do pedido vindo do Bling
     * Retorno:
     *   - array: Status da importação e detalhes
     */
    protected function importarPedido(array $pedidoBling): array
    {
        try {
            // Log detalhado do que está chegando
            Log::info('Importando pedido', [
                'numero' => $pedidoBling['numero'] ?? 'SEM NUMERO',
                'id' => $pedidoBling['id'] ?? 'SEM ID',
                'tem_itens' => isset($pedidoBling['itens']),
                'quantidade_itens' => count($pedidoBling['itens'] ?? [])
            ]);

            // Verificar se o pedido já existe
            $pedidoExistente = Pedido::where('bling_id', $pedidoBling['id'])->first();
            
            if ($pedidoExistente) {
                return [
                    'status' => 'existente',
                    'numero' => $pedidoBling['numero'],
                    'mensagem' => 'Pedido já importado anteriormente'
                ];
            }

            DB::beginTransaction();

            // Verificar campos obrigatórios
            if (!isset($pedidoBling['id']) || !isset($pedidoBling['numero'])) {
                throw new \Exception('Pedido sem ID ou número');
            }

            // Criar o pedido
            $pedido = Pedido::create([
                'bling_id' => $pedidoBling['id'],
                'numero' => $pedidoBling['numero'],
                'status' => 'aberto',
                'cliente_nome' => $pedidoBling['contato']['nome'] ?? 'Cliente não informado',
                'cliente_telefone' => $pedidoBling['contato']['celular'] ?? $pedidoBling['contato']['telefone'] ?? null,
                'observacoes_internas' => $pedidoBling['observacoesInternas'] ?? null,
                'data_pedido' => Carbon::parse($pedidoBling['data']),
                'importado' => true,
                'data_importacao' => now()
            ]);

            // Importar os itens
            if (isset($pedidoBling['itens']) && is_array($pedidoBling['itens'])) {
                foreach ($pedidoBling['itens'] as $index => $itemBling) {
                    // Processar a URL da imagem
                    $imagemUrl = null;
                    if (isset($itemBling['imagem'])) {
                        // Se for uma string, usa direto
                        if (is_string($itemBling['imagem'])) {
                            $imagemUrl = $itemBling['imagem'];
                        }
                        // Se for array, pega a primeira imagem
                        elseif (is_array($itemBling['imagem']) && !empty($itemBling['imagem'])) {
                            $imagemUrl = $itemBling['imagem'][0];
                        }
                    }

                    PedidoItem::create([
                        'pedido_id' => $pedido->id,
                        'bling_produto_id' => $itemBling['produto']['id'] ?? null,
                        'descricao' => $itemBling['descricao'] ?? 'Produto sem descrição',
                        'quantidade' => $itemBling['quantidade'] ?? 1,
                        'imagem_original' => $imagemUrl,
                        'ordem' => $index
                    ]);
                }
            }

            DB::commit();

            return [
                'status' => 'sucesso',
                'numero' => $pedido->numero,
                'mensagem' => 'Pedido importado com sucesso'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao importar pedido', [
                'numero' => $pedidoBling['numero'] ?? 'N/A',
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile()
            ]);

            return [
                'status' => 'erro',
                'numero' => $pedidoBling['numero'] ?? 'N/A',
                'mensagem' => 'Erro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Função: verificarSequenciaPedidos
     * Descrição: Verifica gaps na sequência de números de pedidos.
     * Parâmetros:
     *   - startDate (string): Data inicial no formato Y-m-d
     *   - endDate (string): Data final no formato Y-m-d
     * Retorno:
     *   - array: Números faltantes e resumo
     */
    public function verificarSequenciaPedidos(string $startDate, string $endDate): array
    {
        $pedidos = Pedido::whereBetween('data_pedido', [$startDate, $endDate])
                        ->orderBy('numero')
                        ->pluck('numero')
                        ->toArray();

        if (empty($pedidos)) {
            return [
                'faltantes' => [],
                'primeiro' => null,
                'ultimo' => null,
                'total' => 0
            ];
        }

        $primeiro = min($pedidos);
        $ultimo = max($pedidos);
        $sequenciaCompleta = range($primeiro, $ultimo);
        $faltantes = array_diff($sequenciaCompleta, $pedidos);

        return [
            'faltantes' => array_values($faltantes),
            'primeiro' => $primeiro,
            'ultimo' => $ultimo,
            'total' => count($pedidos),
            'esperado' => count($sequenciaCompleta),
            'gaps' => count($faltantes)
        ];
    }

    /**
     * Função: importarPedidosPorIntervalo
     * Descrição: Importa pedidos por intervalo de números.
     * Parâmetros:
     *   - numeroInicial (int): Número inicial do intervalo
     *   - numeroFinal (int): Número final do intervalo
     * Retorno:
     *   - array: Resumo da importação
     */
    public function importarPedidosPorIntervalo(int $numeroInicial, int $numeroFinal): array
    {
        $resultado = [
            'sucesso' => 0,
            'erros' => 0,
            'ja_existentes' => 0,
            'nao_encontrados' => 0,
            'detalhes' => []
        ];

        try {
            // Passar true para buscar imagens
            $dadosPedidos = $this->blingService->getOrdersByNumberRange($numeroInicial, $numeroFinal, true);
            
            // Log para debug
            Log::info('Dados retornados do Bling', [
                'total_pedidos' => count($dadosPedidos['orders'] ?? [])
            ]);
            
            $pedidosBling = $dadosPedidos['orders'];
            
            // Registrar números não encontrados
            foreach ($dadosPedidos['missingSequence'] as $numeroFaltante) {
                $resultado['nao_encontrados']++;
                $resultado['detalhes'][] = [
                    'status' => 'nao_encontrado',
                    'numero' => $numeroFaltante,
                    'mensagem' => 'Pedido não encontrado no Bling'
                ];
            }

            // Importar pedidos encontrados
            foreach ($pedidosBling as $pedidoBling) {
                $resultadoImportacao = $this->importarPedido($pedidoBling);
                
                if ($resultadoImportacao['status'] === 'sucesso') {
                    $resultado['sucesso']++;
                } elseif ($resultadoImportacao['status'] === 'existente') {
                    $resultado['ja_existentes']++;
                } else {
                    $resultado['erros']++;
                }

                $resultado['detalhes'][] = $resultadoImportacao;
            }

            // Adicionar resumo
            $resultado['resumo'] = [
                'intervalo' => "$numeroInicial - $numeroFinal",
                'total_esperado' => $dadosPedidos['total_esperado'],
                'total_encontrado' => $dadosPedidos['total_encontrado'],
                'total_faltante' => $dadosPedidos['total_faltante']
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao importar pedidos por intervalo', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }

        return $resultado;
    }

    /**
     * Função: verificarIntervalosNaoImportados
     * Descrição: Verifica quais intervalos de pedidos ainda não foram importados.
     * Parâmetros:
     *   - numeroInicial (int): Número inicial para verificar
     *   - numeroFinal (int): Número final para verificar
     * Retorno:
     *   - array: Intervalos sugeridos para importação
     */
    public function verificarIntervalosNaoImportados(int $numeroInicial, int $numeroFinal): array
    {
        // Buscar números já importados no banco
        $numerosImportados = Pedido::whereBetween('numero', [$numeroInicial, $numeroFinal])
                                ->pluck('numero')
                                ->toArray();
        
        // Se não há nenhum pedido importado, retornar o intervalo completo
        if (empty($numerosImportados)) {
            return [[
                'inicio' => $numeroInicial,
                'fim' => $numeroFinal,
                'quantidade' => $numeroFinal - $numeroInicial + 1
            ]];
        }
        
        $intervalos = [];
        $inicio = null;
        
        // Percorrer todos os números do intervalo
        for ($i = $numeroInicial; $i <= $numeroFinal; $i++) {
            if (!in_array($i, $numerosImportados)) {
                // Se é o início de um novo intervalo não importado
                if ($inicio === null) {
                    $inicio = $i;
                }
            } else {
                // Se encontrou um número já importado e havia um intervalo aberto
                if ($inicio !== null) {
                    $intervalos[] = [
                        'inicio' => $inicio,
                        'fim' => $i - 1,
                        'quantidade' => ($i - 1) - $inicio + 1
                    ];
                    $inicio = null;
                }
            }
        }
        
        // Adicionar último intervalo se necessário
        if ($inicio !== null) {
            $intervalos[] = [
                'inicio' => $inicio,
                'fim' => $numeroFinal,
                'quantidade' => $numeroFinal - $inicio + 1
            ];
        }
        
        // Se não há intervalos não importados mas a contagem não bate
        // (pode haver gaps nos importados)
        if (empty($intervalos) && count($numerosImportados) < ($numeroFinal - $numeroInicial + 1)) {
            // Criar intervalos para os números faltantes
            for ($i = $numeroInicial; $i <= $numeroFinal; $i++) {
                if (!in_array($i, $numerosImportados)) {
                    $intervalos[] = [
                        'inicio' => $i,
                        'fim' => $i,
                        'quantidade' => 1
                    ];
                }
            }
        }
        
        return $intervalos;
    }

    /**
     * Função: listarPedidosPorIntervalo
     * Descrição: Lista pedidos do Bling em um intervalo de números.
     * Parâmetros:
     *   - numeroInicial (int): Número inicial
     *   - numeroFinal (int): Número final
     * Retorno:
     *   - array: Dados dos pedidos do Bling
     */
    public function listarPedidosPorIntervalo(int $numeroInicial, int $numeroFinal): array
    {
        try {
            return $this->blingService->getOrdersByNumberRange($numeroInicial, $numeroFinal);
        } catch (\Exception $e) {
            Log::error('Erro ao listar pedidos por intervalo', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Função: listarPedidosNaoImportados
     * Descrição: Lista pedidos do Bling que ainda não foram importados (por data).
     * Parâmetros:
     *   - startDate (string): Data inicial no formato Y-m-d
     *   - endDate (string): Data final no formato Y-m-d
     * Retorno:
     *   - array: Lista de pedidos não importados
     */
    public function listarPedidosNaoImportados(string $startDate, string $endDate): array
    {
        try {
            // Buscar todos os pedidos do Bling no período
            $pedidosBling = $this->blingService->getOrdersWithProductImages($startDate, $endDate);
            
            if (isset($pedidosBling['orders'])) {
                $pedidosBling = $pedidosBling['orders'];
            }

            // Buscar IDs já importados
            $idsImportados = Pedido::pluck('bling_id')->toArray();

            // Filtrar apenas os não importados
            $naoImportados = array_filter($pedidosBling, function($pedido) use ($idsImportados) {
                return !in_array($pedido['id'], $idsImportados);
            });

            return array_values($naoImportados);

        } catch (\Exception $e) {
            Log::error('Erro ao listar pedidos não importados', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }
}