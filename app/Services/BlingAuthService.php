<?php

namespace App\Services;

use App\Models\BlingToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class BlingAuthService
{
    protected $baseUrl = 'https://www.bling.com.br/Api/v3';
    protected $clientId;
    protected $clientSecret;
    protected $redirectUrl;

    public function __construct()
    {
        $this->clientId = Config::get('services.bling.client_id');
        $this->clientSecret = Config::get('services.bling.client_secret');
        $this->redirectUrl = Config::get('services.bling.redirect_url');
    }

    /**
     * Generate the authorization URL for Bling API
     */
    public function getAuthorizationUrl(): string
    {
      
        return "{$this->baseUrl}/oauth/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'state' => $this->generateState(),
            'redirect_uri' => $this->redirectUrl
        ]);
        // dd("{$this->baseUrl}/oauth/authorize?" . http_build_query([
        //     'response_type' => 'code',
        //     'client_id' => $this->clientId,
        //     'state' => $this->generateState(),
        //     'redirect_url' => $this->redirectUrl
        // ]));
    }

    /**
     * Handle the callback from Bling API
     */

     /**
     * Retorna o token mais recente do banco de dados.
     */
    public function getAccessToken()
    {
        $token = BlingToken::latest()->first();

        return $token ? $token->access_token : null;
    }
    
    public function handleCallback(Request $request)
    {
        
        $code = $request->query('code');
        
        if (!$code) {
            throw new \Exception('Código de autorização não fornecido');
        }

        // dd("{$this->baseUrl}/oauth/token", [
        //     'grant_type' => 'authorization_code',
        //     'code' => $code,
        //     'client_id' => $this->clientId,
        //     'client_secret' => $this->clientSecret,
        //     'redirect_uri' => $this->redirectUrl,
        // ]);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}")
        ])->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
        ]);

        Log::info('Resposta do Bling', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        // dd("{$this->baseUrl}/oauth/token", [
        //     'grant_type' => 'authorization_code',
        //     'code' => $code,
        //     'client_id' => $this->clientId,
        //     'client_secret' => $this->clientSecret,
        //     'redirect_uri' => $this->redirectUrl,
        // ]);

        if ($response->failed()) {
            Log::error('Erro ao obter token de acesso:', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'error' => 'Erro ao obter token de acesso',
                'details' => $response->body(),
            ], 400);
        }

        $tokenData = $response->json();

        // Log the token data for debugging purposes
        Log::info('Resposta da API Bling ao trocar o token:', $tokenData);

        // Ensure the `expires_in` field exists before calculating the expiration date
        $expiresAt = isset($tokenData['expires_in']) 
            ? now()->addSeconds($tokenData['expires_in']) 
            : null;

        // Remove existing tokens and save the new one
        BlingToken::query()->delete();
        BlingToken::create([
            'access_token' => $tokenData['access_token'],
            'expires_at' => $expiresAt,
        ]);

        Log::info('Token salvo com sucesso:', [
            'access_token' => $tokenData['access_token'],
            'expires_at' => $expiresAt,
        ]);

        // Redirect to a success page or return a success response
        return redirect('/sucesso')->with('message', 'Token obtido com sucesso!');
    }

    /**
     * Check if a valid token exists in the database
     */
    public function hasValidToken(): bool
    {
        $token = BlingToken::latest()->first();

        if (!$token) {
            Log::info('Nenhum token encontrado no banco.');
            return false;
        }

        if (!$token->expires_at) {
            Log::info('Token encontrado, mas expires_at não está configurado.', ['token' => $token]);
            return false;
        }

        if (!$token->expires_at->isFuture()) {
            Log::info('Token expirado.', ['token' => $token]);
            return false;
        }

        return true;
    }

    /**
     * Generate a random state string for the OAuth flow
     */
    protected function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function refreshToken()
    {
        // Buscar o token atual no banco
        $token = BlingToken::latest()->first();

        if (!$token || !isset($token->refresh_token)) {
            Log::error('Nenhum refresh token disponível.');
            return false;
        }

        // Fazer a requisição para renovar o token
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}")
        ])->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
        ]);

        // Log para depuração
        Log::info('Tentativa de renovar o token do Bling', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        // Verificar se a renovação foi bem-sucedida
        if ($response->failed()) {
            Log::error('Erro ao renovar token:', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        // Salvar o novo token no banco de dados
        $tokenData = $response->json();

        $expiresAt = isset($tokenData['expires_in']) 
            ? now()->addSeconds($tokenData['expires_in']) 
            : null;

        BlingToken::query()->delete();
        BlingToken::create([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'], // Salvar o refresh token
            'expires_at' => $expiresAt,
        ]);

        Log::info('Token atualizado com sucesso!', [
            'access_token' => $tokenData['access_token'],
            'expires_at' => $expiresAt,
        ]);

        return true;
    }
}