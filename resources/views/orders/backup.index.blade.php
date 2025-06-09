<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <button type="submit" class="self-end px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                    Filtrar
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($orders as $order)
                <div id="order-{{ $order['id'] }}" class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="relative aspect-square">
                        <img src="{{ $order['itens'][0]['produto']['imagem'][0] ?? 'https://images.unsplash.com/photo-1612837017391-4b6b7b0e2b0b?q=80&w=500' }}"
                             alt="{{ $order['itens'][0]['produto']['nome'] }}"
                             class="w-full h-full object-cover">
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Pedido #{{ $order['numero'] }}
                            </span>
                            <span class="text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($order['data'])->format('d/m/Y') }}
                            </span>
                        </div>
                        <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                            {{ $order['itens'][0]['produto']['nome'] }}
                        </h3>
                        <button hx-post="{{ route('orders.update-status', $order['id']) }}"
                                hx-swap="outerHTML"
                                hx-target="#order-{{ $order['id'] }}"
                                class="w-full bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 transition-colors">
                            Pedido Passado
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-600">
                    Nenhum pedido em aberto encontrado
                </div>
            @endforelse
        </div>
    </main>

    <script>
        document.body.addEventListener('htmx:afterRequest', function(evt) {
            if (evt.detail.successful) {
                const element = evt.detail.target;
                element.remove();
            }
        });
    </script>
</body>
</html>