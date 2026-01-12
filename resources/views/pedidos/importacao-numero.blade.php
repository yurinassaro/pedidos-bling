@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Importação por Número de Pedido</h1>

    {{-- Filtros por Número --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form id="verificarForm" method="POST" action="{{ route('pedidos.importacao.verificar-intervalo') }}" class="flex gap-4 items-end">
            @csrf
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
            <button type="submit" id="btnVerificar" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Verificar Intervalo
            </button>
        </form>

        <div class="mt-4 text-sm text-gray-600">
            <p>💡 Último pedido importado: <strong>#{{ $numeroInicial - 1 }}</strong>. Busque a partir do próximo.</p>
        </div>
    </div>

    @if($buscaRealizada ?? false)
    {{-- Estatísticas (só mostra após busca) --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
                <p class="text-sm font-medium text-gray-600">Disponíveis</p>
                <p class="text-2xl font-bold text-blue-600">{{ $totalNaoImportado }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 {{ ($totalAntigos ?? 0) > 0 ? 'border-2 border-red-500' : '' }}">
            <div class="text-center">
                <p class="text-sm font-medium text-gray-600">Antigos (+30 dias)</p>
                <p class="text-2xl font-bold text-red-600">{{ $totalAntigos ?? 0 }}</p>
                @if(($totalAntigos ?? 0) > 0)
                <p class="text-xs text-red-500">Excluir do Bling!</p>
                @endif
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
    @elseif($buscaRealizada ?? false)
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-center text-gray-500 py-8">
            Todos os pedidos neste intervalo já foram importados!
        </p>
    </div>
    @endif

    {{-- SEÇÃO DE PEDIDOS ANTIGOS (>30 dias) - Em vermelho --}}
    @if(isset($pedidosAntigos) && count($pedidosAntigos) > 0)
    <div class="bg-red-50 border-2 border-red-500 rounded-lg shadow p-6 mt-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h3 class="text-lg font-bold text-red-700">PEDIDOS ANTIGOS - Excluir do Bling!</h3>
                    <p class="text-sm text-red-600">Estes {{ count($pedidosAntigos) }} pedido(s) têm mais de 30 dias e NÃO serão importados.</p>
                    <p class="text-xs text-red-500 mt-1">Exclua-os do Bling para evitar importações acidentais no futuro.</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-red-200">
                <thead class="bg-red-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">
                            Número
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">
                            Data
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">
                            Dias
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase tracking-wider">
                            Ação
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-red-50 divide-y divide-red-200">
                    @foreach($pedidosAntigos as $pedido)
                    @php
                        $dataPedido = \Carbon\Carbon::parse($pedido['data']);
                        $diasAtras = $dataPedido->diffInDays(now());
                    @endphp
                    <tr class="hover:bg-red-100">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-800">
                            #{{ $pedido['numero'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-700">
                            {{ $pedido['contato']['nome'] ?? 'Não informado' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-700">
                            {{ $dataPedido->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-600 text-white">
                                {{ $diasAtras }} dias atrás
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="https://www.bling.com.br/vendas.php#edit/{{ $pedido['id'] }}"
                               target="_blank"
                               class="inline-flex items-center px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                Abrir no Bling
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @else
    {{-- Estado inicial: apenas formulário --}}
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-center text-gray-500 py-8">
            Defina o intervalo de pedidos e clique em "Verificar Intervalo" para buscar os pedidos disponíveis.
        </p>
    </div>
    @endif

</div>

{{-- Modal de Progresso para Verificar Intervalo --}}
<div id="verificarModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 shadow-xl">
        <div class="text-center">
            <svg class="animate-spin h-12 w-12 text-blue-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Verificando Intervalo...</h3>
            <p class="text-sm text-gray-600 mb-4">Buscando pedidos no Bling. Isso pode levar alguns segundos.</p>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div id="verificarProgressBar" class="bg-blue-500 h-full rounded-full animate-pulse" style="width: 100%"></div>
            </div>
            <p id="verificarProgressText" class="text-xs text-gray-500 mt-2">Consultando API do Bling...</p>
        </div>
    </div>
</div>

{{-- Modal de Progresso para Importação --}}
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

    // Interceptar o formulário de verificar intervalo
    const verificarForm = document.getElementById('verificarForm');
    if (verificarForm) {
        verificarForm.addEventListener('submit', function(e) {
            // Mostrar modal de loading
            document.getElementById('verificarModal').classList.remove('hidden');

            // Desabilitar botão
            const btn = document.getElementById('btnVerificar');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Verificando...';
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

async function importarIntervalo(inicio, fim) {
    if (isImporting) {
        alert('Já existe uma importação em andamento!');
        return;
    }

    const totalPedidos = fim - inicio + 1;

    if (!confirm(`Importar ${totalPedidos} pedido(s) do #${inicio} ao #${fim}?`)) {
        return;
    }

    isImporting = true;
    showProgress(`Preparando importação...`);

    // Desabilitar todos os botões
    document.querySelectorAll('button').forEach(btn => btn.disabled = true);

    let sucessos = 0;
    let erros = 0;
    let naoExistem = 0;
    let pedidosImportados = [];
    let pedidosComErro = [];

    // Importar um por um para mostrar progresso detalhado
    for (let numero = inicio; numero <= fim; numero++) {
        const atual = numero - inicio + 1;
        const percent = Math.round((atual / totalPedidos) * 100);

        updateProgress(percent, `Importando pedido #${numero} (${atual} de ${totalPedidos})`);

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

            // Verificar se a resposta HTTP foi ok
            if (!response.ok) {
                console.error(`HTTP Error ${response.status} ao importar pedido #${numero}`);
                erros++;
                pedidosComErro.push(numero);
                await new Promise(resolve => setTimeout(resolve, 500));
                continue;
            }

            const data = await response.json();

            // Verificar sucesso - pode ter importado ou já existir
            if (data.success) {
                // Verificar se realmente importou algo ou se já existia
                const importados = data.data?.sucesso || 0;
                const jaExistentes = data.data?.ja_existentes || 0;
                const naoEncontrados = data.data?.nao_encontrados || 0;

                if (importados > 0 || jaExistentes > 0) {
                    sucessos++;
                    pedidosImportados.push(numero);
                } else if (naoEncontrados > 0) {
                    // Pedido não existe no Bling - não conta como erro
                    naoExistem++;
                }
            } else {
                erros++;
                pedidosComErro.push(numero);
                console.error(`Erro ao importar pedido #${numero}:`, data.message);
            }

            // Pequeno delay entre requisições para não sobrecarregar
            await new Promise(resolve => setTimeout(resolve, 300));

        } catch (error) {
            erros++;
            pedidosComErro.push(numero);
            console.error(`Erro ao importar pedido #${numero}:`, error);
            // Delay maior em caso de erro
            await new Promise(resolve => setTimeout(resolve, 500));
        }
    }

    isImporting = false;
    hideProgress();
    document.querySelectorAll('button').forEach(btn => btn.disabled = false);

    // Montar mensagem de resultado
    let mensagem = `Importação concluída!\n\n`;
    mensagem += `✓ ${sucessos} pedido(s) importado(s)\n`;

    if (naoExistem > 0) {
        mensagem += `⚠ ${naoExistem} pedido(s) não existem no Bling\n`;
    }

    if (erros > 0) {
        mensagem += `✗ ${erros} pedido(s) com erro\n`;
        if (pedidosComErro.length <= 10) {
            mensagem += `   Pedidos: ${pedidosComErro.join(', ')}`;
        }
    }

    alert(mensagem);

    // Recarregar se importou algum ou para limpar a lista
    window.location.reload();
}

</script>
@endpush
@endsection