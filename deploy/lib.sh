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

# Lê um campo ("Host", "Usuário", "Senha"...) de uma seção do .Producao_acessos.
acesso() {
    python - "$ACESSOS" "$1" "$2" <<'EOF'
import re, sys
arquivo, secao, campo = sys.argv[1:]
s = open(arquivo, encoding='utf-8').read()
i = s.index('[' + secao)
trecho = s[i:s.find('\n[', i + 1)]
print(re.search(r'^' + re.escape(campo) + r':\s*(\S+)', trecho, re.M).group(1))
EOF
}

SSH_HOST="$(acesso 'Transferência' 'Host')"
SSH_USUARIO="$(acesso 'Transferência' 'Usuário')"

# O ssh pede a senha ao programa indicado em SSH_ASKPASS (deploy/askpass.sh), que a lê
# do arquivo de acessos. Funciona sem terminal interativo.
export SSH_ASKPASS="$RAIZ/deploy/askpass.sh" SSH_ASKPASS_REQUIRE=force DISPLAY=:0
SSH_OPCOES=(-o "UserKnownHostsFile=$KNOWN_HOSTS" -o StrictHostKeyChecking=accept-new
    -o ConnectTimeout=20 -o PreferredAuthentications=password -o NumberOfPasswordPrompts=1)

# Avisos do OpenSSH sobre o servidor antigo da Locaweb, que não interessam.
filtrar_avisos() { grep -v "post-quantum\|pq.html\|upgraded\|store now" || true; }

no_servidor() { ssh "${SSH_OPCOES[@]}" "$SSH_USUARIO@$SSH_HOST" "$@" 2>&1 | filtrar_avisos; }

enviar() { scp "${SSH_OPCOES[@]}" "$@" 2>&1 | filtrar_avisos; }
