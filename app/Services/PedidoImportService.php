<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
                // Se existe mas não tem itens, atualizar!
                if ($pedidoExistente->itens()->count() === 0 && !empty($pedidoBling['itens'])) {
                    Log::info("Pedido {$pedidoBling['numero']} existe mas sem itens - atualizando...");
                    return $this->atualizarPedidoSemItens($pedidoExistente, $pedidoBling);
                }

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

                    // Baixar e salvar a imagem localmente
                    $imagemLocal = null;
                    if ($imagemUrl) {
                        $imagemLocal = $this->baixarImagemProduto($imagemUrl, $pedido->numero, $index);
                    }

                    PedidoItem::create([
                        'pedido_id' => $pedido->id,
                        'bling_produto_id' => $itemBling['produto']['id'] ?? null,
                        'descricao' => $itemBling['descricao'] ?? 'Produto sem descrição',
                        'quantidade' => $itemBling['quantidade'] ?? 1,
                        'imagem_original' => $imagemUrl,
                        'imagem_local' => $imagemLocal,
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
     * Função: atualizarPedidoSemItens
     * Descrição: Atualiza um pedido existente que não tem itens.
     */
    protected function atualizarPedidoSemItens(Pedido $pedido, array $pedidoBling): array
    {
        try {
            DB::beginTransaction();

            // Atualizar dados do pedido
            $pedido->update([
                'observacoes_internas' => $pedidoBling['observacoesInternas'] ?? $pedido->observacoes_internas,
            ]);

            // Importar os itens
            if (isset($pedidoBling['itens']) && is_array($pedidoBling['itens'])) {
                foreach ($pedidoBling['itens'] as $index => $itemBling) {
                    // Processar a URL da imagem
                    $imagemUrl = null;
                    if (isset($itemBling['imagem'])) {
                        if (is_string($itemBling['imagem'])) {
                            $imagemUrl = $itemBling['imagem'];
                        } elseif (is_array($itemBling['imagem']) && !empty($itemBling['imagem'])) {
                            $imagemUrl = $itemBling['imagem'][0];
                        }
                    }

                    // Baixar e salvar a imagem localmente
                    $imagemLocal = null;
                    if ($imagemUrl) {
                        $imagemLocal = $this->baixarImagemProduto($imagemUrl, $pedido->numero, $index);
                    }

                    PedidoItem::create([
                        'pedido_id' => $pedido->id,
                        'bling_produto_id' => $itemBling['produto']['id'] ?? null,
                        'descricao' => $itemBling['descricao'] ?? 'Produto sem descrição',
                        'quantidade' => $itemBling['quantidade'] ?? 1,
                        'imagem_original' => $imagemUrl,
                        'imagem_local' => $imagemLocal,
                        'ordem' => $index
                    ]);
                }
            }

            DB::commit();

            Log::info("Pedido {$pedido->numero} atualizado com " . count($pedidoBling['itens'] ?? []) . " itens");

            return [
                'status' => 'sucesso',
                'numero' => $pedido->numero,
                'mensagem' => 'Pedido atualizado com itens'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erro ao atualizar pedido sem itens', [
                'numero' => $pedido->numero,
                'erro' => $e->getMessage()
            ]);

            return [
                'status' => 'erro',
                'numero' => $pedido->numero,
                'mensagem' => 'Erro ao atualizar: ' . $e->getMessage()
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
            'ignorados_antigos' => 0,
            'detalhes' => []
        ];

        // Data limite: 30 dias atras
        $dataLimite = Carbon::now()->subDays(30)->startOfDay();

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
                // Verificar se o pedido tem mais de 30 dias
                $dataPedido = isset($pedidoBling['data']) ? Carbon::parse($pedidoBling['data']) : Carbon::now();

                if ($dataPedido->lt($dataLimite)) {
                    $resultado['ignorados_antigos']++;
                    $resultado['detalhes'][] = [
                        'status' => 'ignorado',
                        'numero' => $pedidoBling['numero'] ?? 'N/A',
                        'mensagem' => 'Pedido com mais de 30 dias - ignorado'
                    ];
                    continue;
                }

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
            // Usar método RÁPIDO para verificação (sem detalhes/imagens)
            return $this->blingService->getOrdersByNumberRangeFast($numeroInicial, $numeroFinal);
        } catch (\Exception $e) {
            Log::error('Erro ao listar pedidos por intervalo', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Função: listarPedidosPorIntervaloCompleto
     * Descrição: Lista pedidos com TODOS os detalhes (mais lento).
     * Usar apenas quando precisar importar.
     */
    public function listarPedidosPorIntervaloCompleto(int $numeroInicial, int $numeroFinal, bool $buscarImagens = true): array
    {
        try {
            return $this->blingService->getOrdersByNumberRange($numeroInicial, $numeroFinal, $buscarImagens);
        } catch (\Exception $e) {
            Log::error('Erro ao listar pedidos completos por intervalo', [
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

    /**
     * Função: baixarImagemProduto
     * Descrição: Baixa a imagem do produto do Bling e salva localmente.
     * Parâmetros:
     *   - url (string): URL da imagem no S3 do Bling
     *   - numeroPedido (string): Número do pedido para organização
     *   - itemIndex (int): Índice do item no pedido
     * Retorno:
     *   - string|null: Caminho relativo da imagem salva ou null se falhar
     */
    protected function baixarImagemProduto(string $url, string $numeroPedido, int $itemIndex): ?string
    {
        try {
            Log::info('Baixando imagem do produto', [
                'pedido' => $numeroPedido,
                'item' => $itemIndex,
                'url' => substr($url, 0, 100) . '...'
            ]);

            // Baixar a imagem
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning('Falha ao baixar imagem', [
                    'pedido' => $numeroPedido,
                    'status' => $response->status()
                ]);
                return null;
            }

            // Determinar extensão pelo content-type
            $contentType = $response->header('Content-Type');
            $isWebp = str_contains($contentType, 'webp');
            $isPng = str_contains($contentType, 'png');
            $isGif = str_contains($contentType, 'gif');

            // Sempre salvar como JPG para melhor compatibilidade e tamanho menor
            $extension = 'jpg';

            // Criar nome do arquivo
            $filename = "pedidos/{$numeroPedido}/item_{$itemIndex}.{$extension}";

            // Converter e comprimir a imagem para JPG
            $imageData = $response->body();
            $originalSize = strlen($imageData);

            // Converter qualquer formato para JPG comprimido
            if ($isWebp || $isPng || $isGif || $originalSize > 500000) {
                $convertedData = $this->compressToJpg($imageData, 85);
                if ($convertedData) {
                    $newSize = strlen($convertedData);
                    Log::info('Imagem comprimida', [
                        'pedido' => $numeroPedido,
                        'original' => round($originalSize / 1024) . 'KB',
                        'comprimido' => round($newSize / 1024) . 'KB',
                        'reducao' => round((1 - $newSize / $originalSize) * 100) . '%'
                    ]);
                    $imageData = $convertedData;
                } else {
                    Log::warning('Falha ao comprimir imagem, salvando original', ['pedido' => $numeroPedido]);
                    // Se falhar a conversão, manter extensão original
                    if ($isPng) $extension = 'png';
                    elseif ($isWebp) $extension = 'webp';
                    elseif ($isGif) $extension = 'gif';
                    $filename = "pedidos/{$numeroPedido}/item_{$itemIndex}.{$extension}";
                    $imageData = $response->body();
                }
            }

            // Garantir que o diretório existe
            Storage::disk('public')->makeDirectory("pedidos/{$numeroPedido}");

            // Salvar a imagem
            Storage::disk('public')->put($filename, $imageData);

            Log::info('Imagem salva com sucesso', [
                'pedido' => $numeroPedido,
                'arquivo' => $filename
            ]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Erro ao baixar imagem do produto', [
                'pedido' => $numeroPedido,
                'erro' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Converte imagem webp para jpg
     */
    protected function convertWebpToJpg(string $webpData): ?string
    {
        return $this->compressToJpg($webpData, 90);
    }

    /**
     * Comprime e converte qualquer imagem para JPG
     * Redimensiona se for maior que 1200px
     */
    protected function compressToJpg(string $imageData, int $quality = 85): ?string
    {
        try {
            // Criar imagem a partir dos dados
            $image = @imagecreatefromstring($imageData);

            if (!$image) {
                return null;
            }

            // Obter dimensões originais
            $width = imagesx($image);
            $height = imagesy($image);

            // Redimensionar se for muito grande (max 1200px no maior lado)
            $maxSize = 1200;
            if ($width > $maxSize || $height > $maxSize) {
                if ($width > $height) {
                    $newWidth = $maxSize;
                    $newHeight = (int) ($height * ($maxSize / $width));
                } else {
                    $newHeight = $maxSize;
                    $newWidth = (int) ($width * ($maxSize / $height));
                }

                // Criar nova imagem redimensionada
                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // Preservar transparência convertendo para branco
                $white = imagecolorallocate($resized, 255, 255, 255);
                imagefill($resized, 0, 0, $white);

                // Redimensionar com alta qualidade
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                // Liberar imagem original
                imagedestroy($image);
                $image = $resized;
            } else {
                // Se não redimensionar, ainda precisa tratar transparência para PNG
                $newImage = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($newImage, 255, 255, 255);
                imagefill($newImage, 0, 0, $white);
                imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
                imagedestroy($image);
                $image = $newImage;
            }

            // Criar buffer de saída
            ob_start();
            imagejpeg($image, null, $quality);
            $jpgData = ob_get_clean();

            // Liberar memória
            imagedestroy($image);

            return $jpgData;
        } catch (\Exception $e) {
            Log::warning('Erro ao comprimir imagem', ['erro' => $e->getMessage()]);
            return null;
        }
    }
}