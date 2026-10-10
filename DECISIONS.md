# Histórico de decisões — PROJ_PO

Registro datado das decisões técnicas e do que mudou no sistema, com o motivo. Cada entrada traz
o contexto, a decisão, como foi feito, como foi testado e os limites conhecidos.

**Onde estão as decisões anteriores a este arquivo (criado em 10/10/2026):**

- **Modelagem e regras de negócio (01 a 06/10/2026):** seção 8 de
  `analise-modelagem-sistema-eventos-luta.md` — banco MySQL, nomes de tabela em português, sem
  CPF, soft delete com unicidade só na aplicação, juízes por luta, Judô simplificado, sem tempo
  real, permissões do painel por nível, dicas exclusivas do selo, sem blog, entre outras.
- **Publicação e infraestrutura (09/10/2026):** `DEPLOY.md` — Locaweb, scripts de `deploy/`,
  SMTP, Cloudflare em modo SSL "Full", primeiro deploy.
- **Segurança (a partir de 10/10/2026):** `SEGURANCA.md` — método da análise, resultados e status
  de cada item. Este arquivo registra as decisões; o `SEGURANCA.md` mantém o status atualizado.

---

## 2026-10-10 — Análise de segurança do PROJ_PO

**Contexto:** a mesma análise feita no T.E.D. (C:\devfolder\proj_ted\SEGURANCA.md), aplicada ao
PO a pedido do Sandro. O PO usa a mesma hospedagem, o mesmo Cloudflare e a mesma conta de
e-mail do T.E.D.

**Resultado** (detalhes, evidências e status no `SEGURANCA.md`):

- **Graves:** PG1 (limite de login contornável com IP falso — `trustProxies('*')` + origem
  acessível sem o Cloudflare), PG2 (super-admin `admin@email.com`, domínio de terceiros, sem duas
  etapas), PG3 (cadastro e "esqueci a senha" sem limite nem captcha — SMTP usável para spam, e a
  conta é a mesma do T.E.D.), PG4 (comprovantes do selo de verificado na pasta pública).
- **Médios:** PM1 (sem cabeçalhos de segurança), PM2 (`X-Powered-By` com a versão do PHP), PM3
  ("esqueci a senha" revela quem tem conta), PM4 (sem limite geral; palpite e placar dos fãs sem
  limite).
- **Em ordem:** SQL, XSS, permissões do painel por nível e área, atribuição em massa, regra de
  senha em produção (12 caracteres, maiúsculas, minúsculas, números, símbolos e senha vazada),
  troca de e-mail com nova verificação, comandos destrutivos bloqueados em produção, uploads,
  cookies, arquivos sensíveis inacessíveis, `composer audit` limpo.

**Decisão:** corrigir na ordem PG4 → PG2 (Sandro, sem código) → PG3 e PG1 (reaproveitando o
código do T.E.D., com o captcha do Cloudflare — chave do PO criada pelo Sandro em 10/10/2026) →
PM1 a PM4.

---

## 2026-10-10 — Comprovantes do selo de verificado no disco privado (PG4)

**Contexto:** no pedido de selo de verificado, o membro envia um comprovante (PDF, JPG ou PNG),
que pode ser documento de identidade. Ele era gravado no disco de mídia (`MEDIA_DISK=public`),
publicado em `/storage/verificacoes/<nome>`: quem tivesse o nome do arquivo abria o documento sem
login. O nome é aleatório e longo, mas é dado pessoal sensível (LGPD). Em produção e no ambiente
local havia **0 comprovantes** — nada a migrar.

**Decisão:** comprovante sempre no disco privado (`local`, raiz `storage/app/private`, fora da
pasta pública), entregue só pela rota protegida do painel, que já existia e já exigia o nível de
moderador ou super-admin.

**Como:**

- `SolicitacaoVerificacao::DISCO_DOCUMENTO = 'local'` — um lugar só para a regra, com o motivo.
- `Site\SolicitacaoVerificacaoController::store` grava nesse disco (deixou de usar o
  `MediaStorage`, que continua servindo às mídias públicas: fotos, logos, banners).
- `Admin\VerificacaoController::documento` lê desse disco.
- `deploy/montar-pacote.sh` passa a criar `storage/app/private` no pacote.

**Testes:** `SiteTest` confere que o comprovante enviado está no disco privado e **não** no
público; `VerificacaoTest` ganhou um teste que garante que um arquivo deixado no disco público
(onde ficava antes) não é entregue; os testes existentes de download foram ajustados ao disco
novo.

**Sugestão em aberto (não feita):** apagar o comprovante depois que a solicitação é aprovada ou
rejeitada — guardar documento de identidade só pelo tempo necessário é o que a LGPD pede. Depende
de decisão do Sandro sobre quanto tempo manter.
