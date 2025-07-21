@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Importação de Pedidos do Bling</h1>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('pedidos.importacao.index') }}" class="flex gap-4 items-end">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Data Inicial</label>
                <input type="date" 
                       name="start_date" 
                       id="start_date" 
                       value="{{ $startDate }}"
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">Data Final</label>
                <input type="date" 
                       name="end_date" 
                       id="end_date" 
                       value="{{ $endDate }}"
                       class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                Buscar Pedidos
            </button>
        </form>
    </div>

    {{-- Resumo da Sequência --}}
    @if(isset($sequencia) && $sequencia['total'] > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Análise da Sequência</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-600">Primeiro Pedido</p>
                <p class="text-2xl font-bold">{{ $sequencia['primeiro'] }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Último Pedido</p>
                <p class="text-2xl font-bold">{{ $sequencia['ultimo'] }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Total Importados</p>
                <p class="text-2xl font-bold text-green-600">{{ $sequencia['total'] }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Faltantes</p>
                <p class="text-2xl font-bold text-red-600">{{ $sequencia['gaps'] }}</p>
            </div>
        </div>

        @if(!empty($sequencia['faltantes']))
        <div class="mt-4">
            <p class="text-sm font-medium text-gray-700 mb-2">Números Faltantes:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($sequencia['faltantes'] as $faltante)
                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">{{ $faltante }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Lista de Pedidos Não Importados --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Pedidos Não Importados ({{ count($pedidosNaoImportados) }})</h3>
            @if(count($pedidosNaoImportados) > 0)
            <button onclick="importarTodos()" 
                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                </svg>
                Importar Todos
            </button>
            @endif
        </div>

        @if(count($pedidosNaoImportados) > 0)
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
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pedidosNaoImportados as $pedido)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" 
                                   class="pedido-checkbox rounded border-gray-300" 
                                   value="{{ $pedido['id'] }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $pedido['numero'] }}
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
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 text-center py-8">Todos os pedidos do período já foram importados!</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Seleção de todos os checkboxes
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.pedido-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
});

// Importar todos os pedidos
function importarTodos() {
    if (!confirm('Deseja importar todos os pedidos não importados?')) {
        return;
    }

    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Importando...';

    fetch('{{ route("pedidos.importacao.importar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            start_date: '{{ $startDate }}',
            end_date: '{{ $endDate }}'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Erro: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg> Importar Todos';
        }
    })
    .catch(error => {
        alert('Erro ao importar pedidos');
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg> Importar Todos';
    });
}
</script>
@endpush
@endsection