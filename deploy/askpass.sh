#!/usr/bin/env bash
# Chamado pelo ssh/scp (via SSH_ASKPASS) para obter a senha do usuário da Locaweb.
# A senha é lida na hora do .Producao_acessos; nada é gravado.
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
python - "$RAIZ/.Producao_acessos" <<'EOF'
import re, sys
s = open(sys.argv[1], encoding='utf-8').read()
i = s.index('[Transferência')
print(re.search(r'^Senha:\s*(\S+)', s[i:s.find('\n[', i + 1)], re.M).group(1))
EOF
