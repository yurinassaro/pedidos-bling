@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
        <div class="flex items-center gap-4">
            <h1 class="text-3xl font-bold">Gerenciar Pedidos</h1>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('pedidos.importacao.por-numero') }}"
           class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            Importar Pedidos
        </a>
        @endif
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
                    <p class="text-sm font-medium text-gray-600">Em Producao</p>
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
                    <option value="em_producao" {{ request('status') == 'em_producao' ? 'selected' : '' }}>Em Producao</option>
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
                        <div class="flex items-start gap-3 flex-1">
                            @if(auth()->user()->isSuperAdmin())
                            <input type="checkbox"
                                   class="pedido-checkbox hidden mt-1 h-5 w-5 text-red-600 rounded"
                                   data-pedido-id="{{ $pedido->id }}"
                                   onchange="atualizarContagemSelecionados()">
                            @endif
                            <div>
                                <h3 class="font-semibold text-lg">
                                    Pedido #{{ $pedido->numero }}
                                </h3>
                                <p class="text-sm text-gray-600">{{ $pedido->cliente_nome }}</p>
                                <p class="text-xs text-gray-500">{{ $pedido->data_pedido->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Observacoes --}}
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
                        @if($item->imagem_personalizada || $item->imagem_local || $item->imagem_original)
                        <img src="{{ $item->imagem_personalizada ? '/serve-image.php?path=' . $item->imagem_personalizada : ($item->imagem_local ? asset('storage/' . $item->imagem_local) : $item->imagem_original) }}"
                            alt="{{ $item->descricao }}"
                            class="w-full h-90 md:h-90 lg:h-90 object-cover rounded mb-3">
                        @else

                        <div class="w-full h-90 md:h-90 lg:h-90 bg-gray-200 rounded mb-2 flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @endif
                        <h4  class="font-medium text-sm mb-1" style="margin:0; padding:0">{{ $item->descricao }}</h4>
                        <p class="text-sm text-gray-600" style="margin:0; padding:0; font-size: 20px">Qtd: {{ number_format($item->quantidade, 0) }}</p>
                        @if($item->hasCustomImage())
                        <p class="text-xs text-blue-600 mt-1">Imagem personalizada</p>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Acoes --}}
                <div class="p-4 bg-gray-50 border-t">
                    <div class="flex gap-2 flex-wrap">
                        <a href="{{ route('pedidos.show', $pedido) }}"
                           class="flex-1 text-center px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
                            Ver Detalhes
                        </a>

                        @if(auth()->user()->canChangeStatus())
                            @if($pedido->status == 'aberto')
                            <button onclick="alterarStatus({{ $pedido->id }}, 'em_producao')"
                                    class="flex-1 px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                                Iniciar Producao
                            </button>
                            @elseif($pedido->status == 'em_producao')
                            <button onclick="alterarStatus({{ $pedido->id }}, 'finalizado')"
                                    class="flex-1 px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm">
                                Finalizar
                            </button>
                            @endif
                        @endif

                        @if(auth()->user()->canSendWhatsApp())
                        {{-- Botao WhatsApp --}}
                        <button onclick="enviarWhatsApp({{ $pedido->id }}, '{{ $pedido->numero }}')"
                                id="btn-whatsapp-{{ $pedido->id }}"
                                class="px-3 py-2 {{ $pedido->enviado_whatsapp ? 'bg-gray-400' : 'bg-green-600 hover:bg-green-700' }} text-white rounded text-sm flex items-center gap-1"
                                title="{{ $pedido->enviado_whatsapp ? 'Enviado em ' . ($pedido->data_envio_whatsapp ? $pedido->data_envio_whatsapp->format('d/m H:i') : '') : 'Enviar para WhatsApp' }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            @if($pedido->enviado_whatsapp)
                                <span class="text-xs">Enviado</span>
                            @endif
                        </button>
                        @endif

                        @if(auth()->user()->canDeleteOrders())
                        <button onclick="excluirPedido({{ $pedido->id }}, '{{ $pedido->numero }}')"
                                class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm"
                                title="Excluir pedido">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
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

    {{-- Paginacao --}}
    <div class="mt-8">
        {{ $pedidos->withQueryString()->links() }}
    </div>
</div>

@if(auth()->user()->isSuperAdmin())
{{-- Botoes Flutuantes (Super Admin) --}}
<div id="floatingButtons" class="fixed bottom-6 right-6 flex flex-col gap-3 z-50">
    {{-- Botao Selecionar --}}
    <button id="btnModoSelecao"
            onclick="toggleModoSelecao()"
            class="w-14 h-14 bg-gray-600 text-white rounded-full shadow-lg hover:bg-gray-700 flex items-center justify-center transition-all duration-300"
            title="Selecionar Multiplos">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
    </button>

    {{-- Contador de selecionados --}}
    <div id="contadorSelecionados" class="hidden bg-gray-800 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg text-center">
        0 selecionados
    </div>

    {{-- Botao Enviar WhatsApp --}}
    <button id="btnEnviarWhatsAppSelecionados"
            onclick="enviarWhatsAppSelecionados()"
            class="hidden w-14 h-14 bg-green-600 text-white rounded-full shadow-lg hover:bg-green-700 flex items-center justify-center transition-all duration-300"
            title="Enviar para WhatsApp">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </button>

    {{-- Botao Excluir Selecionados --}}
    <button id="btnExcluirSelecionados"
            onclick="excluirSelecionados()"
            class="hidden w-14 h-14 bg-red-600 text-white rounded-full shadow-lg hover:bg-red-700 flex items-center justify-center transition-all duration-300"
            title="Excluir Selecionados">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
    </button>
</div>
@endif

@push('scripts')
<script>
let modoSelecao = false;

@if(auth()->user()->canChangeStatus())
function alterarStatus(pedidoId, novoStatus) {
    if (!confirm(`Confirma a alteracao do status para "${novoStatus.replace('_', ' ')}"?`)) {
        return;
    }

    fetch(`/pedidos/${pedidoId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: novoStatus })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Resposta do servidor:', text);
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const card = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
            const badge = card.querySelector('.status-badge');
            if (badge) {
                badge.className = 'status-badge px-3 py-1 rounded-full text-xs font-semibold ';

                if (novoStatus === 'em_producao') {
                    badge.className += 'bg-blue-100 text-blue-800';
                    badge.textContent = 'Em Producao';
                } else if (novoStatus === 'finalizado') {
                    badge.className += 'bg-green-100 text-green-800';
                    badge.textContent = 'Finalizado';
                }
            }

            const actionsDiv = card.querySelector('.p-4.bg-gray-50.border-t > div');
            let newButtons = '<a href="/pedidos/' + pedidoId + '" class="flex-1 text-center px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">Ver Detalhes</a>';

            if (novoStatus === 'em_producao') {
                newButtons += '<button onclick="alterarStatus(' + pedidoId + ', \'finalizado\')" class="flex-1 px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm">Finalizar</button>';
            }

            actionsDiv.innerHTML = newButtons;
            showNotification('Status atualizado com sucesso!', 'success');
        } else {
            showNotification(data.message || 'Erro ao atualizar status', 'error');
        }
    })
    .catch(error => {
        console.error('Erro completo:', error);
        showNotification('Erro ao atualizar status: ' + error.message, 'error');
    });
}
@endif

@if(auth()->user()->canDeleteOrders())
function excluirPedido(pedidoId, numeroPedido) {
    if (!confirm(`Tem certeza que deseja excluir o Pedido #${numeroPedido}?\n\nEsta acao nao pode ser desfeita!`)) {
        return;
    }

    fetch(`/pedidos/${pedidoId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
            card.style.transition = 'opacity 0.3s';
            card.style.opacity = '0';
            setTimeout(() => {
                card.remove();
            }, 300);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Erro ao excluir pedido', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao excluir pedido', 'error');
    });
}
@endif

@if(auth()->user()->isSuperAdmin())
function toggleModoSelecao() {
    modoSelecao = !modoSelecao;
    const checkboxes = document.querySelectorAll('.pedido-checkbox');
    const btnModoSelecao = document.getElementById('btnModoSelecao');
    const btnExcluir = document.getElementById('btnExcluirSelecionados');
    const btnWhatsApp = document.getElementById('btnEnviarWhatsAppSelecionados');
    const contador = document.getElementById('contadorSelecionados');

    checkboxes.forEach(checkbox => {
        if (modoSelecao) {
            checkbox.classList.remove('hidden');
        } else {
            checkbox.classList.add('hidden');
            checkbox.checked = false;
        }
    });

    if (modoSelecao) {
        btnModoSelecao.classList.remove('bg-gray-600', 'hover:bg-gray-700');
        btnModoSelecao.classList.add('bg-red-600', 'hover:bg-red-700');
        btnModoSelecao.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        `;
        btnModoSelecao.title = 'Cancelar Selecao';
    } else {
        btnModoSelecao.classList.remove('bg-red-600', 'hover:bg-red-700');
        btnModoSelecao.classList.add('bg-gray-600', 'hover:bg-gray-700');
        btnModoSelecao.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
        `;
        btnModoSelecao.title = 'Selecionar Multiplos';
        btnExcluir.classList.add('hidden');
        btnWhatsApp.classList.add('hidden');
        contador.classList.add('hidden');
    }

    atualizarContagemSelecionados();
}

function atualizarContagemSelecionados() {
    const checkboxes = document.querySelectorAll('.pedido-checkbox:checked');
    const btnExcluir = document.getElementById('btnExcluirSelecionados');
    const btnWhatsApp = document.getElementById('btnEnviarWhatsAppSelecionados');
    const contador = document.getElementById('contadorSelecionados');

    if (checkboxes.length > 0 && modoSelecao) {
        contador.classList.remove('hidden');
        contador.textContent = `${checkboxes.length} selecionado${checkboxes.length > 1 ? 's' : ''}`;
        btnExcluir.classList.remove('hidden');
        btnWhatsApp.classList.remove('hidden');
        btnWhatsApp.title = `Enviar ${checkboxes.length} para WhatsApp`;
        btnExcluir.title = `Excluir ${checkboxes.length} selecionado${checkboxes.length > 1 ? 's' : ''}`;
    } else {
        contador.classList.add('hidden');
        btnExcluir.classList.add('hidden');
        btnWhatsApp.classList.add('hidden');
    }
}

function excluirSelecionados() {
    const checkboxes = document.querySelectorAll('.pedido-checkbox:checked');
    const pedidosIds = Array.from(checkboxes).map(cb => cb.getAttribute('data-pedido-id'));

    if (pedidosIds.length === 0) {
        showNotification('Nenhum pedido selecionado', 'error');
        return;
    }

    if (!confirm(`Tem certeza que deseja excluir ${pedidosIds.length} pedido(s)?\n\nEsta acao nao pode ser desfeita!`)) {
        return;
    }

    fetch('/pedidos/excluir-multiplos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ pedidos: pedidosIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            pedidosIds.forEach(id => {
                const card = document.querySelector(`[data-pedido-id="${id}"]`);
                if (card) {
                    card.style.transition = 'opacity 0.3s';
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                    }, 300);
                }
            });
            showNotification(data.message, 'success');
            toggleModoSelecao();
        } else {
            showNotification(data.message || 'Erro ao excluir pedidos', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao excluir pedidos', 'error');
    });
}

async function enviarWhatsAppSelecionados() {
    const checkboxes = document.querySelectorAll('.pedido-checkbox:checked');
    const pedidos = Array.from(checkboxes).map(cb => {
        const card = cb.closest('.pedido-card');
        const numero = card.querySelector('h3').textContent.replace('Pedido #', '').trim();
        return {
            id: cb.getAttribute('data-pedido-id'),
            numero: numero
        };
    });

    if (pedidos.length === 0) {
        showNotification('Nenhum pedido selecionado', 'error');
        return;
    }

    if (!confirm(`Enviar ${pedidos.length} pedido(s) para o WhatsApp?\n\nOs pedidos serao enviados em sequencia (fila).`)) {
        return;
    }

    const btnWhatsApp = document.getElementById('btnEnviarWhatsAppSelecionados');
    btnWhatsApp.disabled = true;

    let enviados = 0;
    const MAX_TENTATIVAS = 3;

    for (let i = 0; i < pedidos.length; i++) {
        const pedido = pedidos[i];
        let sucesso = false;
        let tentativa = 0;
        let ultimoErro = '';

        while (!sucesso && tentativa < MAX_TENTATIVAS) {
            tentativa++;

            btnWhatsApp.innerHTML = `
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ${tentativa > 1 ? `Retry ${tentativa}/${MAX_TENTATIVAS} - ` : ''}#${pedido.numero} (${i + 1}/${pedidos.length})
            `;
            btnWhatsApp.classList.remove('bg-green-600', 'bg-red-600');
            btnWhatsApp.classList.add(tentativa > 1 ? 'bg-orange-500' : 'bg-yellow-500');

            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 90000);

                const response = await fetch(`/whatsapp/pedidos/${pedido.id}/enviar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    signal: controller.signal
                });

                clearTimeout(timeoutId);
                const text = await response.text();
                const jsonMatch = text.match(/\{[\s\S]*\}/);

                if (jsonMatch) {
                    const data = JSON.parse(jsonMatch[0]);
                    if (data.success) {
                        sucesso = true;
                        enviados++;

                        const btnIndividual = document.getElementById(`btn-whatsapp-${pedido.id}`);
                        if (btnIndividual) {
                            btnIndividual.classList.remove('bg-green-600', 'hover:bg-green-700');
                            btnIndividual.classList.add('bg-gray-400');
                            btnIndividual.innerHTML = `
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                <span class="text-xs">Enviado</span>
                            `;
                        }

                        showNotification(`Pedido #${pedido.numero} enviado!`, 'success');
                    } else {
                        ultimoErro = data.message || 'Erro desconhecido';
                        console.warn(`Tentativa ${tentativa} falhou para #${pedido.numero}:`, ultimoErro);
                    }
                } else {
                    ultimoErro = 'Resposta invalida do servidor';
                    console.warn(`Tentativa ${tentativa} falhou para #${pedido.numero}: resposta invalida`);
                }
            } catch (error) {
                ultimoErro = error.name === 'AbortError' ? 'Timeout - demorou demais' : error.message;
                console.error(`Tentativa ${tentativa} falhou para #${pedido.numero}:`, error);
            }

            if (!sucesso && tentativa < MAX_TENTATIVAS) {
                showNotification(`Pedido #${pedido.numero} falhou, tentando novamente (${tentativa + 1}/${MAX_TENTATIVAS})...`, 'error');
                await new Promise(resolve => setTimeout(resolve, 5000));
            }
        }

        if (!sucesso) {
            showNotification(`ERRO no pedido #${pedido.numero} apos ${MAX_TENTATIVAS} tentativas: ${ultimoErro} - FILA PARADA!`, 'error');
            btnWhatsApp.disabled = false;
            btnWhatsApp.classList.remove('bg-yellow-500', 'bg-orange-500');
            btnWhatsApp.classList.add('bg-red-600');
            btnWhatsApp.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Parado em #${pedido.numero}
            `;
            return;
        }

        if (i < pedidos.length - 1) {
            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    }

    btnWhatsApp.disabled = false;
    btnWhatsApp.classList.remove('bg-yellow-500', 'bg-orange-500');
    btnWhatsApp.classList.add('bg-green-600');

    showNotification(`Concluido! ${enviados} pedido(s) enviado(s) com sucesso!`, 'success');
    toggleModoSelecao();
}
@endif

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

@if(auth()->user()->canSendWhatsApp())
function enviarWhatsApp(pedidoId, numeroPedido) {
    const btn = document.getElementById(`btn-whatsapp-${pedidoId}`);
    const originalContent = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = `
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-xs">Enviando...</span>
    `;
    btn.classList.remove('bg-green-600', 'hover:bg-green-700');
    btn.classList.add('bg-yellow-500');

    fetch(`/whatsapp/pedidos/${pedidoId}/enviar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.text())
    .then(text => {
        const jsonMatch = text.match(/\{[\s\S]*\}/);
        if (jsonMatch) {
            return JSON.parse(jsonMatch[0]);
        }
        throw new Error('Resposta invalida do servidor');
    })
    .then(data => {
        if (data.success) {
            btn.classList.remove('bg-yellow-500', 'bg-green-600', 'hover:bg-green-700');
            btn.classList.add('bg-gray-400');
            btn.innerHTML = `
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                <span class="text-xs">Enviado</span>
            `;
            btn.title = 'Enviado em ' + (data.enviado_em || 'agora');

            let msg = data.message;
            if (data.details) {
                msg += ` (${data.details.images_sent}/${data.details.total_images} imagens)`;
            }
            showNotification(msg, 'success');
        } else {
            btn.disabled = false;
            btn.classList.remove('bg-yellow-500');
            btn.classList.add('bg-green-600', 'hover:bg-green-700');
            btn.innerHTML = originalContent;
            showNotification(data.message || 'Erro ao enviar para WhatsApp', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        btn.disabled = false;
        btn.classList.remove('bg-yellow-500');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
        btn.innerHTML = originalContent;
        showNotification('Erro ao enviar para WhatsApp', 'error');
    });
}
@endif
</script>
@endpush
@endsection
