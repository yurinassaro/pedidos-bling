<?php

namespace App\Services;

use League\OAuth2\Client\Provider\AbstractProvider;
use GuzzleHttp\Client;

class ClickUpOAuthService
{
    protected $provider;

    public function __construct()
    {
        $this->provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => env('CLICKUP_CLIENT_ID'),
            'clientSecret'            => env('CLICKUP_CLIENT_SECRET'),
            'redirectUri'             => env('CLICKUP_REDIRECT_URI'),
            'urlAuthorize'            => 'https://app.clickup.com/api',
            'urlAccessToken'          => 'https://api.clickup.com/api/v2/oauth/token',
            'urlResourceOwnerDetails' => ''
        ]);
    }

    // Redireciona o usuário para autorizar o aplicativo
    public function getAuthorizationUrl()
    {
        return $this->provider->getAuthorizationUrl();
    }

    // Troca o Authorization Code pelo Access Token
    public function getAccessToken($code)
    {
        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);
    }
}