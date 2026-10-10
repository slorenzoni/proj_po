# Segurança — método de análise e resultado no PROJ_PO

Este arquivo registra **como** a segurança do PROJ_PO foi analisada em 10/10/2026 e **o que**
foi encontrado. Segue o mesmo molde do `SEGURANCA.md` do T.E.D. (C:\devfolder\proj_ted): a parte
de método (seções 2 e 3) serve para qualquer sistema Laravel; as seções 4 em diante são
específicas do PO.

> **Atenção ao versionar:** a seção 4 descreve falhas **ainda abertas** em produção. Manter o
> repositório privado. Nenhuma senha está escrita aqui; os acessos ficam em `.Producao_acessos`
> (fora do Git).

---

## 1. Contexto do sistema analisado

| Item | Valor |
| --- | --- |
| Sistema | PROJ_PO — eventos de luta: palpites, placar dos fãs, ranking, comentários, dicas, selo de verificado |
| Stack | Laravel 13, Fortify (sem Jetstream), Inertia 3 + Vue 3, MySQL |
| Produção | `https://po.vipti.com.br` — Locaweb (hospedagem compartilhada, mesmo servidor e usuário do T.E.D.), PHP 8.5 no web / 8.4 no terminal, MySQL 5.7 |
| Na frente | Cloudflare (DNS com proxy, "Always Use HTTPS", SSL/TLS em modo **Full**) |
| E-mail | SMTP da Locaweb (`email-ssl.com.br:465`), conta `contato@vipti.com.br` — **a mesma do T.E.D.** |
| Perfis | visitante, cliente (membro), administrador em três níveis: super-admin, moderador (verificações) e cadastrador (cadastros) |
| Dados sensíveis | e-mail dos usuários; **comprovantes enviados no pedido de selo de verificado (podem ser documento de identidade)** |

---

## 2. Escopo, ferramentas e limites da análise

**O que foi feito**

- **Leitura do código** (análise estática) com as mesmas buscas da seção 3.
- **Testes de fora, não invasivos**, contra produção: cabeçalhos, acesso direto à origem sem o
  Cloudflare, propriedades públicas da página, arquivos sensíveis.
- **Consultas somente leitura** ao banco de produção (`deploy/tinker-leitura.sh`): contagem de
  usuários, administradores e pedidos de verificação.
- **Auditoria de dependências** (`composer audit`, `npm audit`).
- **Reaproveitamento** do diagnóstico de IP feito no T.E.D. (mesmo servidor e mesmo proxy da
  Locaweb — ver seção 4, PG1).

**O que NÃO foi feito (e por quê)**

- **Nenhum teste de ataque ativo em produção** (força bruta, falsificação de IP, flood).
- **Nenhuma gravação em produção** durante a análise.
- **Revisão controller a controller do painel** (as ~55 ações do admin): o acesso já é restrito
  por nível e área; a checagem foi das regras de acesso, não de cada ação.
- Sem pentest profissional.

---

## 3. Método — checklist reutilizável, categoria por categoria

Para cada categoria: **o que procurar**, **como procurar** (comandos usados aqui) e **como
mitigar no Laravel**. Os comandos rodam no Git Bash, na raiz do projeto.

### 3.1 Inventário (sempre o primeiro passo)

Antes de procurar falhas, listar tudo o que recebe dados do usuário.

```bash
# Todas as rotas que GRAVAM (o que precisa de validação + autorização)
php artisan route:list --except-vendor | grep -E "POST|PUT|PATCH|DELETE"

# Middleware de cada rota (auth, verified, admin, signed, throttle)
php artisan route:list -v

# O que cada controller devolve (redirect, mensagem, download)
grep -rn -E "return (back|redirect|Inertia::render|Storage::)" app/Http/Controllers
```

No PO: 65 ações que gravam (114 rotas no total). As do site exigem `auth` + `verified`; as do
painel exigem também o gate `acessar-admin` e, por área, `can:admin.<area>` (cadastros,
verificações, usuários, configurações).

### 3.2 SQL injection

**Procurar:** SQL escrito à mão com valor do usuário concatenado; nome de coluna ou direção de
ordenação vindos do request.

```bash
grep -rn -E "DB::(raw|select|statement|unprepared|insert|update|delete)|whereRaw|selectRaw|orderByRaw|havingRaw|groupByRaw|fromRaw" app routes database
grep -rn -E "orderBy\(\s*\\\$request|orderBy\(\s*request\(" app
```

**Seguro:** Eloquent/Query Builder com valores (`where('cpf', $cpf)`), `whereRaw('... ?', [$valor])`
com parâmetro.
**Inseguro:** `whereRaw("nome LIKE '%$termo%'")`, `orderBy($request->input('coluna'))`.

**Mitigar:** sempre parâmetros (`?`) no SQL cru; para ordenação, lista fechada de colunas
permitidas (`in_array($coluna, ['nome', 'created_at'], true)`); escapar `%` e `_` em buscas
`LIKE` quando o curinga não deve ser do usuário.

### 3.3 XSS (injeção de HTML/JS)

**Procurar:** saída sem escape.

```bash
grep -rn -E "v-html|innerHTML|\{!!" resources
```

**Seguro:** `{{ }}` no Vue e no Blade (escapam); `{!! nl2br(e($texto)) !!}` (escapa antes).
**Atenção:** `v-html` só com conteúdo gerado pelo servidor e confiável; HTML montado a partir de
modelos editáveis (documentos, e-mails) precisa escapar as variáveis.

**Mitigar:** evitar `v-html`; escapar com `e()`; CSP (seção 3.12) como segunda barreira.

### 3.4 Autorização e acesso a dados de outros (IDOR)

**Procurar:** toda rota com `{modelo}` na URL — o controller confere se o registro é do usuário?

```bash
grep -rn -E "abort_unless|abort_if|authorize\(|Gate::|->can\(|policy" app/Http
grep -rn -A3 "public function authorize" app/Http/Requests
```

Para cada ação: (1) quem pode? (2) onde está a checagem? (3) a consulta é **escopada**
(`where('empregador_id', $user->empregador->id)`) ou busca por id solto (`findOrFail($id)`)?

**Mitigar:** checar posse em `FormRequest::authorize()` ou Policy; escopar consultas pelo dono;
usar UUID nas URLs (não impede acesso, mas impede adivinhar ids sequenciais); validação
`exists` também escopada (`Rule::exists(...)->where('empregador_id', ...)`).

### 3.5 Atribuição em massa

**Procurar:** campos sensíveis em `$fillable`; `create($request->all())`.

```bash
grep -rn -A30 "protected \$fillable" app/Models | grep -E "is_admin|role|status|_id'"
grep -rn -E "\->(create|update|fill)\(\s*\\\$request->(all|input)\(\)" app
```

**Mitigar:** `$request->validated()` em vez de `all()`; campos de privilégio (`is_admin`) fora
do `$fillable`; ids de dono setados no controller, nunca vindos do formulário.

### 3.6 Autenticação (login, senha, cadastro, recuperação)

**Verificar:**

- Limite de tentativas: `RateLimiter::for('login', ...)` em `FortifyServiceProvider` — **por
  qual chave** conta? Se a chave inclui o IP, ver seção 3.8.
- Política de senha: `app/Actions/Fortify/PasswordValidationRules.php` e se há
  `Password::defaults()` configurado no `AppServiceProvider`.
- Enumeração de contas: o "esqueci a senha" e o cadastro revelam se o e-mail existe?
- Verificação de e-mail e 2FA ligados em `config/fortify.php` (`Features::`).
- Contas com senha conhecida (seeders, documentação, chat).

**Mitigar:** limite também **só por e-mail** (não depende do IP); `Password::defaults(fn () =>
Password::min(10)->letters()->numbers()->uncompromised())` em produção; mensagem genérica no
"esqueci a senha"; 2FA obrigatório para admin; seeder nunca cria usuário com senha fixa fora de
`local`/`testing`.

### 3.7 Sessão, cookies e CSRF

```bash
grep -n -E "'lifetime'|'expire_on_close'|'encrypt'|'secure'|'http_only'|'same_site'" config/session.php
curl -s -I https://SITE/login | grep -i set-cookie
```

**Esperado:** cookie com `secure`, `httponly`, `samesite=lax` ou `strict`; CSRF padrão do
Laravel ativo (o Inertia já manda o `X-XSRF-TOKEN`).
**Mitigar:** `SESSION_SECURE_COOKIE=true` em produção; considerar `SESSION_ENCRYPT=true`
quando a sessão fica no banco.

### 3.8 IP real, proxies e acesso direto à origem

Crítico quando há CDN/proxy na frente. O limite de tentativas por IP só funciona se o IP for
confiável.

```bash
# A origem responde sem passar pelo Cloudflare? (troque pelo IP real do servidor)
curl -s -k -o /dev/null -w '%{http_code}\n' --resolve SITE:443:IP_DA_ORIGEM https://SITE/login

# Configuração de proxies confiáveis
grep -n "trustProxies" bootstrap/app.php
```

**Risco:** `trustProxies(at: '*')` + origem acessível direto = qualquer um manda
`X-Forwarded-For` falso, troca de "IP" a cada requisição e anula o limite por IP; e escapa da
proteção contra DDoS do CDN.
**Mitigar:** confiar só nos IPs do Cloudflare e do proxy próprio da hospedagem; limitar por
e-mail/usuário além de IP; regras de limite **na borda** (Cloudflare); quando a hospedagem
permitir, bloquear a origem para tudo que não venha do Cloudflare (Authenticated Origin Pulls
ou firewall). **Testar:** requisição com `X-Forwarded-For` falso e conferir `request()->ip()`.

### 3.9 DoS / DDoS e abuso de recursos

Separar as camadas:

| Camada | Quem protege | O que fazer |
| --- | --- | --- |
| Volumétrico (rede) | CDN (Cloudflare) | Proxy ligado; origem não exposta |
| Muitas requisições (L7) | CDN + aplicação | Regras de limite no Cloudflare; `throttle` nas rotas |
| Requisições caras | Aplicação | Limites menores para PDF, upload, buscas, envio de e-mail |

**Procurar:** rotas sem `throttle`; operações pesadas (gerar PDF, upload, busca `LIKE '%...%'`,
envio de e-mail **síncrono** com `QUEUE_CONNECTION=sync`).

```bash
grep -rn -E "throttle|RateLimiter::for|Limit::" app routes config bootstrap
```

**Mitigar:** `RateLimiter::for('nome', fn ($r) => Limit::perMinute(N)->by($r->user()?->id ?: $r->ip()))`
e `->middleware('throttle:nome')`; limite geral no grupo autenticado; tamanho máximo de upload;
paginação em listas.

### 3.10 Abuso do e-mail do sistema (SMTP como "relé" de spam)

Muito comum e pouco lembrado: toda ação que **manda e-mail para um endereço digitado** pode
ser usada para disparar e-mails a terceiros, queimar a reputação do domínio e fazer o provedor
bloquear a conta.

**Procurar:** cadastro aberto (`Features::registration()`), reenvio de verificação, recuperação
de senha, convites, "fale conosco".

```bash
grep -rn -E "notify\(|Notification::route|Mail::(to|send|raw)" app
```

**Mitigar:** limite por IP **e** por destinatário; intervalo mínimo entre reenvios para o
mesmo destinatário; captcha (Cloudflare Turnstile) no cadastro; SPF/DKIM/DMARC configurados.

### 3.11 Links assinados, convites e tokens

**Verificar:** a rota tem `signed`? Qual a validade? O link pode ser reaproveitado? **A ação
confere a identidade de quem abriu** (ou vincula qualquer conta logada)?

**Mitigar:** validade curta; uso único; amarrar ao destinatário (e-mail verificado da conta =
e-mail do convite). Lembrar que a assinatura depende do esquema/host (ver seção 5, problema do
Cloudflare em modo Flexible).

### 3.12 Cabeçalhos de segurança e exposição de informação

```bash
curl -s -I https://SITE/login | grep -i -E "^(strict-transport|content-security|x-frame|x-content-type|referrer-policy|permissions-policy|x-powered-by|server):"

# O que a página entrega ao navegador (Inertia: atributo data-page)
curl -s https://SITE/ | grep -o 'data-page="[^"]*"'   # decodificar o JSON e olhar as props

# Campos de model que vão para o navegador
php artisan tinker --execute 'echo implode(", ", array_keys(App\Models\User::first()->toArray()));'
```

**Esperado:** `X-Frame-Options: SAMEORIGIN` (ou CSP `frame-ancestors`), `X-Content-Type-Options:
nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, HSTS
(depois de o site estar estável em HTTPS), CSP (começar em `Content-Security-Policy-Report-Only`).
Sem `X-Powered-By` e sem versão de framework/linguagem nas props públicas.
**Mitigar:** um middleware próprio que acrescenta os cabeçalhos (registrado em
`bootstrap/app.php`); `$hidden` nos models para campos internos; revisar as props de páginas
públicas.

### 3.13 Uploads e downloads

**Verificar:** validação por conteúdo (`mimes`/`mimetypes`), tamanho (`max`), disco **privado**,
nome aleatório no armazenamento, download como anexo e com checagem de posse.

### 3.14 Configuração, segredos e infraestrutura

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://SITE/.env           # esperado 404
curl -s -o /dev/null -w '%{http_code}\n' https://SITE/composer.json  # esperado 404
grep -n -E "^APP_DEBUG|^APP_ENV" .env.production
```

- `APP_DEBUG=false` e `APP_ENV=production` no servidor; `.env` com permissão 600, fora da pasta
  pública.
- Senhas fora do Git; nada de senha no `.env.production` (modelo).
- Senha única por sistema/banco; trocar senhas que passaram por chat ou e-mail.
- Banco acessível pela internet: só com senha forte e única.
- Modo SSL/TLS do CDN: **Full** (o trecho CDN → origem também criptografado).
- DNS: registros como `ftp` que são CNAME do domínio principal param de funcionar se o
  principal ganhar o proxy do Cloudflare (FTP/SSH não passam pelo proxy).

### 3.15 Dependências

```bash
composer audit
npm audit --omit=dev   # o que vai para produção
npm audit              # inclui ferramentas de build
```

Diferenciar o que **vai para o servidor** do que é só ferramenta de build (no Laravel com
Vite, o servidor recebe só o JS compilado). Rodar após cada `composer require`/`npm install`.

---

---

## 4. Resultado no PROJ_PO (análise de 10/10/2026)

**Status (atualizado em 10/10/2026):**

| Item | Status | O que foi feito / o que falta |
| --- | --- | --- |
| PG1 | Aberto | — |
| PG2 | Aberto | Trocar e-mail e senha do admin e ativar duas etapas (sem código). |
| PG3 | **Corrigido** — no código, ainda não publicado | Limites: cadastro 5/h por IP e 30/h no sistema; "esqueci a senha" 3/h por e-mail, 10/h por IP e 50/h no sistema; reenvio da verificação 2/min e 6/h por usuário. Captcha do Cloudflare (Turnstile) no cadastro e no "esqueci a senha", conferido pelo servidor; com o Cloudflare fora do ar, os dois são recusados. O captcha do login entra com o PG1. |
| PG4 | **Corrigido** — no código, ainda não publicado | Comprovantes da verificação passaram para o disco privado (`storage/app/private`), entregues só pela rota protegida do painel. Em produção havia 0 comprovantes, então nada a migrar. |
| PM1–PM4 | Aberto | — |

### 4.1 Graves

| # | Falha | Evidência | Mitigação proposta |
| --- | --- | --- | --- |
| PG1 | **Limite de login contornável com IP falso.** `trustProxies(at: '*')` aceita `X-Forwarded-For` de qualquer origem; o limite de login conta e-mail **+ IP**; a origem responde sem o Cloudflare. | `bootstrap/app.php`; `FortifyServiceProvider` (limitador `login`); `curl --resolve` direto na origem → 200. Diagnóstico do T.E.D. (mesmo proxy): a Locaweb já entrega o IP real no `REMOTE_ADDR` e o `X-Forwarded-For` chega como o visitante mandou. | Limite também só por e-mail; captcha do Cloudflare no login; `trustProxies` só no protocolo (`HEADER_X_FORWARDED_PROTO`). Mesmo código do T.E.D. |
| PG2 | **Super-admin com e-mail de terceiros e senha conhecida.** | Banco de produção: 1 administrador, nível super-admin, `admin@email.com` (domínio `email.com` não é nosso), sem duas etapas; a senha passou por conversa. | Trocar e-mail e senha; ativar duas etapas. |
| PG3 | **SMTP utilizável para spam.** Cadastro e "esqueci a senha" sem limite nem captcha. | `config/fortify.php` (`registration`, `resetPasswords`, `emailVerification`) sem limitador nessas rotas. **A conta de e-mail é a mesma do T.E.D.**: um bloqueio da Locaweb derruba o e-mail dos dois sistemas. | Limites por IP, por e-mail e teto geral; captcha no cadastro e no "esqueci a senha". |
| PG4 | **Comprovantes de verificação (podem ser documento de identidade) na pasta pública.** | `SolicitacaoVerificacaoController::store` gravava no disco de mídia (`MEDIA_DISK=public`), publicado em `/storage/verificacoes/<nome>`: quem tivesse o nome abria o arquivo sem login. O nome é aleatório e longo (difícil de adivinhar), mas é dado pessoal sensível (LGPD). Em produção: 0 comprovantes. | Disco privado (`local`), saída só pela rota do painel (que já existia e já exigia o nível certo). |

### 4.2 Médios

| # | Falha | Evidência | Mitigação proposta |
| --- | --- | --- | --- |
| PM1 | Sem cabeçalhos de segurança (CSP, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, HSTS) — clickjacking possível. | `curl -I` em produção. | Middleware de cabeçalhos; CSP em modo relatório primeiro (o T.E.D. já tem o modelo). |
| PM2 | `X-Powered-By: PHP/8.5.7` em toda resposta. | `curl -I`. A página inicial **não** expõe versões. | Remover o cabeçalho. |
| PM3 | Enumeração de contas pelo "esqueci a senha": "Não encontramos um usuário com esse endereço de e-mail." | `lang/pt_BR/passwords.php` ('user') + resposta padrão do Fortify. | Resposta igual para e-mail com e sem conta (como no T.E.D.). |
| PM4 | Sem limite geral nas rotas logadas; palpite e placar dos fãs sem limite (comentários e dicas já têm 10/min). | `routes/web.php`. | Limite geral por usuário + limites nas ações de voto. |

### 4.3 Fora do código

- Senhas de banco, FTP/SSH e e-mail passaram por conversa; a do banco do PO é a mesma do T.E.D.
- `npm audit`: 5 avisos críticos **só em ferramentas de build**; `npm audit --omit=dev` = 0.

### 4.4 Verificado e em ordem

| Área | Resultado |
| --- | --- |
| SQL injection | Eloquent; os `selectRaw`/`orderByRaw` existentes têm texto fixo, sem dado do usuário. |
| XSS | Único `v-html` é o QR code do 2FA (gerado pelo servidor); comentários, dicas e biografias são exibidos escapados pelo Vue. |
| Autorização do painel | Gate `acessar-admin` + gate por área; só o super-admin gerencia usuários e administradores; ninguém altera o próprio perfil de administrador. |
| Atribuição em massa | `User` com `Fillable` explícito (sem campos de privilégio); `Hidden` com senha e segredos do 2FA. |
| Senha | Produção: mínimo 12, maiúsculas e minúsculas, letras, números, símbolos e `uncompromised()`. |
| Troca de e-mail | Zera a verificação (`email_verified_at = null`). |
| Comandos destrutivos | `DB::prohibitDestructiveCommands()` em produção (bloqueia `migrate:fresh` etc.). |
| Uploads do painel | `image` (sem SVG) e `max:4096`; comprovante com `mimes:pdf,jpg,jpeg,png` e `max:5120`. |
| CSRF / sessão | Padrão do Laravel; cookie `secure` + `httponly` + `samesite=lax`. |
| Configuração | `/.env`, `/composer.json`, log e `/.git/config` → 404. |
| PHP | `composer audit` sem avisos. |

---

## 5. Falhas e ajustes de segurança já corrigidos neste projeto

| Data | Problema | Como apareceu | Correção |
| --- | --- | --- | --- |
| 09/10 | HTTP não redirecionava para HTTPS | `curl http://...` → 200 | "Always Use HTTPS" no Cloudflare (vale para a zona toda, junto com o T.E.D.) |
| 09/10 | Cloudflare em **Flexible**: trecho até a Locaweb sem criptografia e links assinados com 403 | Descoberto no T.E.D. | Modo **Full** (a zona é a mesma) |
| 09/10 | Acessos de produção | Montagem do deploy | `.Producao_acessos` fora do Git; scripts leem na hora; `.env` do servidor com 600 |
| 09/10 | Seeder com usuário de teste | Primeiro deploy | Usuário de teste só em `local`/`testing` |

---

## 6. Pesquisas feitas na internet

As pesquisas foram feitas na análise do T.E.D. (09–10/10/2026) e valem para o PO, que usa a
mesma hospedagem (Locaweb), o mesmo Cloudflare e a mesma conta de e-mail. Cada pesquisa abaixo
foi feita para validar uma mitigação. Fontes de terceiros foram usadas
como apoio e sinalizadas; a referência final deve ser a documentação oficial.

| Pergunta | Busca usada | O que se confirmou | Confiabilidade |
| --- | --- | --- | --- |
| Como limitar requisições no Laravel 13 | `Laravel 13 documentation rate limiting RateLimiter::for routes throttle middleware security` | `RateLimiter::for` + `throttle:nome`; resposta 429 com `Retry-After`; o middleware fica em `bootstrap/app.php` (não em `Kernel.php`, que é de versões antigas). Limite reduz força bruta e abuso, mas **não substitui** proteção de borda contra DDoS. | Doc oficial (página do `RateLimiter`) + tutoriais |
| `trustProxies` atrás do Cloudflare | `Laravel trustProxies Cloudflare IP ranges X-Forwarded-For spoofing rate limit bypass origin` | `*` confia em qualquer origem e permite falsificar o IP; confiar nas faixas do Cloudflare **não basta** se a origem for acessível direto (qualquer cliente Cloudflare consegue chegar nela); recomendação: Authenticated Origin Pulls, firewall da origem, limite na borda, testar com `X-Forwarded-For` falso. | Pacotes de terceiros (READMEs) — conferir na doc do Laravel e do Cloudflare |
| Cabeçalhos de segurança | `Laravel security headers middleware Content-Security-Policy X-Frame-Options Strict-Transport-Security OWASP` | Lista de cabeçalhos e valores comuns; padrão de middleware próprio; CSP começar em modo relatório; HSTS `preload` é difícil de desfazer. | Terceiros — conferir no OWASP Secure Headers Project |
| Forçar HTTPS na Locaweb | `Locaweb hospedagem de sites forçar HTTPS redirecionar http para https .htaccess` + leitura da ajuda oficial | A regra da Locaweb (`SERVER_PORT 80`) entra em **loop** atrás do Cloudflare em modo Flexible. | Ajuda oficial da Locaweb |
| Loop de redirecionamento com Cloudflare | `Cloudflare "Always Use HTTPS" Flexible SSL redirect loop .htaccess X-Forwarded-Proto` | Flexible = origem sempre vê HTTP; deixar uma camada só responsável pelo redirecionamento; preferir modo Full. | Terceiros |
| SMTP da Locaweb | `Locaweb email SMTP configuração email-ssl.com.br porta 465 587 SSL TLS autenticação remetente` | 465 = SSL implícito (`MAIL_SCHEME=smtps` no Laravel), envio exige autenticação, remetente = a própria conta. | Ajuda Locaweb + terceiros |

**Fontes**

- [Laravel 13 — Rate Limiting](https://laravel.com/docs/13.x/rate-limiting)
- [Laravel Rate Limiting — Backpack](https://backpackforlaravel.com/articles/tutorials/laravel-rate-limiting-explained-with-real-life-examples)
- [How to Implement Rate Limiting in Laravel — OneUptime](https://oneuptime.com/blog/post/2026-02-03-laravel-rate-limiting/markdown)
- [Laravel throttle — Kinsta](https://kinsta.com/blog/laravel-throttle/)
- [robertboes/laravel-cloudflare-proxies](https://packagist.org/packages/robertboes/laravel-cloudflare-proxies)
- [monicahq/laravel-cloudflare](https://github.com/monicahq/laravel-cloudflare)
- [Trusted Proxy — Laravel News](https://laravel-news.com/trusted-proxy)
- [Security headers in Laravel — Paulund](https://paulund.co.uk/notebook/laravel/security-headers-in-laravel)
- [jeffersongoncalves/laravel-security-headers](https://root.packagist.org/packages/jeffersongoncalves/laravel-security-headers)
- [jcaillot/owasp-headers](https://laraplugins.io/plugins/jcaillot/owasp-headers)
- [Como forçar o HTTPs — Ajuda Locaweb](https://www.locaweb.com.br/ajuda/?p=12866)
- [Fix redirect loop: cPanel .htaccess origin — StackHarbor](https://stackharbor.com/en/knowledge-base/cffix-redirect-loop-cpanel-htaccess-origin/)
- [Portas SMTP — Ajuda Locaweb](https://www.locaweb.com.br/ajuda/wiki/portas-smtp/)

**Para aprofundar (oficiais, ainda não consultados nesta análise):** OWASP Top 10, OWASP ASVS,
OWASP Secure Headers Project, documentação de segurança do Laravel 13 (autenticação,
autorização, CSRF, criptografia), Cloudflare Authenticated Origin Pulls e Cloudflare Turnstile.

---

---

## 7. Plano de correção (ordem sugerida)

1. **PG4** — comprovantes no disco privado (feito no código em 10/10/2026).
2. **Sem código, imediato:** PG2 (admin) e troca das senhas que passaram por conversa.
3. **PG3 e PG1** — limites por e-mail, captcha do Cloudflare (chave do PO criada pelo Sandro em
   10/10/2026) e `trustProxies` só no protocolo. Reaproveitar o código do T.E.D.
   (`LimitarEnviosDaAutenticacao`, `VerificarCaptcha`, `Turnstile`, `RespostaDeLimite`,
   `CaptchaCloudflare.vue`), adaptando às telas do PO (Fortify sem Jetstream).
4. **PM1 a PM4** — cabeçalhos (CSP em modo relatório primeiro), `X-Powered-By`, resposta única no
   "esqueci a senha", limites gerais.
5. **Rotina:** `composer audit` e `npm audit` a cada atualização de dependência.

Cada item de código vai num deploy próprio. Ao corrigir, atualizar o status na seção 4 e
registrar no `DEPLOY.md` quando mudar a publicação.

---

## 8. Checklist rápido para outro sistema

- [ ] Inventário de rotas que gravam e seus middleware
- [ ] SQL cru só com parâmetros; nada de coluna/ordem vinda do request sem lista fechada
- [ ] Nenhum `v-html`/`{!! !!}` com dado do usuário sem escape
- [ ] Toda rota com `{modelo}` confere posse; consultas escopadas pelo dono
- [ ] `validated()` em vez de `all()`; campos de privilégio fora do `$fillable`
- [ ] Login limitado por usuário/e-mail (não só IP); senha forte; 2FA para admin
- [ ] Nenhuma conta com senha conhecida em produção; seeders seguros
- [ ] `trustProxies` restrito; origem não acessível sem o CDN (ou limite que não dependa do IP)
- [ ] `throttle` em cadastro, recuperação de senha, convites, uploads, geração de PDF
- [ ] Ações que mandam e-mail limitadas por destinatário; captcha no cadastro aberto
- [ ] Links assinados amarrados a quem deve usá-los; validade curta
- [ ] Cabeçalhos de segurança; sem `X-Powered-By`; sem versões em páginas públicas
- [ ] Uploads: tipo por conteúdo, tamanho, disco privado, download com checagem de posse
- [ ] `APP_DEBUG=false`; `.env` inacessível e com permissão restrita; segredos fora do Git
- [ ] CDN em modo SSL Full; HTTP → HTTPS em uma camada só
- [ ] `composer audit` e `npm audit --omit=dev` limpos
