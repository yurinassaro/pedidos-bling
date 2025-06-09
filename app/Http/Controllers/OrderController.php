<?php

namespace App\Http\Controllers;

use App\Services\BlingAuthService;
use App\Services\BlingService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $blingService;
    protected $authService;

    public function __construct(BlingService $blingService, BlingAuthService $authService)
    {
        $this->blingService = $blingService;
        $this->authService = $authService;
    }

    public function index(Request $request)
    {
        // Verifica se há um token válido, senão tenta renovar
        if (!$this->blingService->hasValidToken()) {
            $this->blingService->refreshToken();
        }

        if (!$this->authService->hasValidToken()) {
            return redirect()->route('bling.auth');
        }

        //return view('orders.index_layout'); 

        $startDate = $request->query('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->format('Y-m-d'));
        $shouldFilter = $request->has('filter');
        $missingSequence = [];
       
        //$orders = $this->blingService->getOrdersWithProductImages($startDate, $endDate);
        
        try {
            $orders = $shouldFilter ? $this->blingService->getOrdersWithProductImages($startDate, $endDate) : [];
            // Recebendo missingSequence do método getOrdersWithProductImages
            if ($shouldFilter && isset($orders['missingSequence'])) {
                $missingSequence = $orders['missingSequence'];
                $orders = $orders['orders']; // Extraindo apenas os pedidos
            }
            return view('orders.index', compact('orders', 'startDate', 'endDate','missingSequence'));
        } catch (\Exception $e) {
            return view('orders.index', [
                'orders' => [],
                'startDate' => $startDate,
                'endDate' => $endDate,
                'missingSequence' => $missingSequence,
            ])->with('error', 'Erro ao carregar pedidos');
        }
    }

    public function layout(Request $request)
    {
        if (!$this->authService->hasValidToken()) {
            return redirect()->route('bling.auth');
        }

        return view('orders.index_layout'); 

        $startDate = $request->query('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->format('Y-m-d'));
        $orders = $this->blingService->getOrdersWithProductImages($startDate, $endDate);
        
        try {
            $orders = $this->blingService->getOrdersWithProductImages($startDate, $endDate);
            return view('orders.index', compact('orders', 'startDate', 'endDate'));
        } catch (\Exception $e) {
            return view('orders.index', [
                'orders' => [],
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])->with('error', 'Erro ao carregar pedidos');
        }
    }


    public function auth()
    {
        $authUrl = $this->authService->getAuthorizationUrl();
        return view('orders.auth', compact('authUrl'));
    }

    public function callback(Request $request)
    {
        //return $this->authService->handleCallback($request);

        try {
            $this->authService->handleCallback($request);
            return redirect()->route('orders.index')
                ->with('success', 'Autenticação realizada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('bling.auth')
                ->with('error', 'Erro na autenticação com o Bling: ' . $e->getMessage());
        }
    }

    // public function updateStatus($orderId)
    // {
    //     try {
    //         $this->blingService->updateOrderStatus($orderId);
    //         return response()->json(['message' => 'Status atualizado com sucesso']);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Erro ao atualizar status'], 400);
    //     }
    // }
}