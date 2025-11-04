#!/bin/bash

# Script para iniciar o servidor Laravel sem avisos de "Broken pipe"

echo "🚀 Iniciando servidor Laravel..."
echo "📍 URL: http://localhost:8000"
echo "⚙️  Suprimindo avisos de 'Broken pipe'"
echo ""

# Inicia o servidor com configuração PHP customizada
php -c php.ini artisan serve
