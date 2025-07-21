<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $this->whatsappGroupId = env('WHATSAPP_GROUP_ID'); // ID do grupo para enviar os pedidos
    }

    /**
     * Envia o pedido para o WhatsApp
     */
    public function sendOrderToWhatsApp(Request $request)
    {
        try {
            $orderNumber = $request->input('order_number');
            $customerName = $request->input('customer_name');
            $products = $request->input('products');
            
            // Verificar se já foi enviado
            $existingSend = DB::table('whatsapp_sends')
                ->where('order_number', $orderNumber)
                ->where('sent', true)
                ->first();

            if ($existingSend) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pedido já foi enviado anteriormente',
                    'sent_at' => $existingSend->sent_at,
                    'resend' => true
                ]);
            }

            // Montar mensagem
            $message = "*{$orderNumber} - {$customerName}*\n";
            $message .= "Data: " . now()->format('d/m/Y') . "\n\n";

            // Enviar mensagem de texto primeiro
            $textResponse = $this->sendTextMessage($message);

            if (!$textResponse['success']) {
                throw new \Exception('Falha ao enviar mensagem de texto');
            }

            // Enviar imagens dos produtos
            $allImagesSent = true;
            foreach ($products as $product) {
                if (!empty($product['image'])) {
                    $imageMessage = "📦 *{$product['description']}*\n";
                    $imageMessage .= "Quantidade: {$product['quantity']}";
                    
                    $imageResponse = $this->sendImageMessage($product['image'], $imageMessage);
                    
                    if (!$imageResponse['success']) {
                        $allImagesSent = false;
                        Log::error('Falha ao enviar imagem do produto', [
                            'product' => $product['description'],
                            'error' => $imageResponse['error'] ?? 'Unknown error'
                        ]);
                    }
                }
            }

            // Salvar no banco de dados
            DB::table('whatsapp_sends')->insert([
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'products_data' => json_encode($products),
                'whatsapp_group_id' => $this->whatsappGroupId,
                'sent' => true,
                'sent_at' => now(),
                'evolution_message_id' => $textResponse['messageId'] ?? null,
                'status' => $allImagesSent ? 'sent' : 'partially_sent',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido enviado com sucesso',
                'all_images_sent' => $allImagesSent
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao enviar pedido para WhatsApp', [
                'error' => $e->getMessage(),
                'order' => $orderNumber ?? 'Unknown'
            ]);

            // Salvar erro no banco
            DB::table('whatsapp_sends')->insert([
                'order_number' => $orderNumber ?? 'Unknown',
                'customer_name' => $customerName ?? 'Unknown',
                'products_data' => json_encode($products ?? []),
                'whatsapp_group_id' => $this->whatsappGroupId,
                'sent' => false,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reenvia um pedido para o WhatsApp
     */
    public function resendOrderToWhatsApp(Request $request)
    {
        $orderNumber = $request->input('order_number');
        
        // Marcar envio anterior como reenvio
        DB::table('whatsapp_sends')
            ->where('order_number', $orderNumber)
            ->update(['status' => 'resent']);
        
        // Enviar novamente
        return $this->sendOrderToWhatsApp($request);
    }

    /**
     * Verifica o status de envio de um pedido
     */
    public function checkOrderStatus($orderNumber)
    {
        $send = DB::table('whatsapp_sends')
            ->where('order_number', $orderNumber)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$send) {
            return response()->json([
                'sent' => false,
                'status' => 'not_sent'
            ]);
        }

        return response()->json([
            'sent' => $send->sent,
            'status' => $send->status,
            'sent_at' => $send->sent_at,
            'error_message' => $send->error_message
        ]);
    }

    /**
     * Envia mensagem de texto via Evolution API
     */
    private function sendTextMessage($text)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->evolutionApiUrl}/message/sendText/{$this->instanceName}", [
                'number' => $this->whatsappGroupId,
                'text' => $text
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
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->evolutionApiUrl}/message/sendMedia/{$this->instanceName}", [
                'number' => $this->whatsappGroupId,
                'mediatype' => 'image',
                'media' => $imageUrl,
                'caption' => $caption
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

    /**
     * Obtém configurações do WhatsApp
     */
    public function getWhatsAppConfig()
    {
        return response()->json([
            'instance_name' => $this->instanceName,
            'group_id' => $this->whatsappGroupId,
            'api_configured' => !empty($this->evolutionApiKey)
        ]);
    }

                /**
             * Lista todos os grupos do WhatsApp
             */
            /**
         * Lista todos os grupos do WhatsApp
         */
        /**
     * Lista todos os grupos do WhatsApp
     */
    public function listGroups()
    {
        try {
            // Pßrimeiro, vamos tentar com o parâmetro getParticipants
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->evolutionApiUrl}/group/fetchAllGroups/{$this->instanceName}", [
                'getParticipants' => 'false'  // Não precisa dos participantes para listar os grupos
            ]);

            // Se falhar, tenta outro endpoint
            if ($response->failed()) {
                // Tenta endpoint alternativo
                $response = Http::withHeaders([
                    'apikey' => $this->evolutionApiKey,
                    'Content-Type' => 'application/json'
                ])->get("{$this->evolutionApiUrl}/group/getAll/{$this->instanceName}");
            }

            // Se ainda falhar, tenta mais um endpoint
            if ($response->failed()) {
                // Tenta endpoint de listar grupos
                $response = Http::withHeaders([
                    'apikey' => $this->evolutionApiKey,
                    'Content-Type' => 'application/json'
                ])->get("{$this->evolutionApiUrl}/chat/findChats/{$this->instanceName}");
            }

            if ($response->failed()) {
                return view('whatsapp.groups', [
                    'error' => 'Falha ao buscar grupos. Verifique se a instância está conectada.',
                    'groups' => [],
                    'currentGroupId' => $this->whatsappGroupId ?? null,
                    'debugInfo' => [
                        'instance' => $this->instanceName,
                        'api_url' => $this->evolutionApiUrl,
                        'response' => $response->body()
                    ]
                ]);
            }

            $data = $response->json();
            $groups = [];

            // Verifica se é um array direto ou se está dentro de uma chave
            if (isset($data['data'])) {
                $chats = $data['data'];
            } elseif (isset($data['groups'])) {
                $chats = $data['groups'];
            } else {
                $chats = is_array($data) ? $data : [];
            }

            // Filtra apenas grupos (IDs que terminam com @g.us)
            foreach ($chats as $chat) {
                $id = $chat['id'] ?? ($chat['remoteJid'] ?? ($chat['chatId'] ?? ''));
                if (strpos($id, '@g.us') !== false) {
                    $groups[] = [
                        'id' => $id,
                        'subject' => $chat['subject'] ?? ($chat['name'] ?? 'Grupo sem nome'),
                        'participants' => $chat['participants'] ?? [],
                        'desc' => $chat['desc'] ?? ($chat['description'] ?? '')
                    ];
                }
            }
            
            return view('whatsapp.groups', [
                'groups' => $groups,
                'currentGroupId' => $this->whatsappGroupId ?? null,
                'error' => null
            ]);

        } catch (\Exception $e) {
            return view('whatsapp.groups', [
                'error' => 'Erro ao conectar com Evolution API: ' . $e->getMessage(),
                'groups' => [],
                'currentGroupId' => $this->whatsappGroupId ?? null
            ]);
        }
    }

    /**
     * Método alternativo para buscar grupos usando outro endpoint
     */
    public function fetchGroupsAlternative()
    {
        try {
            // Tenta buscar todos os chats primeiro
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json'
            ])->get("{$this->evolutionApiUrl}/chat/list/{$this->instanceName}");

            if ($response->successful()) {
                $chats = $response->json();
                $groups = [];

                // Filtra apenas grupos
                foreach ($chats as $chat) {
                    if (isset($chat['id']) && strpos($chat['id'], '@g.us') !== false) {
                        // Busca informações detalhadas do grupo
                        $groupResponse = Http::withHeaders([
                            'apikey' => $this->evolutionApiKey,
                            'Content-Type' => 'application/json'
                        ])->get("{$this->evolutionApiUrl}/group/participants/{$this->instanceName}", [
                            'groupJid' => $chat['id']
                        ]);

                        if ($groupResponse->successful()) {
                            $groupData = $groupResponse->json();
                            $groups[] = [
                                'id' => $chat['id'],
                                'subject' => $groupData['subject'] ?? 'Grupo sem nome',
                                'participants' => $groupData['participants'] ?? [],
                                'desc' => $groupData['desc'] ?? ''
                            ];
                        }
                    }
                }

                return $groups;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar grupos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Testa o envio de mensagem para o grupo
     */
    public function testGroupMessage(Request $request)
    {
        $groupId = $request->input('group_id');
        
        try {
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->evolutionApiUrl}/message/sendText/{$this->instanceName}", [
                'number' => $groupId,
                'text' => '🚀 Teste de integração Laravel + WhatsApp funcionando! ' . now()->format('d/m/Y H:i:s')
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mensagem de teste enviada com sucesso!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Falha ao enviar mensagem: ' . $response->body()
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ], 500);
        }
    }


        /**
     * Testa a conexão com a Evolution API e mostra informações
     */
    public function testConnection()
    {
        $tests = [];
        
        // Teste 1: Verificar status da instância
        try {
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey
            ])->get("{$this->evolutionApiUrl}/instance/connectionState/{$this->instanceName}");
            
            $tests['instance_status'] = [
                'success' => $response->successful(),
                'data' => $response->json(),
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            $tests['instance_status'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        // Teste 2: Listar instâncias
        try {
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey
            ])->get("{$this->evolutionApiUrl}/instance/list");
            
            $tests['instances_list'] = [
                'success' => $response->successful(),
                'data' => $response->json(),
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            $tests['instances_list'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        // Teste 3: Verificar se consegue acessar chats
        try {
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey
            ])->get("{$this->evolutionApiUrl}/chat/findChats/{$this->instanceName}");
            
            $tests['chats'] = [
                'success' => $response->successful(),
                'count' => $response->successful() ? count($response->json()) : 0,
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            $tests['chats'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return view('whatsapp.test-connection', [
            'tests' => $tests,
            'config' => [
                'api_url' => $this->evolutionApiUrl,
                'instance_name' => $this->instanceName,
                'has_api_key' => !empty($this->evolutionApiKey),
                'group_id' => $this->whatsappGroupId
            ]
        ]);
    }
}