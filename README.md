# Sistema de Integração Bling - API de Pedidos

Sistema Laravel 11 para integração com a API v3 do Bling ERP, focado em importação e gerenciamento de pedidos de venda.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Requisitos](#requisitos)
- [Instalação Local](#instalação-local)
- [Configuração do Ngrok](#configuração-do-ngrok)
- [Configuração no Bling](#configuração-no-bling)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Funcionalidades](#funcionalidades)
- [API e Rotas](#api-e-rotas)
- [Comandos Úteis](#comandos-úteis)
- [Deploy](#deploy)
- [Troubleshooting](#troubleshooting)

## 🎯 Visão Geral

Este sistema sincroniza pedidos do Bling ERP com um banco de dados local, permitindo:
- Importação automática de pedidos por período ou número
- Gerenciamento de status de produção (aberto → em produção → finalizado)
- Upload de imagens personalizadas para itens dos pedidos
- Autenticação OAuth 2.0 com o Bling
- Interface web para gerenciamento

## 📦 Requisitos

- **PHP** >= 8.2
- **Composer** >= 2.0
- **Node.js** >= 18.0
- **NPM** >= 9.0
- **MySQL** ou **PostgreSQL**
- **Ngrok** (para desenvolvimento local)
- **Conta no Bling** com aplicativo OAuth configurado

## 🚀 Instalação Local

### 1. Clone o Repositório

```bash
git clone [URL_DO_REPOSITORIO]
cd api-bling-pedidos
```

### 2. Instale as Dependências

```bash
# Dependências PHP
composer install

# Dependências JavaScript
npm install
```

### 3. Configure o Ambiente

```bash
# Copie o arquivo de exemplo
cp .env.example .env
```

### 4. Edite o arquivo `.env`

```env
# Configuração do Banco de Dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bling_pedidos
DB_USERNAME=root
DB_PASSWORD=

# Configuração do Bling OAuth (temporário para desenvolvimento)
BLING_CLIENT_ID=seu_client_id_aqui
BLING_CLIENT_SECRET=seu_client_secret_aqui
BLING_REDIRECT_URL=http://localhost:8000/callback  # Será atualizado com ngrok
BLING_WEBHOOK_URL=http://localhost:8000/callback   # Será atualizado com ngrok

# Configuração da Aplicação
APP_NAME="Bling Pedidos"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000  # Será atualizado com ngrok
```

### 5. Gere a Chave da Aplicação

```bash
php artisan key:generate
```

### 6. Crie o Banco de Dados

```sql
CREATE DATABASE bling_pedidos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Execute as Migrations

```bash
php artisan migrate
```

### 8. Configure o Storage

```bash
# Crie o link simbólico para o storage
php artisan storage:link
```

## 🌐 Configuração do Ngrok

O ngrok é necessário para expor sua aplicação local para a internet, permitindo que o Bling faça callbacks OAuth e webhooks.

### 1. Instale o Ngrok

```bash
# macOS (via Homebrew)
brew install ngrok

# Windows (via Chocolatey)
choco install ngrok

# Linux (download direto)
wget https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-amd64.tgz
tar -xvf ngrok-v3-stable-linux-amd64.tgz
sudo mv ngrok /usr/local/bin
```

### 2. Crie uma conta no Ngrok (opcional, mas recomendado)

1. Acesse https://ngrok.com e crie uma conta gratuita
2. Obtenha seu token de autenticação
3. Configure o token:

```bash
ngrok config add-authtoken SEU_TOKEN_AQUI
```

### 3. Inicie o Servidor Laravel

```bash
# Em um terminal
php artisan serve
```

### 4. Inicie o Ngrok

```bash
# Em outro terminal
ngrok http 8000
```

### 5. Copie a URL do Ngrok

O ngrok mostrará algo assim:

```
Session Status                online
Account                       seu-email@example.com
Version                       3.5.0
Region                        United States (us)
Latency                       78ms
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123.ngrok-free.app -> http://localhost:8000
```

**Copie a URL HTTPS** (exemplo: `https://abc123.ngrok-free.app`)

### 6. Atualize o `.env` com a URL do Ngrok

```env
APP_URL=https://abc123.ngrok-free.app
BLING_REDIRECT_URL=https://abc123.ngrok-free.app/callback
BLING_WEBHOOK_URL=https://abc123.ngrok-free.app/callback
```

### 7. Limpe o Cache de Configuração

```bash
php artisan config:clear
php artisan cache:clear
```

## 🔧 Configuração no Bling

### 1. Acesse o Portal de Desenvolvedores do Bling

1. Entre em https://developer.bling.com.br
2. Faça login com sua conta Bling

### 2. Crie ou Configure seu Aplicativo

1. Clique em "Meus aplicativos"
2. Crie um novo aplicativo ou edite o existente
3. Configure os seguintes campos:

```
Nome do Aplicativo: Bling Pedidos Integration
Descrição: Sistema de importação e gerenciamento de pedidos

URLs de Redirecionamento (Callback):
- https://abc123.ngrok-free.app/callback  (sua URL do ngrok)

Escopos necessários:
- Pedidos de Venda (Leitura e Escrita)
- Produtos (Leitura)

Webhook URL (se disponível):
- https://abc123.ngrok-free.app/callback
```

### 3. Copie as Credenciais

1. Copie o **Client ID**
2. Copie o **Client Secret**
3. Atualize no seu `.env`:

```env
BLING_CLIENT_ID=b3558e5db7b2024a3b9c28223804073f31782e2e
BLING_CLIENT_SECRET=f662868b5f90d0c89626baf292b1e306f247ba2a8eda19355727d85faaab
```

## 🏃‍♂️ Executando o Sistema

### 1. Inicie todos os serviços

```bash
# Opção 1: Use o script fornecido
chmod +x serve.sh
./serve.sh

# Opção 2: Inicie manualmente cada serviço
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Ngrok
ngrok http 8000

# Terminal 3: Queue Worker (opcional)
php artisan queue:listen --tries=1

# Terminal 4: Frontend (se usar Vite)
npm run dev
```

### 2. Acesse o Sistema

1. Abra o navegador em `http://localhost:8000`
2. Ou use a URL do ngrok: `https://abc123.ngrok-free.app`

### 3. Primeira Autenticação

1. Clique em "Importar Pedidos"
2. O sistema redirecionará para o Bling
3. Faça login e autorize o aplicativo
4. Você será redirecionado de volta ao sistema
5. O token OAuth será salvo automaticamente

## 📁 Estrutura do Projeto

```
api-bling-pedidos/
├── app/
│   ├── Http/Controllers/
│   │   ├── PedidoController.php          # Gerenciamento local de pedidos
│   │   ├── PedidoImportController.php    # Importação do Bling
│   │   └── OrderController.php           # Autenticação OAuth
│   ├── Models/
│   │   ├── Pedido.php                    # Model principal
│   │   ├── PedidoItem.php               # Itens dos pedidos
│   │   └── BlingToken.php               # Tokens OAuth
│   ├── Services/
│   │   ├── BlingAuthService.php         # OAuth com Bling
│   │   ├── BlingService.php             # API do Bling
│   │   └── PedidoImportService.php      # Lógica de importação
│   └── Console/Commands/
│       └── ImportarPedidosPorNumero.php  # Comando CLI
├── database/
│   └── migrations/                       # Estrutura do banco
├── resources/
│   └── views/
│       └── pedidos/                      # Interface web
├── routes/
│   └── web.php                          # Rotas da aplicação
├── .env.example                         # Exemplo de configuração
├── serve.sh                             # Script de inicialização
└── README.md                            # Esta documentação
```

## ⚙️ Funcionalidades

### 📥 Importação de Pedidos

#### Por Período
- Acesse `/importacao`
- Selecione data inicial e final
- Sistema importa pedidos com status "não faturado"
- Busca automaticamente imagens dos produtos

#### Por Número
- Acesse `/importacao/por-numero`
- Informe intervalo de números
- Sistema identifica gaps na sequência
- Útil para recuperar pedidos específicos

### 📊 Gerenciamento de Status

Estados disponíveis:
1. **Aberto** - Pedido recém importado
2. **Em Produção** - Sendo processado
3. **Finalizado** - Pedido concluído

### 🖼️ Imagens Personalizadas

- Upload de imagem customizada por item
- Sistema mantém imagem original do Bling
- Permite alternar entre original e personalizada

### 🗑️ Exclusão em Lote

- Seleção múltipla de pedidos
- Exclusão com confirmação
- Remove imagens associadas

## 🛠️ API e Rotas

### Rotas Principais

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/` | Redireciona para `/pedidos` |
| GET | `/pedidos` | Lista todos os pedidos |
| GET | `/pedidos/{id}` | Detalhes do pedido |
| PATCH | `/pedidos/{id}/status` | Altera status |
| DELETE | `/pedidos/{id}` | Exclui pedido |
| GET | `/importacao` | Tela de importação por data |
| POST | `/importacao/importar` | Executa importação |
| GET | `/auth` | Inicia OAuth com Bling |
| GET | `/callback` | Callback OAuth |

### Filtros Disponíveis

```
GET /pedidos?status=aberto&numero_inicial=8000&data_inicio=2024-01-01
```

## 🎮 Comandos Úteis

### Artisan Commands

```bash
# Importar pedidos por número via CLI
php artisan bling:importar-numeros 8000 8500

# Importar com batch customizado
php artisan bling:importar-numeros 8000 8500 --batch=50

# Renovar token OAuth
php artisan bling:refresh-token

# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Comandos de Desenvolvimento

```bash
# Criar novo controller
php artisan make:controller NomeController

# Criar nova migration
php artisan make:migration create_table_name

# Criar novo model
php artisan make:model NomeModel -m

# Tinker (REPL)
php artisan tinker
```

## 🚀 Deploy

### Preparação para Produção

1. **Configure variáveis de ambiente de produção**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com.br

# Use URL definitiva no Bling
BLING_REDIRECT_URL=https://seu-dominio.com.br/callback
BLING_WEBHOOK_URL=https://seu-dominio.com.br/callback
```

2. **Otimize a aplicação**

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

3. **Configure o banco de dados de produção**

4. **Configure HTTPS (obrigatório para OAuth)**

5. **Configure Queue Worker (supervisor)**

```ini
[program:bling-pedidos]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/para/artisan queue:work --sleep=3
directory=/caminho/para/projeto
autostart=true
autorestart=true
user=www-data
numprocs=1
```

6. **Configure Cron para renovação de token**

```cron
# Renovar token OAuth a cada 6 horas
0 */6 * * * cd /caminho/para/projeto && php artisan bling:refresh-token
```

### Atualizar URL no Bling

1. Acesse https://developer.bling.com.br
2. Edite seu aplicativo
3. Atualize as URLs de callback para produção
4. Salve as alterações

## 🐛 Troubleshooting

### Token OAuth Expirado

```bash
# Renove manualmente
php artisan bling:refresh-token

# Ou faça novo login
# Acesse /auth no navegador
```

### Ngrok Session Timeout

O ngrok gratuito tem sessão de 2 horas. Quando expirar:
1. Reinicie o ngrok
2. Copie a nova URL
3. Atualize `.env`
4. Atualize no Bling
5. Limpe cache: `php artisan config:clear`

### Erro 500 na Importação

```bash
# Verifique logs
tail -f storage/logs/laravel.log

# Verifique permissões
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Imagens não Aparecem

```bash
# Recrie link simbólico
php artisan storage:link

# Verifique permissões
chmod -R 775 storage/app/public
```

### Rate Limit do Bling

- Sistema já tem delays entre requisições
- Se necessário, aumente em `BlingService.php`:

```php
usleep(200000); // Aumentar para 200ms
```

## 📝 Notas Importantes

1. **Segurança**: Nunca commite o arquivo `.env`
2. **Backup**: Faça backup regular do banco de dados
3. **Logs**: Monitore `storage/logs/laravel.log`
4. **Rate Limits**: Bling limita requisições por minuto
5. **Webhook**: Ainda não implementado completamente
6. **OAuth**: Token expira, sistema renova automaticamente

## 🤝 Contribuindo

1. Faça fork do projeto
2. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
3. Commit: `git commit -m 'Add nova funcionalidade'`
4. Push: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

## 📄 Licença

[Especificar licença]

## 👥 Contato

[Suas informações de contato]

---

**Desenvolvido com Laravel 11 e integração Bling API v3**
