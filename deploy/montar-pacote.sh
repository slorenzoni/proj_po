#!/usr/bin/env bash
# Monta o pacote de produção em C:\devfolder\proj_po_deploy (fora do repositório):
#   po_app.tar.gz → ~/po_app          (aplicação, vendor sem dependências de desenvolvimento)
#   po.tar.gz     → ~/public_html/po  (conteúdo de public/, com o index.php de produção)
# Não leva .env, testes, documentos, fotos nem dados locais.
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

rm -rf "$PACOTE"
mkdir -p "$PACOTE/po_app"

cd "$RAIZ"
npm run build >/dev/null

# Arquivos versionados (inclui alterações ainda não commitadas da árvore de trabalho).
git ls-files -z --cached --others --exclude-standard | while IFS= read -r -d '' arquivo; do
    case "$arquivo" in
        tests/*|deploy/*|.github/*|*.md|*.xlsx|phpunit.xml|.editorconfig|.gitattributes|pint.json|phpstan.neon) continue ;;
    esac
    [ -f "$arquivo" ] || continue
    mkdir -p "$PACOTE/po_app/$(dirname "$arquivo")"
    cp "$arquivo" "$PACOTE/po_app/$arquivo"
done
cp -r public/build "$PACOTE/po_app/public/build"

# storage/app/private: comprovantes da verificacao (disco privado - SEGURANCA.md, PG4).
for pasta in storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; do
    mkdir -p "$PACOTE/po_app/$pasta"
done

cd "$PACOTE/po_app"
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts >/dev/null 2>&1
rm -f public/hot

# A pasta public vira o conteúdo de public_html/po, com o index.php de produção.
mv "$PACOTE/po_app/public" "$PACOTE/po"
cp "$RAIZ/deploy/index.producao.php" "$PACOTE/po/index.php"
php -l "$PACOTE/po/index.php" >/dev/null

# tar.gz (o zip do PowerShell grava caminhos com "\" e quebra no Linux).
tar -czf "$PACOTE/po_app.tar.gz" -C "$PACOTE/po_app" .
tar -czf "$PACOTE/po.tar.gz" -C "$PACOTE/po" .
ls -la "$PACOTE"/*.tar.gz
