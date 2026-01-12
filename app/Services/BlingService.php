<?php

namespace App\Services;

use App\Models\BlingToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlingService
{
    protected $baseUrl = 'https://www.bling.com.br/Api/v3';
    protected $clientId;
    protected $clientSecret;
    protected $redirectUrl;

    public function __construct()
    {
        $this->clientId = config('services.bling.client_id');
        $this->clientSecret = config('services.bling.client_secret');
        $this->redirectUrl = config('services.bling.redirect_url');
    }

    /**
     * Gera a URL para autorização no Bling
     */
    public function getAuthorizationUrl()
    {
        return "{$this->baseUrl}/oauth/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'state' => $this->generateState()
        ]);
    }

    /**
     * Obtém o token de acesso usando o código de autorização
     */
    
    public function getAccessToken($code)
    {
        $response = Http::post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUrl,
        ]);

        if ($response->failed()) {
            Log::error('Erro ao obter token de acesso', ['response' => $response->body()]);
            throw new \Exception('Erro ao obter token de acesso');
        }

        $tokenData = $response->json();

        // Salvar token no banco
        BlingToken::query()->delete(); // Remove tokens antigos
        BlingToken::create([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'], // Salvar o refresh token
            'expires_at' => now()->addSeconds($tokenData['expires_in']),
        ]);

        Log::info('Token atualizado com sucesso.');
        return true;
        //return $tokenData['access_token'];
    }

    /**
     * Verifica se existe um token válido
     */
    public function hasValidToken()
    {

        $token = BlingToken::latest()->first();

        if (!$token) {
            Log::info('Nenhum token encontrado no banco.');
            return false;
        }

        // Verifica validade do token com margem de 5 minutos
        return $token->expires_at && $token->expires_at->subMinutes(5)->isFuture();
    }


    /**
     * Atualiza o token de acesso usando o refresh token
     */
    public function refreshToken()
    {
        $token = BlingToken::latest()->first();

        if (!$token || !isset($token->refresh_token)) {
            Log::error('Nenhum refresh token disponível.');
            return false;
        }

        $response = Http::post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUrl,
        ]);

        if ($response->failed()) {
            Log::error('Erro ao renovar token', ['response' => $response->body()]);
            return false;
        }

        $tokenData = $response->json();

        BlingToken::query()->delete(); // Remove o token antigo
        BlingToken::create([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'expires_at' => now()->addSeconds($tokenData['expires_in']),
        ]);

        Log::info('Token atualizado com sucesso.');
        return true;
    }

      /**
     * Retorna o token de acesso atual
     */
    protected function getCurrentAccessToken()
    {
        if (!$this->hasValidToken()) {
            $this->refreshToken();
        }

        $token = BlingToken::latest()->first();

        if (!$token) {
            throw new \Exception('Token inválido ou expirado.');
        }

        return $token->access_token;
    }
    /**
     * Busca pedidos no Bling dentro de um intervalo de datas
     */
    // public function getOrders($startDate, $endDate)
    // {
    //     $accessToken = $this->getCurrentAccessToken();

    //     $response = Http::withToken($accessToken)
    //         ->get("{$this->baseUrl}/pedidos/vendas", [
    //             'filters' => "dataEmissao[{$startDate} TO {$endDate}] situacao[0]",
    //             'expand' => 'itens.produto' // Adiciona os detalhes do produto, incluindo imagens
    //         ]);
    //     if ($response->failed()) {
    //         Log::error('Erro ao buscar pedidos', ['response' => $response->body()]);
    //         throw new \Exception('Erro ao buscar pedidos');
    //     }

    //     return $response->json('data');
   // }

    public function getOrders($startDate, $endDate)
{
    $accessToken = $this->getCurrentAccessToken();

    $response = Http::withToken($accessToken)
        ->get("{$this->baseUrl}/pedidos/vendas", [
            'filters' => "dataEmissao[{$startDate} TO {$endDate}] situacao[0]",
            'expand' => 'itens.produto' // Expande os detalhes dos produtos nos itens
        ]);

    if ($response->failed()) {
        Log::error('Erro ao buscar pedidos', [
            'response' => $response->body(),
            'headers' => $response->headers(),
            'status' => $response->status()
        ]);
        throw new \Exception('Erro ao buscar pedidos');
    }

    Log::info('Resposta da API de Pedidos', [
        'body' => $response->json(),
        'url' => "{$this->baseUrl}/pedidos/vendas",
        'filters' => "dataEmissao[{$startDate} TO {$endDate}] situacao[0]",
        'expand' => 'itens.produto'
    ]);

    return $response->json('data');
}

/**
     * Faz uma requisição autenticada ao Bling
     */
    public function makeAuthenticatedRequest($endpoint, $method = 'GET', $data = [])
    {
        $accessToken = $this->getCurrentAccessToken(); // Garante que o token é válido antes da requisição

        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken"
        ])->$method("{$this->baseUrl}/$endpoint", $data);

        if ($response->failed()) {
            Log::error("Erro na requisição Bling: $endpoint", ['response' => $response->body()]);
            throw new \Exception("Erro ao acessar $endpoint");
        }

        return $response->json();
    }


// public function getOrdersWithProductImages($startDate, $endDate)
// {
//     $accessToken = $this->getCurrentAccessToken();

//     // Buscar pedidos
//     $response = Http::withToken($accessToken)
//         ->get("{$this->baseUrl}/pedidos/vendas", [
//             'filters' => "dataEmissao[{$startDate} TO {$endDate}] situacao[0]",
//             'expand' => 'itens.produto' // Expande os detalhes dos produtos nos itens
//         ]);

//     if ($response->failed()) {
//         Log::error('Erro ao buscar pedidos', ['response' => $response->body()]);
//         throw new \Exception('Erro ao buscar pedidos');
//     }

//     $orders = $response->json('data') ?? [];

//     $limit = 2; // Defina o limite de pedidos
//     $count = 0; // Inicialize o contador
   
//     // Adicionar imagens dos produtos
//     foreach ($orders as &$order) {
//         if ($count >= $limit) {
//             break; // Interrompe o loop ao atingir o limite
//         }

//         if ($order['id']) {
//             // Buscar detalhes do produto
//             $pedidoResponse = Http::withToken($accessToken)
//                 ->get("{$this->baseUrl}/pedidos/vendas/{$order['id']}", [                   
//                 'expand' => 'itens.produto' // Expande os detalhes dos produtos nos itens
//             ])->json('data');  
            
//             foreach($pedidoResponse['itens'] as $item){
//                 $produtoResponse = Http::withToken($accessToken)
//                 ->get("{$this->baseUrl}/produtos/{$item['produto']['id']}")->json('data');
                
//                 dd($produtoResponse['midia']['imagens']['internas'][0]['link'] ?? null);

//                 $order['produto']['imagem'] = $produtoResponse['midia']['imagens']['internas']['limk'] ?? null;
                
//                 // foreach($produtoResponse as &$produto){
//                 //     dd($produto);
//                 //     echo $produto['midia']['imagens'][0]['link']; 
//                 // }
//             }

//             dd($produtoResponse);
                
//             if ($pedidoResponse->ok()) {
                
                 

//                 $item['produto']['imagem'] = $productData['imagens'] ?? [];
//             } else {
//                 Log::error("Erro ao buscar produto {$order['id']}", [
//                     'response' => $productResponse->body()
//                 ]);
//                 $item['produto']['imagem'] = [];
//             }
//         }
//         $count++; // Incrementa o contador
//     }
//     dd("oi");
//     return $orders;
// }

    public function getOrdersWithProductImages($startDate, $endDate)
    {
        $accessToken = $this->getCurrentAccessToken();
        $allOrders = [];
        $page = 1;
        $hasMorePages = true;
        
        Log::info('Iniciando busca de pedidos', [
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        // Buscar todos os pedidos com paginação
        while ($hasMorePages) {
            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/pedidos/vendas", [
                    'pagina' => $page,
                    'limite' => 100, // Máximo permitido pela API
                    'criterio' => 'dataEmissao',
                    'filters' => "dataEmissao[{$startDate} TO {$endDate}] situacao[0]",
                ]);

            if ($response->failed()) {
                Log::error('Erro ao buscar pedidos', [
                    'page' => $page,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Erro ao buscar pedidos');
            }

            $responseData = $response->json();
            $orders = $responseData['data'] ?? [];
            
            Log::info("Página {$page} processada", [
                'quantidade' => count($orders),
                'total_ate_agora' => count($allOrders) + count($orders)
            ]);

            // Adicionar pedidos desta página ao array total
            $allOrders = array_merge($allOrders, $orders);

            // Verificar se há mais páginas
            $hasMorePages = !empty($orders) && count($orders) == 100;
            $page++;

            // Delay para evitar rate limit
            if ($hasMorePages) {
                usleep(200000); // 200ms entre requisições
            }
        }

        Log::info('Total de pedidos encontrados', [
            'total' => count($allOrders)
        ]);

        // Processar imagens (com limite para evitar timeout)
        $missingSequence = [];
        $count = 0;
        $maxToProcess = 200; // Limite para processar por vez

        foreach ($allOrders as &$order) {
            if ($count >= $maxToProcess) {
                Log::warning("Limite de processamento atingido: {$maxToProcess} pedidos");
                break;
            }

            if ($order['id']) {
                // Buscar detalhes do pedido com delay
                usleep(100000); // 100ms entre requisições
                
                $pedidoResponse = Http::withToken($accessToken)
                    ->get("{$this->baseUrl}/pedidos/vendas/{$order['id']}");

                if ($pedidoResponse->failed()) {
                    Log::error("Erro ao buscar detalhes do pedido {$order['id']}", [
                        'response' => $pedidoResponse->body()
                    ]);
                    continue;
                }

                $pedidoData = $pedidoResponse->json('data');
                
                // Processar itens do pedido
                foreach ($pedidoData['itens'] ?? [] as $itemIndex => $item) {
                    if (isset($item['produto']['id'])) {
                        $produtoId = $item['produto']['id'];
                        
                        // Buscar produto com delay
                        usleep(100000); // 100ms entre requisições
                        
                        $produtoResponse = Http::withToken($accessToken)
                            ->get("{$this->baseUrl}/produtos/{$produtoId}");
                        
                        if ($produtoResponse->ok()) {
                            $produtoData = $produtoResponse->json('data');
                            
                            $imagemLink = isset($produtoData['midia']['imagens']['internas'][0]['link'])
                                ? $produtoData['midia']['imagens']['internas'][0]['link']
                                : null;

                            $order['itens'][$itemIndex]['imagem'] = $imagemLink;
                            $order['itens'][$itemIndex]['descricao'] = $item['descricao'];
                            $order['itens'][$itemIndex]['quantidade'] = $item['quantidade'];
                        }
                    }
                }

                $order['observacoesInternas'] = $pedidoData['observacoesInternas'] ?? null;
            }
            
            $count++;
        }

        // Verificar sequência de números
        if (!empty($allOrders)) {
            $numeros = array_column($allOrders, 'numero');
            sort($numeros);
            
            $primeiro = $numeros[0];
            $ultimo = end($numeros);
            
            for ($i = $primeiro; $i <= $ultimo; $i++) {
                if (!in_array($i, $numeros)) {
                    $missingSequence[] = $i;
                }
            }
        }

        // Ordenar por número
        usort($allOrders, function ($a, $b) {
            return $a['numero'] <=> $b['numero'];
        });

        return [
            'orders' => $allOrders, 
            'missingSequence' => $missingSequence,
            'total_encontrados' => count($allOrders),
            'processados_com_imagem' => $count
        ];
    }


    public function getOrdersWithProductImagesBackup($startDate, $endDate)
    {
        // Garante que o token esteja atualizado antes da requisição
        if (!$this->hasValidToken()) {
            $this->refreshToken();
        }

        $accessToken = $this->getCurrentAccessToken();

        // Buscar pedidos
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/pedidos/vendas", [
                'filters' => "dataEmissao[{$startDate} TO {$endDate}] situacao[0]",
                'expand' => 'itens.produto' // Expande os detalhes dos produtos nos itens
            ]);

        if ($response->failed()) {
            Log::error('Erro ao buscar pedidos', ['response' => $response->body()]);
            throw new \Exception('Erro ao buscar pedidos');
        }

        $orders = $response->json('data') ?? [];
        //dd($orders);
        $limit = 0; // Defina o limite de pedidos
        $count = 0; // Inicialize o contador

        $allNumbers = array_map('intval', array_column($orders, 'numero')); // Converta os números para inteiros
        sort($allNumbers); // Ordene os números

        $missingSequence = [];
        $lastNumber = null;

        // Verifica a sequência diretamente nos números disponíveis
        foreach ($allNumbers as $currentNumber) {
            if ($lastNumber !== null && $currentNumber !== $lastNumber + 1) {
                for ($i = $lastNumber + 1; $i < $currentNumber; $i++) {
                    $missingSequence[] = $i; // Adiciona os números faltantes
                }
            }
            $lastNumber = $currentNumber; // Atualiza o último número
        }

        // Se houver números faltantes, marque os pedidos
        if (!empty($missingSequence)) {
            foreach ($orders as &$order) {
                $order['faltou'] = true;
            }
        }

        
        foreach ($orders as &$order) {
            if (isset($order['id'])) {
                // Buscar detalhes do pedido individual
                $pedidoResponse = Http::withToken($accessToken)
                    ->get("{$this->baseUrl}/pedidos/vendas/{$order['id']}", [
                        'expand' => 'itens.produto' // Expande os detalhes dos produtos nos itens
                    ]);

                if ($pedidoResponse->failed()) {
                    Log::error("Erro ao buscar detalhes do pedido {$order['id']}", [
                        'response' => $pedidoResponse->body()
                    ]);
                    continue;
                }

                $pedidoData = $pedidoResponse->json('data');

                // Iterar sobre os itens do pedido para adicionar imagens e detalhes
                foreach ($pedidoData['itens'] as $itemIndex => $item) {
                    if (isset($item['produto']['id'])) {
                        $produtoId = $item['produto']['id'];

                        // Consultar o produto para buscar a imagem
                        $produtoResponse = Http::withToken($accessToken)
                            ->get("{$this->baseUrl}/produtos/{$produtoId}");

                        if ($produtoResponse->ok()) {
                            $produtoData = $produtoResponse->json('data');

                            // Pegar o link da primeira imagem, se existir
                            $imagemLink = $produtoData['midia']['imagens']['internas'][0]['link'] ?? null;

                            // Adicionar a imagem ao item do pedido
                            $order['itens'][$itemIndex]['imagem'] = $imagemLink;
                            $order['itens'][$itemIndex]['descricao'] = $item['descricao'];
                            $order['itens'][$itemIndex]['quantidade'] = $item['quantidade'];
                        } else {
                            Log::error("Erro ao buscar produto {$produtoId}", [
                                'response' => $produtoResponse->body()
                            ]);
                            $order['itens'][$itemIndex]['imagem'] = null;
                        }
                    }
                }

                $order['observacoesInternas'] = $pedidoData['observacoesInternas'] ?? null;
            }
        }

    
        // // Adicionar imagens dos produtos
        // foreach ($orders as &$order) {
        //     // if ($count >= $limit) {
        //       //  break; // Interrompe o loop ao atingir o limite
        //     //}
            
        //     if ($order['id']) {
        //         // Buscar detalhes do pedido individual
        //         $pedidoResponse = Http::withToken($accessToken)
        //             ->get("{$this->baseUrl}/pedidos/vendas/{$order['id']}", [
        //                 'expand' => 'itens.produto' // Expande os detalhes dos produtos nos itens
        //             ]);

        //         if ($pedidoResponse->failed()) {
        //             Log::error("Erro ao buscar detalhes do pedido {$order['id']}", [
        //                 'response' => $pedidoResponse->body()
        //             ]);
        //             continue;
        //         }

        //         $pedidoData = $pedidoResponse->json('data');
        //         //dd($pedidoData);// Iterar sobre os itens do pedido
                
        //         foreach ($pedidoData['itens'] as $itemIndex => $item) {
        //             if (isset($item['produto']['id'])) {
        //                 $produtoId = $item['produto']['id'];
                        
        //                 // Consultar o produto para buscar a imagem
        //                 $produtoResponse = Http::withToken($accessToken)
        //                     ->get("{$this->baseUrl}/produtos/{$produtoId}");
        //                 //dd($produtoResponse);
        //                 if ($produtoResponse->ok()) {
        //                     $produtoData = $produtoResponse->json('data');
        //                     //dd($produtoData);
        //                     // Pegar o link da primeira imagem, se existir
        //                     $imagemLink = isset($produtoData['midia']['imagens']['internas'][0]['link'])
        //                     ? $produtoData['midia']['imagens']['internas'][0]['link']
        //                     : null;
        //                     // Adicionar a imagem ao item do pedido
        //                     $orders[$count]['itens'][$itemIndex]['imagem'] = $imagemLink;
        //                     $orders[$count]['itens'][$itemIndex]['descricao'] = $item['descricao'];
        //                     $orders[$count]['itens'][$itemIndex]['quantidade'] = $item['quantidade'];
        //                     //dd($orders[$count]['itens'][$itemIndex]['descricao']);
        //                 } else {
        //                     Log::error("Erro ao buscar produto {$produtoId}", [
        //                         'response' => $produtoResponse->body()
        //                     ]);
        //                     $orders[$count]['itens'][$itemIndex]['produto']['imagem'] = null;
        //                 }
        //             }
        //         }
                
        //         $orders[$count]['observacoesInternas'] = $pedidoData['observacoesInternas'];
        //     }
            
        //     $count++; // Incrementa o contador
        // }
        usort($orders, function ($a, $b) {
            return $a['numero'] <=> $b['numero'];
        });
        return ['orders' => $orders, 'missingSequence' => $missingSequence];
        //return $orders;

    // dd(array_slice($orders, 0, 8));
    // return array_slice($orders, 6, 13);
    }



    /**
     * Atualiza o status de um pedido no Bling
     */
    public function updateOrderStatus($orderId, $statusId)
    {
        $accessToken = $this->getCurrentAccessToken();

        $response = Http::withToken($accessToken)
            ->patch("{$this->baseUrl}/pedidos/vendas/{$orderId}", [
                'situacao' => ['id' => $statusId]
            ]);

        if ($response->failed()) {
            Log::error('Erro ao atualizar status do pedido', ['response' => $response->body()]);
            throw new \Exception('Erro ao atualizar status do pedido');
        }

        return $response->json();
    }

    /**
     * Gera um estado aleatório para segurança na autenticação
     */
    protected function generateState()
    {
        return bin2hex(random_bytes(16));
    }

        /**
     * Busca RÁPIDA de pedidos por intervalo - apenas verifica existência
     * NÃO busca detalhes nem imagens - ideal para verificação
     */
    public function getOrdersByNumberRangeFast($numeroInicial, $numeroFinal)
    {
        $accessToken = $this->getCurrentAccessToken();
        $allOrders = [];
        $batchSize = 10; // Buscar em lotes de 10 para paralelizar

        Log::info('Busca RÁPIDA de pedidos por número', [
            'numeroInicial' => $numeroInicial,
            'numeroFinal' => $numeroFinal
        ]);

        // Buscar pedido por pedido (sem detalhes)
        for ($numero = $numeroInicial; $numero <= $numeroFinal; $numero++) {
            try {
                $response = Http::timeout(10)
                    ->withToken($accessToken)
                    ->get("{$this->baseUrl}/pedidos/vendas", [
                        'numero' => $numero
                    ]);

                if ($response->ok()) {
                    $pedidoData = $response->json('data');

                    if (!empty($pedidoData)) {
                        $pedido = $pedidoData[0];
                        $allOrders[] = [
                            'id' => $pedido['id'],
                            'numero' => $pedido['numero'],
                            'data' => $pedido['data'] ?? null,
                            'contato' => $pedido['contato'] ?? [],
                            'situacao' => $pedido['situacao'] ?? null,
                            'total' => $pedido['total'] ?? 0,
                        ];
                    }
                } elseif ($response->status() === 429) {
                    // Rate limit - aguardar
                    Log::warning("Rate limit na busca rápida, aguardando...");
                    sleep(1);
                    $numero--; // Repetir este número
                    continue;
                }

                // Delay mínimo entre requisições (50ms)
                usleep(50000);

            } catch (\Exception $e) {
                Log::warning("Erro ao buscar pedido {$numero}: " . $e->getMessage());
            }
        }

        // Ordenar por número
        usort($allOrders, fn($a, $b) => $a['numero'] <=> $b['numero']);

        // Identificar números faltantes
        $numerosEncontrados = array_column($allOrders, 'numero');
        $missingSequence = [];

        for ($i = $numeroInicial; $i <= $numeroFinal; $i++) {
            if (!in_array($i, $numerosEncontrados)) {
                $missingSequence[] = $i;
            }
        }

        return [
            'orders' => $allOrders,
            'missingSequence' => $missingSequence,
            'total_esperado' => $numeroFinal - $numeroInicial + 1,
            'total_encontrado' => count($allOrders),
            'total_faltante' => count($missingSequence)
        ];
    }

    /**
     * Busca pedidos por intervalo de números
     */
    public function getOrdersByNumberRange($numeroInicial, $numeroFinal, $buscarImagens = false)
    {
        $accessToken = $this->getCurrentAccessToken();
        $allOrders = [];

        Log::info('Iniciando busca de pedidos por número', [
            'numeroInicial' => $numeroInicial,
            'numeroFinal' => $numeroFinal,
            'buscarImagens' => $buscarImagens
        ]);

        // Buscar pedido por pedido individualmente
        for ($numero = $numeroInicial; $numero <= $numeroFinal; $numero++) {
            $pedido = $this->buscarPedidoComRetry($numero, $accessToken, $buscarImagens);

            if ($pedido) {
                $allOrders[] = $pedido;
            }
        }

        // Ordenar por número
        usort($allOrders, function ($a, $b) {
            return $a['numero'] <=> $b['numero'];
        });

        // Identificar números faltantes
        $numerosEncontrados = array_column($allOrders, 'numero');
        $missingSequence = [];

        for ($i = $numeroInicial; $i <= $numeroFinal; $i++) {
            if (!in_array($i, $numerosEncontrados)) {
                $missingSequence[] = $i;
            }
        }

        return [
            'orders' => $allOrders,
            'missingSequence' => $missingSequence,
            'total_esperado' => $numeroFinal - $numeroInicial + 1,
            'total_encontrado' => count($allOrders),
            'total_faltante' => count($missingSequence)
        ];
    }

    /**
     * Busca um pedido específico com retry automático
     */
    protected function buscarPedidoComRetry($numero, $accessToken, $buscarImagens, $maxTentativas = 3)
    {
        $tentativa = 0;

        while ($tentativa < $maxTentativas) {
            $tentativa++;

            try {
                // Buscar pedido específico
                $response = Http::timeout(30)
                    ->withToken($accessToken)
                    ->get("{$this->baseUrl}/pedidos/vendas", [
                        'numero' => $numero,
                        'expand' => 'itens.produto'
                    ]);

                if ($response->ok()) {
                    $pedidoData = $response->json('data');

                    if (!empty($pedidoData)) {
                        $pedido = $pedidoData[0];

                        Log::info("Pedido {$numero} encontrado (tentativa {$tentativa})", [
                            'id' => $pedido['id'],
                            'cliente' => $pedido['contato']['nome'] ?? 'N/A'
                        ]);

                        // Buscar detalhes completos do pedido COM RETRY
                        $pedidoCompleto = $this->buscarDetalhesPedidoComRetry($pedido['id'], $accessToken, $buscarImagens);

                        if ($pedidoCompleto) {
                            return $pedidoCompleto;
                        } else {
                            // Se não conseguiu detalhes, retorna o básico
                            Log::warning("Pedido {$numero}: retornando dados básicos (sem detalhes)");
                            return $pedido;
                        }
                    } else {
                        Log::info("Pedido {$numero} não encontrado no Bling");
                        return null;
                    }
                } elseif ($response->status() === 429) {
                    // Rate limit - aguardar mais tempo
                    Log::warning("Rate limit atingido para pedido {$numero}, aguardando...");
                    sleep(2);
                    continue;
                } else {
                    Log::warning("Erro ao buscar pedido {$numero} (tentativa {$tentativa})", [
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 200)
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Exceção ao buscar pedido {$numero} (tentativa {$tentativa})", [
                    'erro' => $e->getMessage()
                ]);
            }

            // Delay entre tentativas (aumenta a cada tentativa)
            if ($tentativa < $maxTentativas) {
                usleep(500000 * $tentativa); // 500ms, 1s, 1.5s
            }
        }

        Log::error("Falha ao buscar pedido {$numero} após {$maxTentativas} tentativas");
        return null;
    }

    /**
     * Busca detalhes de um pedido com retry automático
     */
    protected function buscarDetalhesPedidoComRetry($pedidoId, $accessToken, $buscarImagens, $maxTentativas = 3)
    {
        $tentativa = 0;

        while ($tentativa < $maxTentativas) {
            $tentativa++;

            try {
                $pedidoResponse = Http::timeout(30)
                    ->withToken($accessToken)
                    ->get("{$this->baseUrl}/pedidos/vendas/{$pedidoId}", [
                        'expand' => 'itens.produto'
                    ]);

                if ($pedidoResponse->ok()) {
                    $pedidoCompleto = $pedidoResponse->json('data');

                    // Verificar se tem itens
                    if (empty($pedidoCompleto['itens'])) {
                        Log::warning("Pedido {$pedidoId} retornou sem itens, tentando novamente...");
                        if ($tentativa < $maxTentativas) {
                            usleep(300000);
                            continue;
                        }
                    }

                    Log::info("Detalhes do pedido {$pedidoId} obtidos", [
                        'itens' => count($pedidoCompleto['itens'] ?? [])
                    ]);

                    // Se precisar buscar imagens
                    if ($buscarImagens && !empty($pedidoCompleto['itens'])) {
                        foreach ($pedidoCompleto['itens'] as $itemIndex => $item) {
                            if (isset($item['produto']['id'])) {
                                $imagemLink = $this->buscarImagemProdutoComRetry($item['produto']['id'], $accessToken);
                                $pedidoCompleto['itens'][$itemIndex]['imagem'] = $imagemLink;
                            }
                        }
                    }

                    return $pedidoCompleto;

                } elseif ($pedidoResponse->status() === 429) {
                    Log::warning("Rate limit nos detalhes do pedido {$pedidoId}, aguardando...");
                    sleep(2);
                    continue;
                }

            } catch (\Exception $e) {
                Log::error("Exceção ao buscar detalhes do pedido {$pedidoId} (tentativa {$tentativa})", [
                    'erro' => $e->getMessage()
                ]);
            }

            if ($tentativa < $maxTentativas) {
                usleep(300000 * $tentativa);
            }
        }

        return null;
    }

    /**
     * Busca imagem de um produto com retry automático
     */
    protected function buscarImagemProdutoComRetry($produtoId, $accessToken, $maxTentativas = 2)
    {
        $tentativa = 0;

        while ($tentativa < $maxTentativas) {
            $tentativa++;

            try {
                usleep(150000); // 150ms entre requisições de produto

                $produtoResponse = Http::timeout(15)
                    ->withToken($accessToken)
                    ->get("{$this->baseUrl}/produtos/{$produtoId}");

                if ($produtoResponse->ok()) {
                    $produtoData = $produtoResponse->json('data');
                    return $produtoData['midia']['imagens']['internas'][0]['link'] ?? null;
                } elseif ($produtoResponse->status() === 429) {
                    sleep(1);
                    continue;
                }

            } catch (\Exception $e) {
                Log::warning("Erro ao buscar imagem do produto {$produtoId}", [
                    'erro' => $e->getMessage()
                ]);
            }
        }

        return null;
    }
}