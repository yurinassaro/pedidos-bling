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
                    <input type="date" id="start_date" name="start_date" value=""
                           class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">Data Final</label>
                    <input type="date" id="end_date" name="end_date" value=""
                           class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                <button type="submit" class="self-end px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                    Filtrar
                </button>
            </form>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div id="" class="bg-white rounded-lg shadow-md overflow-hidden">
                    <h2 class="font-semibold text-lg mb-3 line-clamp-2 text-center">
                        nome pedido e nome do cliente
                      </h3>
                    <div class="relative aspect-square">
                        
                        <img src="https://orgbling.s3.amazonaws.com/b1d1498828cf03ce8bb60d3d85085c27/4b7a193f16d894db9e8dfc407972548c?AWSAccessKeyId=AKIATCLMSGFX4J7TU445&Expires=1733704782&Signature=y10cm%2BWhXQtZwQYNziD4M6PztC8%3D"
                        alt=""
                        class="w-full h-full object-cover">
                    
                        <div class="p-4">                        
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    Pedido #15151
                                </span>
                                <span class="text-sm text-gray-600">
                                    23/07/1992
                                </span>
                            </div> 
                            <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                                Total de Produtos: VALOR TOTAL 2015
                            </h3>
                            <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                                Total: R$ 221,00
                            </h3>                            
                        </div>
                       
                    </div>
                    <div class="relative aspect-square">
                        
                        <img src="IMAGEM"
                        alt=""
                        class="w-full h-full object-cover">
                    
                        <div class="p-4">                        
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    Pedido #15151
                                </span>
                                <span class="text-sm text-gray-600">
                                    23/07/1992
                                </span>
                            </div> 
                            <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                                Total de Produtos: VALOR TOTAL 2015
                            </h3>
                            <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                                Total: R$ 221,00
                            </h3>                            
                        </div>
                       
                    </div>
                    <button hx-post=""
                                    hx-swap="outerHTML"
                                    hx-target=""
                                    class="w-full bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 transition-colors">
                                Pedido Passado
                            </button>
                </div>

        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <div id="" class="bg-white rounded-lg shadow-md overflow-hidden">
                <h2 class="font-semibold text-lg mb-3 line-clamp-2">
                    nome pedido e nome do cliente
                  </h3>
                <div class="relative aspect-square">
                    
                    <img src="IMAGEM"
                    alt=""
                    class="w-full h-full object-cover">
                
                    <div class="p-4">                        
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Pedido #15151
                            </span>
                            <span class="text-sm text-gray-600">
                                23/07/1992
                            </span>
                        </div> 
                        <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                            Total de Produtos: VALOR TOTAL 2015
                        </h3>
                        <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                            Total: R$ 221,00
                        </h3>                            
                    </div>
                   
                </div>
                <div class="relative aspect-square">
                    
                    <img src="IMAGEM"
                    alt=""
                    class="w-full h-full object-cover">
                
                    <div class="p-4">                        
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Pedido #15151
                            </span>
                            <span class="text-sm text-gray-600">
                                23/07/1992
                            </span>
                        </div> 
                        <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                            Total de Produtos: VALOR TOTAL 2015
                        </h3>
                        <h3 class="font-semibold text-lg mb-3 line-clamp-2">
                            Total: R$ 221,00
                        </h3>                            
                    </div>
                   
                </div>
                <button hx-post=""
                                hx-swap="outerHTML"
                                hx-target=""
                                class="w-full bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 transition-colors">
                            Pedido Passado
                        </button>
            </div>

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
