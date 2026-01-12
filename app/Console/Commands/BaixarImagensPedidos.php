<?php

namespace App\Console\Commands;

use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BaixarImagensPedidos extends Command
{
    protected $signature = 'pedidos:baixar-imagens
                            {--inicio= : Número do pedido inicial}
                            {--fim= : Número do pedido final}
                            {--todos : Baixar para todos os pedidos sem imagem local}';

    protected $description = 'Baixa as imagens dos produtos dos pedidos já importados';

    public function handle()
    {
        $inicio = $this->option('inicio');
        $fim = $this->option('fim');
        $todos = $this->option('todos');

        // Buscar pedidos
        $query = Pedido::with('itens');

        if ($inicio && $fim) {
            $query->whereBetween('numero', [$inicio, $fim]);
            $this->info("Buscando pedidos de {$inicio} até {$fim}...");
        } elseif ($todos) {
            // Apenas pedidos que têm itens sem imagem_local
            $query->whereHas('itens', function ($q) {
                $q->whereNull('imagem_local')
                  ->whereNotNull('imagem_original');
            });
            $this->info("Buscando todos os pedidos sem imagem local...");
        } else {
            $this->error('Use --inicio e --fim ou --todos');
            return 1;
        }

        $pedidos = $query->orderBy('numero')->get();
        $this->info("Encontrados {$pedidos->count()} pedidos.");

        if ($pedidos->isEmpty()) {
            $this->info('Nenhum pedido encontrado.');
            return 0;
        }

        $bar = $this->output->createProgressBar($pedidos->count());
        $bar->start();

        $totalBaixadas = 0;
        $totalErros = 0;

        foreach ($pedidos as $pedido) {
            foreach ($pedido->itens as $index => $item) {
                // Pular se já tem imagem local ou não tem imagem original
                if ($item->imagem_local || !$item->imagem_original) {
                    continue;
                }

                $resultado = $this->baixarImagem($item, $pedido->numero, $index);

                if ($resultado) {
                    $totalBaixadas++;
                } else {
                    $totalErros++;
                }

                // Pequeno delay para não sobrecarregar
                usleep(100000); // 0.1 segundo
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Concluído!");
        $this->info("Imagens baixadas: {$totalBaixadas}");
        $this->warn("Erros: {$totalErros}");

        return 0;
    }

    protected function baixarImagem(PedidoItem $item, string $numeroPedido, int $index): bool
    {
        try {
            $url = $item->imagem_original;

            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning("Falha ao baixar imagem do pedido {$numeroPedido}", [
                    'status' => $response->status()
                ]);
                return false;
            }

            // Determinar extensão pelo content-type
            $contentType = $response->header('Content-Type');
            $extension = 'jpg';
            if (str_contains($contentType, 'png')) {
                $extension = 'png';
            } elseif (str_contains($contentType, 'gif')) {
                $extension = 'gif';
            } elseif (str_contains($contentType, 'webp')) {
                $extension = 'webp';
            }

            // Criar nome do arquivo
            $filename = "pedidos/{$numeroPedido}/item_{$index}.{$extension}";

            // Garantir que o diretório existe
            Storage::disk('public')->makeDirectory("pedidos/{$numeroPedido}");

            // Salvar a imagem
            Storage::disk('public')->put($filename, $response->body());

            // Atualizar o item no banco
            $item->update(['imagem_local' => $filename]);

            return true;

        } catch (\Exception $e) {
            Log::error("Erro ao baixar imagem do pedido {$numeroPedido}", [
                'erro' => $e->getMessage()
            ]);
            return false;
        }
    }
}
