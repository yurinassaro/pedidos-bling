@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('pedidos.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Voltar para Lista
        </a>
    </div>

    {{-- Cabeçalho do Pedido --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">Pedido #{{ $pedido->numero }}</h1>
                <p class="text-lg text-gray-600">{{ $pedido->cliente_nome }}</p>
                @if($pedido->cliente_telefone)
                <p class="text-gray-500">📱 {{ $pedido->cliente_telefone }}</p>
                @endif
            </div>
            <div class="text-right">
                <span class="status-badge inline-block px-4 py-2 rounded-full text-sm font-semibold
                    @if($pedido->status == 'aberto') bg-yellow-100 text-yellow-800
                    @elseif($pedido->status == 'em_producao') bg-blue-100 text-blue-800
                    @else bg-green-100 text-green-800
                    @endif">
                    {{ ucfirst(str_replace('_', ' ', $pedido->status)) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Data do Pedido</p>
                <p class="font-semibold">{{ $pedido->data_pedido->format('d/m/Y') }}</p>
            </div>
            @if($pedido->data_producao)
            <div>
                <p class="text-gray-500">Início da Produção</p>
                <p class="font-semibold">{{ $pedido->data_producao->format('d/m/Y H:i') }}</p>
            </div>
            @endif
            @if($pedido->data_finalizacao)
            <div>
                <p class="text-gray-500">Finalizado em</p>
                <p class="font-semibold">{{ $pedido->data_finalizacao->format('d/m/Y H:i') }}</p>
            </div>
            @endif
        </div>

        @if($pedido->observacoes_internas)
        <div class="mt-4 p-4 bg-amber-50 rounded-lg">
            <p class="text-amber-800">
                <strong>Observações:</strong> {{ $pedido->observacoes_internas }}
            </p>
        </div>
        @endif

        {{-- Botões de Ação --}}
        <div class="mt-6 flex gap-3">
            @if($pedido->status == 'aberto')
            <button onclick="alterarStatus('em_producao')"
                    class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                Iniciar Produção
            </button>
            @elseif($pedido->status == 'em_producao')
            <button onclick="alterarStatus('finalizado')"
                    class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                Finalizar Pedido
            </button>
            <button onclick="alterarStatus('aberto')"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                Voltar para Aberto
            </button>
            @endif
        </div>
    </div>

    {{-- Itens do Pedido --}}
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Itens do Pedido</h2>
        
        @if($pedido->itens->isEmpty())
            <p class="text-gray-500 text-center py-8">Nenhum item encontrado neste pedido.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($pedido->itens as $item)
                <div class="border rounded-lg overflow-hidden" data-item-id="{{ $item->id }}">
                    <div class="relative">
                        @if($item->imagem_personalizada || $item->imagem_original)
                        <img src="{{ $item->imagem_personalizada ? '/serve-image.php?path=' . $item->imagem_personalizada : $item->imagem_original }}" 
                            alt="{{ $item->descricao }}"
                            class="w-full h-64 object-cover">
                        @else
                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                            <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @endif
                        
                        @if($item->imagem_personalizada)
                        <div class="absolute top-2 right-2 bg-blue-500 text-white px-2 py-1 rounded text-xs">
                            Personalizada
                        </div>
                        @endif
                    </div>
                    
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-2">{{ $item->descricao }}</h3>
                        <p class="text-gray-600 mb-4">Quantidade: {{ number_format($item->quantidade, 0) }}</p>
                        
                        @if($item->observacoes)
                        <p class="text-sm text-gray-500 mb-4">{{ $item->observacoes }}</p>
                        @endif
                        
                        <div class="flex gap-2">
                            <label for="upload-{{ $item->id }}" 
                                   class="flex-1 text-center px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 cursor-pointer">
                                {{ $item->imagem_personalizada ? 'Trocar Imagem' : 'Adicionar Imagem' }}
                            </label>
                            <input type="file" 
                                   id="upload-{{ $item->id }}" 
                                   class="hidden" 
                                   accept="image/*"
                                   onchange="uploadImagem({{ $pedido->id }}, {{ $item->id }}, this)">
                            
                            @if($item->imagem_personalizada)
                            <button onclick="removerImagem({{ $pedido->id }}, {{ $item->id }})"
                                    class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                                Remover
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function alterarStatus(novoStatus) {
    if (!confirm(`Confirma a alteração do status para "${novoStatus.replace('_', ' ')}"?`)) {
        return;
    }

    fetch(`/pedidos/{{ $pedido->id }}/status`, {
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
            showNotification('Status atualizado com sucesso!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('Erro ao atualizar status', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao atualizar status', 'error');
    });
}

function uploadImagem(pedidoId, itemId, input) {
    const file = input.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('imagem', file);

    // Mostrar loading
    const itemDiv = document.querySelector(`[data-item-id="${itemId}"]`);
    itemDiv.classList.add('opacity-50');

    fetch(`/pedidos/${pedidoId}/itens/${itemId}/imagem`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => {
        console.log('Status:', response.status); // Debug
        console.log('Headers:', response.headers); // Debug
        
        if (!response.ok) {
            throw new Error('Erro na requisição');
        }
        return response.json();
    })
    .then(data => {
        console.log('Resposta:', data); // Debug
        
        if (data.success) {
            showNotification('Imagem atualizada com sucesso!', 'success');
            
            // Recarregar a página após 1.5 segundos
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(data.message || 'Erro ao fazer upload da imagem', 'error');
            itemDiv.classList.remove('opacity-50');
        }
    })
    .catch(error => {
        console.error('Erro completo:', error); // Debug
        showNotification('Erro ao fazer upload da imagem', 'error');
        itemDiv.classList.remove('opacity-50');
    });
}

function removerImagem(pedidoId, itemId) {
    if (!confirm('Confirma a remoção da imagem personalizada?')) {
        return;
    }

    fetch(`/pedidos/${pedidoId}/itens/${itemId}/imagem`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Imagem removida com sucesso!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification('Erro ao remover imagem', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao remover imagem', 'error');
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