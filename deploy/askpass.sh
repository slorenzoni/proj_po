#!/usr/bin/env bash
# Chamado pelo ssh/scp (via SSH_ASKPASS) para obter a senha do usuário da Locaweb.
# A senha é lida na hora do .Producao_acessos; nada é gravado.
# PHP vem do lib.sh (exportado); o padrão abaixo vale se este script for chamado sozinho.
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
"${PHP:-/c/php/8.4/php.exe}" "$RAIZ/deploy/acesso.php" "$RAIZ/.Producao_acessos" 'Transferência' 'Senha' | tr -d '\r'
