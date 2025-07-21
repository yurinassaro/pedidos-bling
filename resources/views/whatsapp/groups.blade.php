<!-- Arquivo: resources/views/whatsapp/groups.blade.php -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Grupos WhatsApp - Evolution API</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                Grupos WhatsApp Disponíveis
            </h1>

            @if($error)
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p class="font-bold">Erro:</p>
                    <p>{{ $error }}</p>
                    
                    @if(isset($debugInfo))
                        <details class="mt-2">
                            <summary class="cursor-pointer text-sm">Ver detalhes técnicos</summary>
                            <pre class="mt-2 text-xs bg-red-50 p-2 rounded overflow-x-auto">{{ json_encode($debugInfo, JSON_PRETTY_PRINT) }}</pre>
                        </details>
                    @endif
                </div>
            @endif

            @if(isset($currentGroupId) && $currentGroupId)
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
                    <p class="font-bold">ID do Grupo Configurado:</p>
                    <p class="font-mono">{{ $currentGroupId }}</p>
                </div>
            @endif

            @if(count($groups) > 0)
                <div class="space-y-4">
                    @foreach($groups as $index => $group)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        {{ $group['subject'] ?? 'Grupo sem nome' }}
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span class="font-semibold">ID:</span> 
                                        <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $group['id'] ?? 'ID não disponível' }}</code>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span class="font-semibold">Participantes:</span> 
                                        {{ count($group['participants'] ?? []) }}
                                    </p>
                                    @if(isset($group['desc']))
                                        <p class="text-sm text-gray-600 mt-1">
                                            <span class="font-semibold">Descrição:</span> 
                                            {{ $group['desc'] }}
                                        </p>
                                    @endif
                                </div>
                                <div class="ml-4 space-y-2">
                                    <button onclick="copyToClipboard('{{ $group['id'] ?? '' }}')" 
                                            class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600 transition-colors">
                                        Copiar ID
                                    </button>
                                    <button onclick="testGroup('{{ $group['id'] ?? '' }}')" 
                                            class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition-colors block w-full">
                                        Testar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-semibold text-yellow-800 mb-2">📌 Como usar:</h4>
                    <ol class="list-decimal list-inside text-sm text-yellow-700 space-y-1">
                        <li>Copie o ID do grupo desejado</li>
                        <li>Adicione no arquivo <code class="bg-yellow-100 px-1 rounded">.env</code>:</li>
                        <li class="font-mono ml-4">WHATSAPP_GROUP_ID=ID_COPIADO_AQUI</li>
                        <li>Execute: <code class="bg-yellow-100 px-1 rounded">php artisan config:clear</code></li>
                    </ol>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <p>Nenhum grupo encontrado.</p>
                    <p class="text-sm mt-2">Verifique se o WhatsApp está conectado e se o bot está em algum grupo.</p>
                </div>
            @endif

            <div class="mt-6 flex justify-between">
                <a href="{{ route('orders.index') }}" 
                   class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
                    ← Voltar aos Pedidos
                </a>
                <button onclick="location.reload()" 
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                    🔄 Atualizar Lista
                </button>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('ID copiado para a área de transferência!');
            }).catch(err => {
                alert('Erro ao copiar: ' + err);
            });
        }

        async function testGroup(groupId) {
            if (!confirm('Enviar mensagem de teste para este grupo?')) {
                return;
            }

            try {
                const response = await fetch('/whatsapp/test-group', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ group_id: groupId })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ Erro ao enviar mensagem de teste: ' + error.message);
            }
        }
    </script>
</body>
</html>