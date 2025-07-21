@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Importação por Número de Pedido</h1>

    {{-- Filtros por Número --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('pedidos.importacao.por-numero') }}" class="flex gap-4 items-end">
            <div>
                <label for="numero_inicial" class="block text-sm font-medium text-gray-700">Número Inicial</label>
                <input type="number" 
                       name="numero_inicial" 
                       id="numero_inicial" 
                       value="{{ $numeroInicial }}"
                       min="1"
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="numero_final" class="block text-sm font-medium text-gray-700">Número Final</label>
                <input type="number" 
                       name="numero_final" 
                       id="numero_final" 
                       value="{{ $numeroFinal }}"
                       min="1"
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Verificar Intervalo
            </button>
        </form>

        <div class="mt-4 text-sm text-gray-600">
            <p>💡 Dica: A API do Bling processa até 100 pedidos por vez. Para melhores resultados, use intervalos de 100 números.</p>
        </div>
    </div>

    {{-- Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center">
                <p class="text-sm font-medium text-gray-600">Intervalo Analisado</p>
                <p class="text-2xl font-bold">{{ $numeroInicial }} - {{ $numeroFinal }}</p>
                <p class="text-sm text-gray-500">{{ $numeroFinal - $numeroInicial + 1 }} números</p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center">
                <p class="text-sm font-medium text-gray-600">Já Importados</p>
                <p class="text-2xl font-bold text-green-600">{{ $totalImportado }}</p>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center">
                <p class="text-sm font-medium text-gray-600">Não Importados</p>
                <p class="text-2xl font-bold text-red-600">{{ $totalNaoImportado }}</p>
            </div>
        </div>
    </div>

    {{-- Lista de Pedidos Não Importados --}}
    @if(isset($pedidosNaoImportados) && count($pedidosNaoImportados) > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Pedidos Disponíveis para Importação ({{ count($pedidosNaoImportados) }})</h3>
            <button onclick="importarTodosSelecionados()" 
                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                </svg>
                Importar Selecionados
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Número
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Itens
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Observações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pedidosNaoImportados as $pedido)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" 
                                   class="pedido-checkbox rounded border-gray-300" 
                                   data-numero="{{ $pedido['numero'] }}"
                                   value="{{ $pedido['numero'] }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            #{{ $pedido['numero'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $pedido['contato']['nome'] ?? 'Não informado' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($pedido['data'])->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ count($pedido['itens'] ?? []) }} item(ns)
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ Str::limit($pedido['observacoesInternas'] ?? '-', 50) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Intervalos Sugeridos --}}
    @if(count($intervalosNaoImportados) > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Intervalos Não Importados</h3>
            <button onclick="importarTodosIntervalos()" 
                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                </svg>
                Importar Todos
            </button>
        </div>

        <div class="space-y-3">
            @foreach($intervalosNaoImportados as $index => $intervalo)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                    <span class="font-medium">
                        Pedidos {{ $intervalo['inicio'] }} até {{ $intervalo['fim'] }}
                    </span>
                    <span class="text-sm text-gray-600 ml-2">
                        ({{ $intervalo['quantidade'] }} {{ $intervalo['quantidade'] == 1 ? 'pedido' : 'pedidos' }})
                    </span>
                </div>
                <button type="button"
                        onclick="importarIntervalo({{ $intervalo['inicio'] }}, {{ $intervalo['fim'] }})" 
                        class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm intervalo-btn"
                        data-intervalo-index="{{ $index }}">
                    Importar
                </button>
            </div>
            @endforeach
        </div>

        <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
            <p class="text-sm text-yellow-800">
                <strong>Nota:</strong> Intervalos grandes serão divididos automaticamente em lotes de 100 pedidos.
            </p>
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-center text-gray-500 py-8">
            ✅ Todos os pedidos neste intervalo já foram importados!
        </p>
    </div>
    @endif
</div>

{{-- Modal de Progresso --}}
<div id="progressModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4">Importando Pedidos...</h3>
        <div class="mb-4">
            <div class="bg-gray-200 rounded-full h-4 overflow-hidden">
                <div id="progressBar" class="bg-blue-500 h-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <p class="text-sm text-gray-600 mt-2 text-center">
                <span id="progressText">0%</span>
            </p>
        </div>
        <div id="progressDetails" class="text-sm text-gray-600"></div>
    </div>
</div>

@push('scripts')
<script>
let isImporting = false;

// Aguardar o DOM carregar completamente
document.addEventListener('DOMContentLoaded', function() {
    // Seleção de todos os checkboxes
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.pedido-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});

// Importar pedidos selecionados
function importarTodosSelecionados() {
    const checkboxes = document.querySelectorAll('.pedido-checkbox:checked');
    const numeros = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (numeros.length === 0) {
        alert('Selecione pelo menos um pedido para importar!');
        return;
    }
    
    // Ordenar os números
    numeros.sort((a, b) => a - b);
    
    // Se for apenas um pedido ou pedidos não sequenciais
    if (numeros.length === 1) {
        // Importar apenas um pedido
        importarIntervalo(numeros[0], numeros[0]);
    } else {
        // Verificar se os números são sequenciais
        let isSequential = true;
        for (let i = 1; i < numeros.length; i++) {
            if (numeros[i] !== numeros[i-1] + 1) {
                isSequential = false;
                break;
            }
        }
        
        if (isSequential) {
            // Se forem sequenciais, importar como intervalo
            if (!confirm(`Importar ${numeros.length} pedido(s) do #${numeros[0]} ao #${numeros[numeros.length-1]}?`)) {
                return;
            }
            importarIntervalo(numeros[0], numeros[numeros.length-1]);
        } else {
            // Se não forem sequenciais, importar em lotes
            if (!confirm(`Importar ${numeros.length} pedido(s) selecionados?`)) {
                return;
            }
            importarPedidosIndividuais(numeros);
        }
    }
}

// Nova função para importar pedidos individuais não sequenciais
async function importarPedidosIndividuais(numeros) {
    isImporting = true;
    showProgress(`Importando ${numeros.length} pedidos...`);
    
    // Desabilitar todos os botões
    document.querySelectorAll('button').forEach(btn => btn.disabled = true);
    
    let sucessos = 0;
    let erros = 0;
    
    for (let i = 0; i < numeros.length; i++) {
        const numero = numeros[i];
        const percent = Math.round(((i + 1) / numeros.length) * 100);
        
        updateProgress(percent, `Importando pedido #${numero} (${i + 1} de ${numeros.length})`);
        
        try {
            const response = await fetch('{{ route("pedidos.importacao.importar-por-numero") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    numero_inicial: numero,
                    numero_final: numero
                })
            });

            const data = await response.json();
            
            if (data.success) {
                sucessos++;
            } else {
                erros++;
                console.error(`Erro ao importar pedido #${numero}:`, data.message);
            }
            
            // Pequeno delay entre requisições
            await new Promise(resolve => setTimeout(resolve, 500));
            
        } catch (error) {
            erros++;
            console.error(`Erro ao importar pedido #${numero}:`, error);
        }
    }
    
    isImporting = false;
    hideProgress();
    
    let mensagem = `Importação concluída!\n`;
    if (sucessos > 0) mensagem += `✓ ${sucessos} pedido(s) importado(s) com sucesso\n`;
    if (erros > 0) mensagem += `✗ ${erros} erro(s) durante a importação`;
    
    alert(mensagem);
    window.location.reload();
}

function showProgress(text = 'Processando...') {
    document.getElementById('progressModal').classList.remove('hidden');
    document.getElementById('progressDetails').textContent = text;
}

function hideProgress() {
    document.getElementById('progressModal').classList.add('hidden');
}

function updateProgress(percent, text) {
    document.getElementById('progressBar').style.width = percent + '%';
    document.getElementById('progressText').textContent = percent + '%';
    if (text) {
        document.getElementById('progressDetails').textContent = text;
    }
}

function importarIntervalo(inicio, fim) {
    if (isImporting) {
        alert('Já existe uma importação em andamento!');
        return;
    }

    if (!confirm(`Importar pedidos ${inicio} até ${fim}?`)) {
        return;
    }

    isImporting = true;
    showProgress(`Importando pedidos ${inicio} até ${fim}...`);

    // Desabilitar todos os botões
    document.querySelectorAll('.intervalo-btn').forEach(btn => btn.disabled = true);

    fetch('{{ route("pedidos.importacao.importar-por-numero") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            numero_inicial: inicio,
            numero_final: fim
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erro ao importar pedidos');
        console.error(error);
    })
    .finally(() => {
        isImporting = false;
        hideProgress();
        document.querySelectorAll('.intervalo-btn').forEach(btn => btn.disabled = false);
    });
}

async function importarTodosIntervalos() {
    if (!confirm('Importar todos os intervalos não importados? Isso pode levar vários minutos.')) {
        return;
    }

    const intervalos = {!! json_encode($intervalosNaoImportados) !!};
    
    if (intervalos.length === 0) {
        alert('Não há intervalos para importar!');
        return;
    }

    isImporting = true;
    document.querySelectorAll('button').forEach(btn => btn.disabled = true);

    for (let i = 0; i < intervalos.length; i++) {
        const intervalo = intervalos[i];
        const percent = Math.round(((i + 1) / intervalos.length) * 100);
        
        updateProgress(percent, `Importando intervalo ${i + 1} de ${intervalos.length}: pedidos ${intervalo.inicio} até ${intervalo.fim}`);

        try {
            const response = await fetch('{{ route("pedidos.importacao.importar-por-numero") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    numero_inicial: intervalo.inicio,
                    numero_final: intervalo.fim
                })
            });

            const data = await response.json();
            
            if (!data.success) {
                console.error(`Erro no intervalo ${intervalo.inicio}-${intervalo.fim}:`, data.message);
            }

            // Aguardar um pouco entre requisições para não sobrecarregar
            await new Promise(resolve => setTimeout(resolve, 1000));

        } catch (error) {
            console.error(`Erro no intervalo ${intervalo.inicio}-${intervalo.fim}:`, error);
        }
    }

    alert('Importação concluída! A página será recarregada.');
    window.location.reload();
}
</script>
@endpush
@endsection