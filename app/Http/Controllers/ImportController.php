<?php

namespace App\Http\Controllers;

use App\Services\ImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    public function import(Request $request)
    {
        try {
            $startDate = $request->query('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->query('end_date', now()->format('Y-m-d'));

            $importedCount = $this->importService->importOrders($startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => "Importação concluída! {$importedCount} pedidos importados.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na importação: ' . $e->getMessage()
            ], 500);
        }
    }
}