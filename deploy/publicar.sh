#!/usr/bin/env bash
# Publica o pacote montado por deploy/montar-pacote.sh no servidor da Locaweb.
# PRÉ-REQUISITO: SSH liberado no painel da Locaweb (a liberação dura 3 horas).
#
# Atualiza código e front-end sem tocar em ~/po_app/.env nem nos arquivos enviados pelo
# painel (storage/app/public). Migrations novas: rodar deploy/artisan-producao.sh migrate.
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

[ -f "$PACOTE/po_app.tar.gz" ] && [ -f "$PACOTE/po.tar.gz" ] || { echo "Monte o pacote antes: deploy/montar-pacote.sh" >&2; exit 1; }

echo "== Enviando pacotes"
enviar "$PACOTE/po_app.tar.gz" "$PACOTE/po.tar.gz" "$SSH_USUARIO@$SSH_HOST:~/tmp/"

echo "== Descompactando e finalizando no servidor"
no_servidor "set -e
cd ~/po_app
php84 artisan down --no-interaction >/dev/null 2>&1 || true
tar -xzf ~/tmp/po_app.tar.gz
mkdir -p ~/public_html/po && tar -xzf ~/tmp/po.tar.gz -C ~/public_html/po
rm -f ~/tmp/po_app.tar.gz ~/tmp/po.tar.gz
chmod -R u+rwX,g+rwX storage bootstrap/cache
# storage:link não funciona (symlink() desativado no PHP da Locaweb); ln -s funciona.
ln -sfn $SERVIDOR_APP/storage/app/public ~/public_html/po/storage
php84 artisan config:cache --no-interaction >/dev/null
php84 artisan route:cache --no-interaction >/dev/null
php84 artisan view:cache --no-interaction >/dev/null
php84 artisan up --no-interaction >/dev/null
echo publicado"

echo "== Conferindo o site"
for caminho in "" login eventos up; do
    curl -s -o /dev/null -m 30 -w "%{http_code} /$caminho\n" "https://po.vipti.com.br/$caminho"
done
