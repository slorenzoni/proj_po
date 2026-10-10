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

---

## 2026-10-10 — Limites e captcha no cadastro e no "esqueci a senha" (PG3)

**Contexto:** cadastro, "esqueci a senha" e reenvio da verificação mandam e-mail para um
endereço digitado por qualquer pessoa. Sem limite nem captcha, um robô usa o SMTP do sistema para
disparar e-mails a terceiros — e a conta (`contato@vipti.com.br`) é a mesma do T.E.D.: um
bloqueio da Locaweb derruba o e-mail dos dois sistemas.

**Decisão:** o mesmo desenho já publicado no T.E.D., adaptado ao PO.

- **Limites** (`FortifyServiceProvider`): `cadastro` (5/h por IP e 30/h no sistema todo — este
  não depende do IP), `recuperacao-senha` (3/h por e-mail, 10/h por IP, 50/h no sistema; o
  Laravel já segura 1/min por endereço) e `verificacao` (2/min e 6/h por usuário; o padrão do
  Fortify era 6/min). O Fortify não tem opção de limite para cadastro e recuperação: entram pelo
  middleware `LimitarEnviosDaAutenticacao`, no grupo de rotas do Fortify.
- **Captcha do Cloudflare (Turnstile)** no cadastro e no "esqueci a senha"
  (`VerificarCaptcha`, `App\Support\Turnstile`): o token é conferido pelo **servidor**, então um
  robô que acesse a Locaweb direto, sem o Cloudflare, não passa. Com o Cloudflare fora do ar, os
  dois são **recusados**. O login fica de fora por enquanto — entra com o PG1 (no T.E.D. o login
  é aceito quando o Cloudflare está fora).
- **Na tela:** `components/CaptchaCloudflare.vue`. O widget cria sozinho o campo escondido
  `cf-turnstile-response` dentro do `<Form>` do Inertia, então o token vai junto no envio; depois
  de cada envio (`@finish`) o widget gera outro token, porque cada um vale uma vez só.
- **Resposta dos limites:** em vez do 429 cru, volta para a tela com a mensagem no campo ou num
  aviso (toast) — `App\Support\RespostaDeLimite`. O `AuthLayout` passou a ter o `<Toaster />`,
  para o aviso aparecer também nas telas de login, cadastro e verificação.
- **Chaves:** `TURNSTILE_SITE_KEY` e `TURNSTILE_SECRET_KEY` (sem as duas, o captcha fica
  desligado). Reais no `.Producao_acessos` (widget de `po.vipti.com.br`, criado pelo Sandro em
  10/10/2026; secreta conferida com o Cloudflare sem ser exibida). No `.env` local, as chaves de
  teste do Cloudflare (sempre passam); nos testes automatizados, desligado pelo `phpunit.xml`.

**Testes:** `tests/Feature/Auth/SegurancaEnviosDeEmailTest.php` (13: limites de cadastro por IP
e geral, "esqueci a senha" por e-mail, reenvio da verificação, captcha válido, inválido, ausente
e com o Cloudflare fora do ar, chave pública chegando às telas, captcha desligado sem chaves, login
ainda sem captcha). Larastan e Pint nos arquivos novos; `vue-tsc` e build do front-end.

**Pendente:** publicar (o `.env` do servidor precisa receber as duas chaves).

---

## 2026-10-10 — Limite de login por e-mail, captcha no login e IP real (PG1)

**Contexto:** o limite de login contava e-mail + IP, e o sistema confiava em qualquer proxy
(`trustProxies(at: '*')`). Como a Locaweb responde direto pelo IP, sem passar pelo Cloudflare,
quem acessasse por ali podia mandar um `X-Forwarded-For` falso a cada tentativa e zerar o limite
— e também fugir da proteção do Cloudflare.

**Decisão:** o mesmo desenho já publicado no T.E.D.

- **Limite só por e-mail** (`FortifyServiceProvider`, limitador `login`): além de 5/min por
  e-mail + IP, 20/h por e-mail. Trocar de IP não adianta. Efeito colateral aceito: 20 senhas
  erradas numa hora travam aquele e-mail por até 1 hora, mesmo para o dono. Ao estourar, volta
  para o login com a mensagem no campo (antes: 429 cru).
- **Captcha do Cloudflare no login** (`VerificarCaptcha`, rota `login.store`, e a tela
  `pages/auth/Login.vue`). Com o Cloudflare fora do ar o login é **aceito** (ninguém fica
  trancado; o limite por e-mail continua valendo) — "fora do ar" é a chamada do servidor ao
  Cloudflare que falha, nunca token vazio.
- **IP real:** `trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_PROTO)` em
  `bootstrap/app.php` — confia no proxy só para o protocolo. Base: o diagnóstico feito no T.E.D.
  (mesmo servidor e mesmo proxy da Locaweb), em que o `REMOTE_ADDR` já é o IP real pelo
  Cloudflare e direto, e o `X-Forwarded-For` chega falsificado no acesso direto. Vale para os
  limites por IP e para o IP gravado na auditoria (`AuditContext`).

**Testes:** `tests/Feature/Auth/SegurancaLoginTest.php` (10: bloqueio por e-mail trocando de IP
a cada tentativa, 5/min do mesmo IP, `X-Forwarded-For` falso ignorado, IPv6 mantido, limite por
IP não furado pelo cabeçalho, captcha válido, inválido e ausente, Cloudflare fora do ar e com
erro 5xx, chave pública na tela). O teste do kit inicial `users are rate limited`
(`AuthenticationTest`) dependia do formato interno da chave e do 429 — foi reescrito para o
comportamento novo. O teste provisório "login ainda não passa pelo captcha" saiu do
`SegurancaEnviosDeEmailTest`.

**Se a hospedagem mudar:** refazer o diagnóstico de IP antes de mexer na regra de proxies.

---

## 2026-10-10 — Itens médios PM2, PM3 e PM4

Pedido do Sandro: fazer agora o PM2, o PM3 e o PM4; o PM1 (cabeçalhos e CSP) fica para depois.

- **PM2 — versão do PHP exposta:** middleware global `CabecalhosDeSeguranca` (em
  `bootstrap/app.php`) remove o `X-Powered-By: PHP/8.5.7`. O `expose_php` não pode ser desligado
  na hospedagem compartilhada. É nesse middleware que entram os cabeçalhos do PM1.
- **PM3 — descobrir quem tem conta:** `App\Http\Responses\PedidoDeRedefinicaoNaoAtendido`
  (ligado no `FortifyServiceProvider`) responde ao "esqueci a senha" de e-mail inexistente — e ao
  pedido repetido em menos de 1 minuto, que o Laravel só segura para e-mail existente — exatamente
  como no sucesso. Frase nova em `lang/pt_BR/passwords.php` ('sent'): "Se este e-mail estiver
  cadastrado, enviamos...". Não tratado: o cadastro diz que o e-mail já está em uso — aceito
  (corrigir mudaria o fluxo de cadastro); captcha e limites deixam a varredura cara.
- **PM4 — limites gerais** (`AppServiceProvider::configurarLimitesGerais`): `throttle:usuario`
  (120/min por usuário) nos grupos logados do site, das configurações e do painel; `throttle:votos`
  (20/min e 300/h por usuário) no palpite e no placar dos fãs, que não tinham limite (comentários e
  dicas já tinham 10/min). O limite geral devolve a página 429; o dos votos volta para a luta com
  um aviso (toast).

**Testes:** `tests/Feature/SegurancaMediosTest.php` (7: sem `X-Powered-By`, resposta igual com e
sem conta, pedido repetido, e-mail inválido recusado, 120/min por usuário sem afetar outro
usuário, limites do palpite e do placar dos fãs com o aviso). Larastan e Pint nos arquivos
alterados.

