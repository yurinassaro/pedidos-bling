#!/bin/bash

# Script de Deploy para Bling Pedidos
# Uso: ./deploy.sh [comando]
# Comandos: build, up, down, restart, logs, migrate, super-admin, shell

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Arquivo docker-compose
COMPOSE_FILE="docker-compose.prod.yml"

# Funcoes
print_header() {
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  Bling Pedidos - Deploy${NC}"
    echo -e "${GREEN}========================================${NC}"
}

check_env() {
    if [ ! -f ".env.prod" ]; then
        echo -e "${RED}Erro: .env.prod nao encontrado!${NC}"
        echo -e "${YELLOW}Copie .env.prod.example para .env.prod e configure as variaveis.${NC}"
        exit 1
    fi
}

build() {
    echo -e "${YELLOW}Construindo containers...${NC}"
    docker-compose -f $COMPOSE_FILE build --no-cache
    echo -e "${GREEN}Build concluido!${NC}"
}

up() {
    check_env
    echo -e "${YELLOW}Iniciando containers...${NC}"
    docker-compose -f $COMPOSE_FILE up -d
    echo -e "${GREEN}Containers iniciados!${NC}"
    echo -e "${YELLOW}Aguardando banco de dados ficar pronto...${NC}"
    sleep 10
    migrate
    storage_link
    echo -e "${GREEN}Sistema pronto em http://localhost:9020${NC}"
}

down() {
    echo -e "${YELLOW}Parando containers...${NC}"
    docker-compose -f $COMPOSE_FILE down
    echo -e "${GREEN}Containers parados!${NC}"
}

restart() {
    echo -e "${YELLOW}Reiniciando containers...${NC}"
    docker-compose -f $COMPOSE_FILE restart
    echo -e "${GREEN}Containers reiniciados!${NC}"
}

logs() {
    docker-compose -f $COMPOSE_FILE logs -f
}

migrate() {
    echo -e "${YELLOW}Executando migrations...${NC}"
    docker-compose -f $COMPOSE_FILE exec app php artisan migrate --force
    echo -e "${GREEN}Migrations executadas!${NC}"
}

storage_link() {
    echo -e "${YELLOW}Criando link do storage...${NC}"
    docker-compose -f $COMPOSE_FILE exec app php artisan storage:link || true
    echo -e "${GREEN}Storage linkado!${NC}"
}

create_super_admin() {
    if [ -z "$1" ]; then
        echo -e "${RED}Uso: ./deploy.sh super-admin email@exemplo.com${NC}"
        exit 1
    fi
    echo -e "${YELLOW}Criando Super Admin...${NC}"
    docker-compose -f $COMPOSE_FILE exec app php artisan user:create-super-admin "$1"
    echo -e "${GREEN}Super Admin criado!${NC}"
}

shell() {
    docker-compose -f $COMPOSE_FILE exec app sh
}

clear_cache() {
    echo -e "${YELLOW}Limpando cache...${NC}"
    docker-compose -f $COMPOSE_FILE exec app php artisan config:clear
    docker-compose -f $COMPOSE_FILE exec app php artisan cache:clear
    docker-compose -f $COMPOSE_FILE exec app php artisan route:clear
    docker-compose -f $COMPOSE_FILE exec app php artisan view:clear
    echo -e "${GREEN}Cache limpo!${NC}"
}

optimize() {
    echo -e "${YELLOW}Otimizando para producao...${NC}"
    docker-compose -f $COMPOSE_FILE exec app php artisan config:cache
    docker-compose -f $COMPOSE_FILE exec app php artisan route:cache
    docker-compose -f $COMPOSE_FILE exec app php artisan view:cache
    echo -e "${GREEN}Otimizacao concluida!${NC}"
}

# Menu principal
print_header

case "$1" in
    build)
        build
        ;;
    up)
        up
        ;;
    down)
        down
        ;;
    restart)
        restart
        ;;
    logs)
        logs
        ;;
    migrate)
        migrate
        ;;
    super-admin)
        create_super_admin "$2"
        ;;
    shell)
        shell
        ;;
    clear-cache)
        clear_cache
        ;;
    optimize)
        optimize
        ;;
    *)
        echo "Comandos disponiveis:"
        echo "  build       - Construir containers"
        echo "  up          - Iniciar todos os servicos"
        echo "  down        - Parar todos os servicos"
        echo "  restart     - Reiniciar servicos"
        echo "  logs        - Ver logs em tempo real"
        echo "  migrate     - Executar migrations"
        echo "  super-admin - Criar super admin (./deploy.sh super-admin email)"
        echo "  shell       - Abrir shell no container app"
        echo "  clear-cache - Limpar todos os caches"
        echo "  optimize    - Otimizar para producao"
        ;;
esac
