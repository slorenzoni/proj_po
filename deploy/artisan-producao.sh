#!/usr/bin/env bash
# Roda um comando artisan do código LOCAL contra o banco de PRODUÇÃO (MySQL da Locaweb,
# que aceita conexão externa). Não precisa do SSH liberado.
#
# Exemplos:
#   deploy/artisan-producao.sh migrate --pretend      # simula (não grava)
#   deploy/artisan-producao.sh migrate --force
#   deploy/artisan-producao.sh db:seed --force
#   deploy/artisan-producao.sh tinker --execute 'echo App\Models\User::count();'
#
# Atenção: o código local precisa ser o mesmo que está no servidor.
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

export DB_CONNECTION=mysql DB_PORT=3306
DB_HOST="$(acesso 'Banco de dados MySQL' 'Host')"
DB_DATABASE="$(acesso 'Banco de dados MySQL' 'Banco')"
DB_USERNAME="$(acesso 'Banco de dados MySQL' 'Usuário')"
DB_PASSWORD="$(acesso 'Banco de dados MySQL' 'Senha')"
export DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD

# Variáveis do processo têm prioridade sobre o .env local. APP_ENV=production impede o
# seeder de criar o usuário de teste.
export APP_ENV=production APP_DEBUG=false QUEUE_CONNECTION=sync

cd "$RAIZ"
php artisan config:clear >/dev/null
php artisan "$@"
