#!/usr/bin/env bash
# Funções comuns dos scripts de deploy do PROJ_PO (Locaweb, po.vipti.com.br).
# Os acessos são lidos na hora do arquivo .Producao_acessos (fora do Git). Nenhum script
# grava senha em arquivo.

set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ACESSOS="$RAIZ/.Producao_acessos"
PACOTE="${PACOTE:-/c/devfolder/proj_po_deploy}"
SERVIDOR_APP='/home/storage/d/66/10/vipti2/po_app'
KNOWN_HOSTS="$HOME/.ssh/known_hosts_locaweb_po"

[ -f "$ACESSOS" ] || { echo "Arquivo de acessos não encontrado: $ACESSOS" >&2; exit 1; }

# PHP e Composer usados pelos scripts. Nenhum dos dois está no PATH do notebook, por isso
# os caminhos são fixos aqui. A versão é a mesma do servidor (php84 na Locaweb), para que
# o código rodado daqui contra a produção e o vendor do pacote batam com o de lá.
# Para usar outro: PHP=/c/php/8.5/php.exe deploy/<script>.sh
PHP="${PHP:-/c/php/8.4/php.exe}"
COMPOSER_PHAR="${COMPOSER_PHAR:-/c/php/composer/composer.phar}"
[ -x "$PHP" ] || { echo "PHP não encontrado: $PHP" >&2; exit 1; }
# O plugin Wayfinder do build (npm run build) chama "php artisan" direto: põe este PHP no PATH.
PATH="$(dirname "$PHP"):$PATH"
export PHP COMPOSER_PHAR PATH
composer() { "$PHP" "$COMPOSER_PHAR" "$@"; }

# Lê um campo ("Host", "Usuário", "Senha"...) de uma seção do .Producao_acessos.
acesso() { "$PHP" "$RAIZ/deploy/acesso.php" "$ACESSOS" "$1" "$2" | tr -d '\r'; }

SSH_HOST="$(acesso 'Transferência' 'Host')"
SSH_USUARIO="$(acesso 'Transferência' 'Usuário')"

# O ssh pede a senha ao programa indicado em SSH_ASKPASS (deploy/askpass.sh), que a lê
# do arquivo de acessos. Funciona sem terminal interativo.
export SSH_ASKPASS="$RAIZ/deploy/askpass.sh" SSH_ASKPASS_REQUIRE=force DISPLAY=:0
SSH_OPCOES=(-o "UserKnownHostsFile=$KNOWN_HOSTS" -o StrictHostKeyChecking=accept-new
    -o ConnectTimeout=20 -o PreferredAuthentications=password -o NumberOfPasswordPrompts=1)

# Avisos do OpenSSH sobre o servidor antigo da Locaweb, que não interessam.
filtrar_avisos() { grep -v "post-quantum\|pq.html\|upgraded\|store now" || true; }

# -n: o ssh não lê a entrada padrão. Sem isso ele fica parado esperando quando o script
# roda sem terminal (por exemplo, chamado por outra ferramenta).
no_servidor() { ssh -n "${SSH_OPCOES[@]}" "$SSH_USUARIO@$SSH_HOST" "$@" 2>&1 | filtrar_avisos; }

enviar() { scp "${SSH_OPCOES[@]}" "$@" 2>&1 | filtrar_avisos; }
