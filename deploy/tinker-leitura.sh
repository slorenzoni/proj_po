#!/usr/bin/env bash
# Roda código no tinker com o banco em modo SOMENTE LEITURA: a conexão MySQL é aberta com
# "SET SESSION TRANSACTION READ ONLY" (inclusive em reconexões), então INSERT, UPDATE,
# DELETE e DDL falham no próprio banco. Se o modo não for confirmado, nada é executado.
#
# Por padrão consulta PRODUÇÃO (via artisan-producao.sh, sem SSH); --local usa o banco local.
#
# Exemplos:
#   deploy/tinker-leitura.sh 'echo App\Models\User::count();'
#   deploy/tinker-leitura.sh --local 'dump(App\Models\Evento::latest()->first()?->nome);'
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

alvo=producao
if [ "${1:-}" = "--local" ]; then
    alvo=local
    shift
fi
[ $# -eq 1 ] || { echo "Uso: deploy/tinker-leitura.sh [--local] 'código PHP'" >&2; exit 1; }

# Força o modo leitura na conexão padrão e confere antes de rodar o código pedido.
trava='$conexao = config("database.default");
config(["database.connections.$conexao.options.".PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION TRANSACTION READ ONLY"]);
DB::purge($conexao);
if ((int) DB::scalar("SELECT @@session.transaction_read_only") !== 1) { throw new RuntimeException("Modo somente leitura não confirmado; nada foi executado."); }
'

if [ "$alvo" = producao ]; then
    "$RAIZ/deploy/artisan-producao.sh" tinker --execute "$trava$1"
else
    cd "$RAIZ"
    php artisan tinker --execute "$trava$1"
fi
