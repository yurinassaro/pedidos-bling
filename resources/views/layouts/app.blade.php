<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistema de Pedidos') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js para interatividade -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('pedidos.index') }}" class="text-xl font-bold text-gray-800">
                            Sistema de Pedidos
                        </a>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <!-- Link de Pedidos -->
                        <a href="{{ route('pedidos.index') }}" 
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('pedidos.index') || request()->routeIs('pedidos.show') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 focus:outline-none transition">
                            Pedidos
                        </a>
                        
                        <!-- Dropdown de Importação -->
                        <div class="relative inline-flex items-center" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                    class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('pedidos.importacao.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium leading-5 focus:outline-none transition h-full">
                                Importação
                                <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95"
                                class="absolute left-0 top-full mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                                style="display: none;">
                                <div class="py-1">
                                    <a href="{{ route('pedidos.importacao.index') }}" 
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('pedidos.importacao.index') ? 'bg-gray-100' : '' }}">
                                        Por Data
                                    </a>
                                    <a href="{{ route('pedidos.importacao.por-numero') }}" 
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('pedidos.importacao.por-numero') ? 'bg-gray-100' : '' }}">
                                        Por Número
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="flex items-center sm:hidden">
                    <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition" x-data="{ open: false }">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="sm:hidden" x-data="{ open: false }">
            <div :class="{'block': open, 'hidden': !open}" class="hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <a href="{{ route('pedidos.index') }}" 
                    class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('pedidos.index') ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium focus:outline-none transition">
                        Pedidos
                    </a>
                    <div class="border-l-4 {{ request()->routeIs('pedidos.importacao.*') ? 'border-blue-500 bg-blue-50' : 'border-transparent' }}">
                        <div class="pl-3 pr-4 py-2 text-base font-medium text-gray-600">
                            Importação
                        </div>
                        <a href="{{ route('pedidos.importacao.index') }}" 
                        class="block pl-8 pr-4 py-2 text-sm {{ request()->routeIs('pedidos.importacao.index') ? 'text-blue-700 font-semibold' : 'text-gray-600 hover:text-gray-800' }}">
                            Por Data
                        </a>
                        <a href="{{ route('pedidos.importacao.por-numero') }}" 
                        class="block pl-8 pr-4 py-2 text-sm {{ request()->routeIs('pedidos.importacao.por-numero') ? 'text-blue-700 font-semibold' : 'text-gray-600 hover:text-gray-800' }}">
                            Por Número
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main>
        @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>