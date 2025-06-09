<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BlingAuthService;
use App\Services\BlingService;
use Illuminate\Support\Facades\Log;

class RefreshBlingToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bling:refresh-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renova o token de acesso do Bling se estiver expirado.';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(BlingService $blingService)
    {
        $this->info('Verificando se o token do Bling precisa ser renovado...');

        if (!$blingService->hasValidToken()) {
            $blingService->refreshToken();
            $this->info('Token atualizado com sucesso!');
            Log::info('Token do Bling atualizado com sucesso pelo agendador.');
        } else {
            $this->info('O token ainda é válido. Nenhuma ação necessária.');
        }
    }
}
