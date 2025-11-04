<?php
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PedidoImportController;
use Illuminate\Support\Facades\Route;

// Rotas de autenticação Bling (mantém as existentes)
Route::get('/auth', [OrderController::class, 'auth'])->name('bling.auth');
Route::get('/callback', [OrderController::class, 'callback'])->name('bling.callback');

// Rotas de importação de pedidos
Route::prefix('importacao')->group(function () {
    Route::get('/', [PedidoImportController::class, 'index'])->name('pedidos.importacao.index');
    Route::get('/por-numero', [PedidoImportController::class, 'indexPorNumero'])->name('pedidos.importacao.por-numero');
    Route::post('/importar', [PedidoImportController::class, 'importar'])->name('pedidos.importacao.importar');
    Route::post('/importar-por-numero', [PedidoImportController::class, 'importarPorNumero'])->name('pedidos.importacao.importar-por-numero');
    Route::post('/verificar-nao-importados', [PedidoImportController::class, 'verificarNaoImportados'])->name('pedidos.importacao.verificar');
});

// Rotas de gerenciamento de pedidos
Route::prefix('pedidos')->group(function () {
    Route::get('/', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    Route::patch('/{pedido}/status', [PedidoController::class, 'alterarStatus'])->name('pedidos.alterar-status');
    Route::delete('/{pedido}', [PedidoController::class, 'destroy'])->name('pedidos.destroy');
    Route::post('/excluir-multiplos', [PedidoController::class, 'destroyMultiple'])->name('pedidos.destroy-multiple');
    Route::post('/{pedido}/itens/{item}/imagem', [PedidoController::class, 'atualizarImagem'])->name('pedidos.atualizar-imagem');
    Route::delete('/{pedido}/itens/{item}/imagem', [PedidoController::class, 'removerImagemPersonalizada'])->name('pedidos.remover-imagem');
});

// Redirecionar home para pedidos
Route::get('/', function () {
    return redirect()->route('pedidos.index');
});

// Rota temporária para servir imagens do storage (DESENVOLVIMENTO)
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404, 'Arquivo não encontrado');
    }
    
    // Verificar se é uma imagem
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        abort(403, 'Tipo de arquivo não permitido');
    }
    
    return response()->file($fullPath);
})->where('path', '.*');