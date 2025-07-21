<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gerenciamento de Pedidos Bling</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-gray-100">
    @if (session('success') || session('error'))
        <div class="fixed top-4 right-4 z-50">
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-2">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-2">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    @endif

    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Pedidos Bling</h1>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex items-center gap-4 bg-white p-4 rounded-lg shadow-md mb-8">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <form method="get" class="flex gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Data Inicial</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                           class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">Data Final</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                           class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" name="filter" value="1"
                            class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors">
                        Filtrar Pedidos
                    </button>
                    <button type="button" onclick="importOrders()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                        Importar
                    </button>
                </div>
            </form>
        </div>

        @if (!empty($missingSequence))
            @php
                $missingAboveCurrent = $missingSequence;
            @endphp
            @if (!empty($missingAboveCurrent))
                <div class="alert alert-danger text-center text-lg font-bold">
                    ATENÇÃO: FALTANDO OS PEDIDOS: {{ implode(', ', $missingAboveCurrent) }}
                </div>        
            @endif
        @endif

        @forelse ($orders as $order)
            @php
            if (isset($missingSequence)) {
                $missingAboveCurrent = array_filter($missingSequence, function ($missing) use ($order) {
                    return $missing < $order['numero'];
                });
                $missingSequence = array_diff($missingSequence, $missingAboveCurrent);
            }
            @endphp

            @if (!empty($missingAboveCurrent))
                <div class="alert alert-danger text-center text-lg font-bold">
                    ATENÇÃO: FALTANDO: {{ implode(', ', $missingAboveCurrent) }}
                </div>        
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" style="margin-top:10px">
                <div id="order-{{ $order['numero'] }}" class="bg-white rounded-lg shadow-md overflow-hidden">
                    <h2 class="font-semibold text-lg mb-3 line-clamp-2 text-center">
                        *{{ $order['numero'] }} - {{ $order['contato']['nome'] }}* 
                        <span class="text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($order['data'])->format('d/m/Y') }}
                        </span>
                    </h2>
                    
                    @if (isset($order['observacoesInternas']) && $order['observacoesInternas'] != null)
                        <h2 class="font-semibold text-lg mb-3 line-clamp-2 text-center">
                            Obs: {{ $order['observacoesInternas'] }}
                        </h2>    
                    @endif
                    
                    <!-- Botão para enviar todos os produtos do pedido -->
                    <div class="px-4 mb-4">
                        <button data-order-number="{{ $order['numero'] }}" 
                                data-customer-name="{{ $order['contato']['nome'] }}"
                                onclick="sendOrderToWhatsApp(this)" 
                                id="btn-order-{{ $order['numero'] }}"
                                class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            <span>Enviar para o Zap</span>
                        </button>
                        <div id="status-order-{{ $order['numero'] }}" class="mt-2 text-sm text-center hidden">
                            <!-- Status do envio aparecerá aqui -->
                        </div>
                    </div>
                    
                    @if (isset($order['itens']) && is_array($order['itens']))
                        @foreach ($order['itens'] as $item)
                            <div class="relative aspect-square">                    
                                <img src="{{ $item['imagem'] ?? 'INSIRA UMA FOTO' }}"
                                     alt=""
                                     class="w-full h-full object-cover">
                                
                                <div class="p-4">                        
                                    <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                                        @if (isset($item['descricao']))
                                            {{ $item['descricao'] }}
                                        @endif
                                    </h3>                          
                                </div>   
                                
                                <div class="p-4">                        
                                    <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                                        @if (isset($item['quantidade']) && $item['quantidade'] != null)
                                            qtd: {{ $item['quantidade'] }}
                                        @endif
                                    </h3>                          
                                </div>                        
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @empty
            <p class="text-center text-gray-500 py-8">Nenhum pedido encontrado.</p>
        @endforelse
    </main>
<script>
    // Função para enviar pedido - SEM VERIFICAÇÃO AUTOMÁTICA
    async function sendOrderToWhatsApp(button) {
        // Prevenir múltiplos cliques
        if (button.disabled) return;
        
        const orderNumber = button.getAttribute('data-order-number');
        const customerName = button.getAttribute('data-customer-name');
        
        console.log('Enviando pedido:', orderNumber);
        
        // Coletar dados do pedido
        const orderElement = document.getElementById(`order-${orderNumber}`);
        const products = [];
        
        // Extrair informações dos produtos
        const productElements = orderElement.querySelectorAll('.relative.aspect-square');
        productElements.forEach(el => {
            const img = el.querySelector('img');
            const descDiv = el.querySelector('h3');
            const qtyDiv = el.querySelectorAll('h3')[1];
            
            let description = '';
            let quantity = '1';
            
            if (descDiv && descDiv.textContent.trim() && !descDiv.textContent.includes('qtd:')) {
                description = descDiv.textContent.trim();
            }
            
            if (qtyDiv && qtyDiv.textContent.includes('qtd:')) {
                quantity = qtyDiv.textContent.replace('qtd:', '').trim();
            }
            
            if (description) {
                products.push({
                    image: img.src,
                    description: description,
                    quantity: quantity
                });
            }
        });
        
        // Desabilitar botão
        button.disabled = true;
        const originalContent = button.innerHTML;
        button.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Enviando...</span>
        `;
        
        try {
            const response = await fetch('/whatsapp/send-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    order_number: orderNumber,
                    customer_name: customerName,
                    products: products
                })
            });
            
            // Mostrar sucesso
            button.innerHTML = `
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span>Enviado!</span>
            `;
            
            // Aguardar 5 segundos antes de reabilitar
            setTimeout(() => {
                button.innerHTML = originalContent;
                button.disabled = false;
            }, 5000);
            
        } catch (error) {
            console.error('Erro:', error);
            button.innerHTML = originalContent;
            button.disabled = false;
            alert('Erro ao enviar. Tente novamente.');
        }
    }
    
    function importOrders() {
        if (confirm('Deseja importar os pedidos?')) {
            fetch('/import', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Importação realizada');
                window.location.reload();
            })
            .catch(error => {
                alert('Erro na importação: ' + error.message);
            });
        }
    }
    
    // NÃO HÁ CÓDIGO AUTOMÁTICO AQUI
    console.log('Script carregado - Envio manual apenas');
</script>
</body>
</html>