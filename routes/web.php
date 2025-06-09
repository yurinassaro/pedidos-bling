<?php
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClickUpController;
use App\Http\Controllers\ImportController;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/', [OrderController::class, 'index'])->name('orders.index');
// Route::get('/auth', [OrderController::class, 'auth'])->name('bling.auth');
// Route::post('/orders/{orderId}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified',
// ])->group(function () {
//     Route::get('/dashboard', function () {
//         return view('dashboard');
//     })->name('dashboard');
// });

Route::get('/', [OrderController::class, 'index'])->name('orders.index');
//Route::get('/layout', [OrderController::class, 'layout'])->name('orders.index');

Route::get('/auth', [OrderController::class, 'auth'])->name('bling.auth');
Route::get('/callback', [OrderController::class, 'callback'])->name('bling.callback');
Route::post('/orders/{orderId}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
Route::post('/import', [ImportController::class, 'import'])->name('orders.import');
Route::get('/sucesso', function () {
    return 'Callback processado com sucesso!';
});

//Route::get('/clickup/callback', [ClickUpController::class, 'callback'])->name('clickup.callback');
