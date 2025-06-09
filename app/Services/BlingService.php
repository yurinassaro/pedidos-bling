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
    // Garante que o token esteja atualizado antes da requisição
    if (!$this->hasValidToken()) {
        $this->refreshToken();
    }

    $accessToken = $this->getCurrentAccessToken();

    // Buscar pedidos
    $response = Http::withToken($accessToken)
        ->get("{$this->baseUrl}/pedidos/vendas", [
            'dataInicial' => $startDate, // Data inicial no formato YYYY-MM-DD
            'dataFinal' => $endDate,     // Data final no formato YYYY-MM-DD
            'expand' => 'itens.produto' // Expande os detalhes dos produtos nos itens
        ]);

    
    if ($response->failed()) {
        Log::error('Erro ao buscar pedidos', ['response' => $response->body()]);
        throw new \Exception('Erro ao buscar pedidos');
    }

    $orders = $response->json('data') ?? [];
    // dd($orders);
    $allNumbers = array_map('intval', array_column($orders, 'numero'));
    sort($allNumbers);
    
    $missingSequence = [];
    $lastNumber = null;

    foreach ($allNumbers as $currentNumber) {
        if ($lastNumber !== null && $currentNumber !== $lastNumber + 1) {
            for ($i = $lastNumber + 1; $i < $currentNumber; $i++) {
                $missingSequence[] = $i;
            }
        }
        $lastNumber = $currentNumber;
    }

    // Tentar recuperar pedidos que estão faltando
    foreach ($missingSequence as $missingNumber) {
        $pedidoResponse = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/pedidos/vendas", [
                'numero' => $missingNumber,
                'expand' => 'itens.produto'
            ]);

        if ($pedidoResponse->ok()) {
            $pedidoData = $pedidoResponse->json('data');
            if (!empty($pedidoData)) {
                $orders[] = $pedidoData[0];
            }
        } else {
            Log::error("Erro ao buscar pedido manualmente: {$missingNumber}", [
                'response' => $pedidoResponse->body()
            ]);
        }
    }

    
    foreach ($orders as &$order) {
        if (isset($order['id'])) {
            $pedidoResponse = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/pedidos/vendas/{$order['id']}", [
                    'expand' => 'itens.produto'
                ]);

            if ($pedidoResponse->failed()) {
                Log::error("Erro ao buscar detalhes do pedido {$order['id']}", [
                    'response' => $pedidoResponse->body()
                ]);
                continue;
            }

            $pedidoData = $pedidoResponse->json('data');
            
            foreach ($pedidoData['itens'] as $itemIndex => $item) {
                if (isset($item['produto']['id'])) {
                    $produtoId = $item['produto']['id'];
                    $produtoResponse = Http::withToken($accessToken)
                        ->get("{$this->baseUrl}/produtos/{$produtoId}");

                    if ($produtoResponse->ok()) {
                        $produtoData = $produtoResponse->json('data');
                        $imagemLink = $produtoData['midia']['imagens']['internas'][0]['link'] ?? null;
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
    
    usort($orders, function ($a, $b) {
        return $a['numero'] <=> $b['numero'];
    });

    return ['orders' => $orders, 'missingSequence' => $missingSequence];
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
}