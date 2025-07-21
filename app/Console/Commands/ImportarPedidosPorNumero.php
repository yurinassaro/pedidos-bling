<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PedidoImportService;
use App\Services\BlingAuthService;

class ImportarPedidosPorNumero extends Command
{
    protected $signature = 'bling:importar-numeros 
                            {inicio : Número inicial do pedido}
                            {fim : Número final do pedido}
                            {--batch=100 : Tamanho do lote}';

    protected $description = 'Importa pedidos do Bling por intervalo de números';

    protected PedidoImportService $importService;
    protected BlingAuthService $authService;

    public function __construct(PedidoImportService $importService, BlingAuthService $authService)
    {
        parent::__construct();
        $this->importService = $importService;
        $this->authService = $authService;
    }

    public function handle()
    {
        if (!$this->authService->hasValidToken()) {
            $this->error('Token Bling não encontrado ou expirado!');
            return 1;
        }

        $inicio = (int) $this->argument('inicio');
        $fim = (int) $this->argument('fim');
        $batchSize = (int) $this->option('batch');

        $this->info("Importando pedidos de #{$inicio} até #{$fim}");
        $this->info("Tamanho do lote: {$batchSize}");

        $currentStart = $inicio;

        while ($currentStart <= $fim) {
            $currentEnd = min($currentStart + $batchSize - 1, $fim);
            
            $this->info("\nProcessando lote: #{$currentStart} até #{$currentEnd}");
            
            try {
                $resultado = $this->importService->importarPedidosPorIntervalo($currentStart, $currentEnd);
                
                $this->info("✓ Importados: {$resultado['sucesso']}");
                $this->warn("⚠ Já existentes: {$resultado['ja_existentes']}");
                $this->error("✗ Não encontrados: {$resultado['nao_encontrados']}");
                
                if ($resultado['erros'] > 0) {
                    $this->error("✗ Erros: {$resultado['erros']}");
                }

            } catch (\Exception $e) {
                $this->error("Erro no lote: " . $e->getMessage());
            }

            $currentStart = $currentEnd + 1;
            
            // Pausa entre lotes
            if ($currentStart <= $fim) {
                $this->info("Aguardando 2 segundos antes do próximo lote...");
                sleep(2);
            }
        }

        $this->info("\n✅ Importação concluída!");
        return 0;
    }
}