@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Gerenciar Pedidos</h1>
        <a href="{{ route('pedidos.importacao.index') }}" 
           class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            Importar Pedidos
        </a>
    </div>

    {{-- Cards de Status --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Em Aberto</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $contadores['aberto'] }}</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Em Produção</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $contadores['em_producao'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Finalizados</p>
                    <p class="text-3xl font-bold text-green-600">{{ $contadores['finalizado'] }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('pedidos.index') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" 
                        id="status" 
                        class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="todos" {{ request('status') == 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="aberto" {{ request('status') == 'aberto' ? 'selected' : '' }}>Em Aberto</option>
                    <option value="em_producao" {{ request('status') == 'em_producao' ? 'selected' : '' }}>Em Produção</option>
                    <option value="finalizado" {{ request('status') == 'finalizado' ? 'selected' : '' }}>Finalizados</option>
                </select>
            </div>

            <div>
                <label for="numero_inicial" class="block text-sm font-medium text-gray-700">A partir do pedido</label>
                <input type="number" 
                       name="numero_inicial" 
                       id="numero_inicial" 
                       value="{{ request('numero_inicial') }}"
                       placeholder="Ex: 1000"
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="data_inicial" class="block text-sm font-medium text-gray-700">Data Inicial</label>
                <input type="date" 
                       name="data_inicial" 
                       id="data_inicial" 
                       value="{{ request('data_inicial') }}"
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="data_final" class="block text-sm font-medium text-gray-700">Data Final</label>
                <input type="date" 
                       name="data_final" 
                       id="data_final" 
                       value="{{ request('data_final') }}"
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <button type="submit" 
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Filtrar
            </button>
            
            <a href="{{ route('pedidos.index') }}" 
               class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                Limpar
            </a>
        </form>
    </div>

    {{-- Lista de Pedidos --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($pedidos as $pedido)
            <div class="bg-white rounded-lg shadow-md overflow-hidden pedido-card" data-pedido-id="{{ $pedido->id }}">
                {{-- Header do Card --}}
                <div class="p-4 border-b bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-lg">
                                Pedido #{{ $pedido->numero }}
                            </h3>
                            <p class="text-sm text-gray-600">{{ $pedido->cliente_nome }}</p>
                            <p class="text-xs text-gray-500">{{ $pedido->data_pedido->format('d/m/Y') }}</p>
                        </div>
                        <span class="status-badge px-3 py-1 rounded-full text-xs font-semibold
                            @if($pedido->status == 'aberto') bg-yellow-100 text-yellow-800
                            @elseif($pedido->status == 'em_producao') bg-blue-100 text-blue-800
                            @else bg-green-100 text-green-800
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $pedido->status)) }}
                        </span>
                    </div>
                </div>

                {{-- Observações --}}
                @if($pedido->observacoes_internas)
                <div class="px-4 py-2 bg-amber-50 border-b border-amber-100">
                    <p class="text-sm text-amber-800">
                        <strong>Obs:</strong> {{ $pedido->observacoes_internas }}
                    </p>
                </div>
                @endif

                {{-- Itens do Pedido --}}
                <div class="divide-y divide-gray-200 max-h-[600px] md:max-h-[500px] overflow-y-auto">
                    @foreach($pedido->itens as $item)
                    <div class="p-4">
                        @if($item->imagem)
                        <img src="{{ $item->hasCustomImage() ? Storage::url($item->imagem) : $item->imagem }}" 
                             alt="{{ $item->descricao }}"
                             class="w-full h-100 md:h-100 lg:h-100 object-cover rounded mb-3">
                        @else
                        <div class="w-full h-100 md:h-100 lg:h-100 bg-gray-200 rounded mb-3 flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @endif
                        <h4 class="font-medium text-sm mb-1">{{ $item->descricao }}</h4>
                        <p class="text-sm text-gray-600">Qtd: {{ number_format($item->quantidade, 0) }}</p>
                        @if($item->hasCustomImage())
                        <p class="text-xs text-blue-600 mt-1">✓ Imagem personalizada</p>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Ações --}}
                <div class="p-4 bg-gray-50 border-t">
                    <div class="flex gap-2">
                        <a href="{{ route('pedidos.show', $pedido) }}" 
                           class="flex-1 text-center px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
                            Ver Detalhes
                        </a>
                        
                        @if($pedido->status == 'aberto')
                        <button onclick="alterarStatus({{ $pedido->id }}, 'em_producao')"
                                class="flex-1 px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                            Iniciar Produção
                        </button>
                        @elseif($pedido->status == 'em_producao')
                        <button onclick="alterarStatus({{ $pedido->id }}, 'finalizado')"
                                class="flex-1 px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm">
                            Finalizar
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-2 text-gray-500">Nenhum pedido encontrado</p>
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    <div class="mt-8">
        {{ $pedidos->withQueryString()->links() }}
    </div>
</div>

@push('scripts')
<script>
function alterarStatus(pedidoId, novoStatus) {
    if (!confirm(`Confirma a alteração do status para "${novoStatus.replace('_', ' ')}"?`)) {
        return;
    }

    fetch(`/pedidos/${pedidoId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ status: novoStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar o card sem recarregar a página
            const card = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
            
            // Atualizar badge de status
            const badge = card.querySelector('.status-badge');
            badge.className = 'status-badge px-3 py-1 rounded-full text-xs font-semibold ';
            
            if (novoStatus === 'em_producao') {
                badge.className += 'bg-blue-100 text-blue-800';
                badge.textContent = 'Em Produção';
            } else if (novoStatus === 'finalizado') {
                badge.className += 'bg-green-100 text-green-800';
                badge.textContent = 'Finalizado';
            }
            
            // Atualizar botões de ação
            const actionsDiv = card.querySelector('.p-4.bg-gray-50.border-t > div');
            let newButtons = '<a href="/pedidos/' + pedidoId + '" class="flex-1 text-center px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">Ver Detalhes</a>';
            
            if (novoStatus === 'em_producao') {
                newButtons += '<button onclick="alterarStatus(' + pedidoId + ', \'finalizado\')" class="flex-1 px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm">Finalizar</button>';
            }
            
            actionsDiv.innerHTML = newButtons;
            
            // Mostrar notificação de sucesso
            showNotification('Status atualizado com sucesso!', 'success');
        } else {
            showNotification('Erro ao atualizar status', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao atualizar status', 'error');
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
@endpush
@endsection