<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PedidoImportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

// Rotas de autenticação Bling (públicas para OAuth)
Route::get('/auth', [OrderController::class, 'auth'])->name('bling.auth');
Route::get('/callback', [OrderController::class, 'callback'])->name('bling.callback');

// Rota temporária para servir imagens do storage (DESENVOLVIMENTO)
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404, 'Arquivo não encontrado');
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        abort(403, 'Tipo de arquivo não permitido');
    }

    return response()->file($fullPath);
})->where('path', '.*');

// Proxy de imagens para W-API
Route::get('/image-proxy/{hash}.jpg', function ($hash) {
    $url = Cache::get("image_proxy_{$hash}");

    if (!$url) {
        abort(404, 'Imagem não encontrada ou expirada');
    }

    try {
        $response = Http::timeout(30)->get($url);

        if ($response->successful()) {
            return response($response->body())
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=3600');
        }

        abort(404, 'Erro ao buscar imagem');
    } catch (\Exception $e) {
        abort(500, 'Erro ao processar imagem');
    }
})->where('hash', '[a-f0-9]+');

// ============================================================
// ROTAS PROTEGIDAS POR AUTENTICAÇÃO
// ============================================================
Route::middleware(['auth'])->group(function () {

    // Redirecionar home para pedidos
    Route::get('/', function () {
        return redirect()->route('pedidos.index');
    });

    // --------------------------------------------------------
    // ROTAS PARA TODOS OS USUÁRIOS AUTENTICADOS (viewer, admin, super_admin)
    // --------------------------------------------------------
    Route::prefix('pedidos')->group(function () {
        Route::get('/', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    });

    // --------------------------------------------------------
    // ROTAS PARA ADMIN E SUPER_ADMIN (alterar status)
    // --------------------------------------------------------
    Route::middleware(['permission:change_status'])->group(function () {
        Route::patch('/pedidos/{pedido}/status', [PedidoController::class, 'alterarStatus'])
            ->name('pedidos.alterar-status');
    });

    // --------------------------------------------------------
    // ROTAS EXCLUSIVAS PARA SUPER_ADMIN
    // --------------------------------------------------------
    Route::middleware(['role:super_admin'])->group(function () {

        // Importação de pedidos
        Route::prefix('importacao')->group(function () {
            Route::get('/', [PedidoImportController::class, 'index'])->name('pedidos.importacao.index');
            Route::get('/por-numero', [PedidoImportController::class, 'indexPorNumero'])->name('pedidos.importacao.por-numero');
            Route::post('/por-numero/verificar', [PedidoImportController::class, 'verificarIntervalo'])->name('pedidos.importacao.verificar-intervalo');
            Route::post('/importar', [PedidoImportController::class, 'importar'])->name('pedidos.importacao.importar');
            Route::post('/importar-por-numero', [PedidoImportController::class, 'importarPorNumero'])->name('pedidos.importacao.importar-por-numero');
            Route::post('/verificar-nao-importados', [PedidoImportController::class, 'verificarNaoImportados'])->name('pedidos.importacao.verificar');
        });

        // Exclusão de pedidos
        Route::delete('/pedidos/{pedido}', [PedidoController::class, 'destroy'])->name('pedidos.destroy');
        Route::post('/pedidos/excluir-multiplos', [PedidoController::class, 'destroyMultiple'])->name('pedidos.destroy-multiple');

        // Gerenciamento de imagens
        Route::post('/pedidos/{pedido}/itens/{item}/imagem', [PedidoController::class, 'atualizarImagem'])->name('pedidos.atualizar-imagem');
        Route::delete('/pedidos/{pedido}/itens/{item}/imagem', [PedidoController::class, 'removerImagemPersonalizada'])->name('pedidos.remover-imagem');

        // WhatsApp
        Route::prefix('whatsapp')->group(function () {
            Route::post('/pedidos/{pedido}/enviar', [WhatsAppController::class, 'sendOrder'])->name('whatsapp.enviar-pedido');
            Route::get('/status', [WhatsAppController::class, 'checkStatus'])->name('whatsapp.status');
            Route::get('/grupos', [WhatsAppController::class, 'listGroups'])->name('whatsapp.grupos');
        });

        // Gestão de usuários
        Route::prefix('usuarios')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('usuarios.index');
            Route::get('/criar', [UserController::class, 'create'])->name('usuarios.create');
            Route::post('/', [UserController::class, 'store'])->name('usuarios.store');
            Route::get('/{user}/editar', [UserController::class, 'edit'])->name('usuarios.edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('usuarios.update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');
        });
    });
});
