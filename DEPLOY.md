# Publicação em produção — PROJ_PO

Produção: **https://po.vipti.com.br**, na Locaweb (hospedagem de sites, Linux). No ar desde 09/10/2026.

Este guia não tem senhas. Todos os acessos (FTP/SSH, banco, administrador) ficam em
`.Producao_acessos`, na raiz do projeto, que está fora do Git (`.gitignore` e
`.git/info/exclude`). Os scripts de `deploy/` leem esse arquivo na hora e não gravam senha em
lugar nenhum.

## Estrutura no servidor

```
/home/storage/d/66/10/vipti2/      (home do usuário vipti2)
├── po_app/                        aplicação Laravel (fora da área pública)
│   ├── .env                       configuração de produção (permissão 600)
│   └── storage/app/public/        fotos, logos e banners enviados pelo painel
└── public_html/po/                raiz do subdomínio po.vipti.com.br
    ├── index.php                  versão de produção (deploy/index.producao.php)
    ├── .htaccess, build/, ícones  conteúdo de public/
    └── storage -> ~/po_app/storage/app/public   (link criado com ln -s)
```

O subdomínio é configurado no painel da Locaweb como "conteúdo de pasta" apontando para
`public_html/po`. O `index.php` de produção usa o caminho absoluto de `po_app` e chama
`usePublicPath(__DIR__)` para o Vite achar os arquivos compilados.

## Particularidades da Locaweb

| Situação | Consequência |
| --- | --- |
| Não há `php` padrão no terminal | Usar **`php84`** (8.4) em todos os comandos |
| `proc_open`, `exec`, `symlink` e `mail` desativados no PHP | Fila em `QUEUE_CONNECTION=sync`; `storage:link` não funciona (usar `ln -s`); e-mail só por SMTP; `artisan about` não funciona |
| SSH só depois de **liberar no painel**, e a liberação dura **3 horas** | Combinar a janela antes de publicar |
| Sem a liberação, o SSH **aceita a senha** e o comando termina sem erro e **sem nenhuma saída** (nada é executado) | Script de deploy que não devolve nada: conferir a liberação antes de investigar o script |
| FTP (porta 21) funciona, mas sem TLS | Enviar arquivos por SSH/SCP (porta 22), que é criptografado |
| E-mail pelo SMTP da Locaweb (`email-ssl.com.br`, porta **465**, conta `contato@vipti.com.br`, remetente "po - prime"), ativo desde 09/10/2026 | `MAIL_MAILER=smtp` com **`MAIL_SCHEME=smtps`** (a 465 já começa criptografada); o remetente tem de ser a própria conta autenticada. A mesma conta é usada pelo PROJ_TED |
| HTTPS termina num proxy antes do PHP | `trustProxies` confiando **só no protocolo** (`HEADER_X_FORWARDED_PROTO`) em `bootstrap/app.php` e `URL::forceScheme('https')` quando `APP_URL` é https — sem isso o login redireciona para `http` e a sessão se perde. O IP real do visitante já chega no `REMOTE_ADDR`; confiar no `X-Forwarded-For` deixaria qualquer um trocar o próprio IP (SEGURANCA.md, PG1) |
| O Cloudflare tem de ficar em modo SSL/TLS **"Full"** (não "Flexible" nem "Full (strict)"); trocado em 09/10/2026 | Em "Flexible" ele fala com a Locaweb pela porta 80, o PHP recebe `X-Forwarded-Proto: http` e todo **link assinado** (verificação de e-mail, convite) dá **403 Invalid signature**, mesmo com o link em https — além de o trecho até a Locaweb trafegar sem criptografia. "Full (strict)" não serve: o certificado da Locaweb é genérico (`*.websiteseguro.com`) |
| Banco MySQL **5.7** (local usa 8.4), com acesso externo | Migrations rodam do notebook; validar antes com `migrate --pretend` |
| `zip` do PowerShell grava caminhos com `\` | Empacotar com `tar.gz` |

## Scripts (`deploy/`)

| Script | Para que serve | Precisa de SSH? |
| --- | --- | --- |
| `artisan-producao.sh` | Roda `php artisan ...` do código local contra o banco de produção | Não |
| `tinker-leitura.sh` | Tinker com o banco em modo somente leitura (produção por padrão; `--local` para o banco local) — gravações e DDL são recusadas pelo MySQL | Não |
| `montar-pacote.sh` | Gera `po_app.tar.gz` e `po.tar.gz` em `C:\devfolder\proj_po_deploy` | Não |
| `publicar.sh` | Envia os pacotes, descompacta, refaz link e caches, coloca no ar | Sim |
| `copiar-cadastros-para-producao.php` | Cópia única dos cadastros locais (feita no primeiro deploy) | Não |
| `lib.sh`, `askpass.sh`, `acesso.php` | Funções comuns, entrega da senha ao `ssh`/`scp` e leitura de um campo do `.Producao_acessos` | — |

Os scripts rodam no Git Bash, a partir da raiz do projeto. Não dependem de `php`, `composer`
nem `python` no PATH: o `lib.sh` usa `C:\php\8.4\php.exe` (mesma versão do `php84` do servidor)
e `C:\php\composer\composer.phar`, e põe esse PHP no PATH dos scripts (o plugin Wayfinder do
`npm run build` chama `php artisan` direto). Para outro PHP: `PHP=/c/php/8.5/php.exe deploy/<script>.sh`.

Fora dos scripts, o build manual também precisa do PHP no PATH:
`PATH="/c/php/8.4:$PATH" npm run build`.

## Atualizar a produção

1. Garantir que o código local está commitado e testado (`php artisan test --compact`).
2. Se houver migration nova, simular e aplicar (não precisa de SSH):
   ```bash
   deploy/artisan-producao.sh migrate --pretend
   deploy/artisan-producao.sh migrate --force
   ```
3. Montar o pacote: `deploy/montar-pacote.sh`
4. Liberar o SSH no painel da Locaweb.
5. Publicar: `deploy/publicar.sh` — coloca o site em manutenção, atualiza, refaz link e caches,
   tira da manutenção e confere as páginas.

O `publicar.sh` não altera o `.env` do servidor nem as fotos enviadas pelo painel.

## Alterar o `.env` do servidor

Com o SSH liberado, editar `~/po_app/.env` e depois rodar `php84 artisan config:cache` em
`~/po_app`. O modelo está em `.env.production` (local, fora do Git, sem senhas).

Variáveis da segurança (desde 10/10/2026): `TURNSTILE_SITE_KEY` e `TURNSTILE_SECRET_KEY` (captcha
do Cloudflare; valores na seção `[Captcha Cloudflare (Turnstile)]` do `.Producao_acessos`),
`CSP_MODO` (`relatorio`, `bloquear` ou `desligado`) e `HSTS_SEGUNDOS`.

## Primeiro deploy (09/10/2026) — registro

1. `migrate --pretend` no banco da Locaweb para validar o MySQL 5.7.
2. `migrate --force` e `db:seed --force` com `APP_ENV=production` (cria o papel Comentarista,
   a pontuação padrão e os pesos; **não** cria o usuário de teste).
3. Cópia dos cadastros locais com os mesmos IDs: organizações, categorias, atletas (com 57
   fotos), treinadores, juízes, eventos e lutas do UFC e do Jungle Fight. Ficaram de fora
   registros excluídos e tudo ligado a usuários de teste. A decisão de levar ou não dados locais
   é tomada a cada deploy.
4. Criação do administrador (`admin@email.com`, super-admin, e-mail confirmado) e de um
   usuário comum de teste.
5. Envio dos pacotes por SCP, descompactação, `.env` criado direto no servidor, `key:generate`,
   permissões, `ln -s` do storage, caches de configuração, rotas e telas.
6. A página provisória "Estamos chegando" foi guardada em `~/tmp/em-breve-index.html`.

## Publicação da segurança (10/10/2026) — registro

Itens PG1, PG3, PG4 e PM1 a PM4 do `SEGURANCA.md`, num deploy só (pedido do Sandro).

1. Sem migration nova (`migrate:status` sem pendentes).
2. `.env` do servidor: cópia em `~/po_app/.env.antes-seguranca-20261010` (600) e inclusão das
   chaves do captcha, `CSP_MODO=relatorio` e `HSTS_SEGUNDOS=31536000`. As chaves foram lidas do
   `.Producao_acessos` e enviadas pela entrada do `ssh`, sem aparecer na tela.
3. `montar-pacote.sh` (já cria `storage/app/private`, o disco dos comprovantes) e `publicar.sh`.
4. Conferido em produção: `/`, `/login`, `/eventos` e `/up` com 200; em `/login` saem
   `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, HSTS e a CSP em modo
   relatório, sem `X-Powered-By`; a chave do captcha está em login, cadastro e "esqueci a
   senha"; o script embutido sai com nonce; `POST /api/csp-relatorio` responde 204; log de erros
   sem registros depois da publicação.

## Correção do PCRE JIT (22/11/2026) — registro

Desde 16/11/2026 a página inicial `/` respondia **500**: `preg_match(): Allocation of JIT memory
failed` (o PHP da Locaweb, 8.4.28, passou a bloquear memória executável; o mesmo aconteceu no
T.E.D., no minierp e no CRM M11). Correção: `ini_set('pcre.jit', '0')` no
`deploy/index.producao.php`. O arquivo foi enviado direto por `scp` para
`~/public_html/po/index.php` (o original ficou em `~/tmp/po_index.php.bak-20261122`), sem
pacote. Depois disso `/`, `/login` e `/up` respondem 200. O código do servidor não mudou
(último pacote publicado é o de 10/10/2026).

## Atualização de segurança das dependências (22/11/2026) — registro

Commit `c29a1b4`: Symfony 8.1.8 → 8.1.11 e Laravel 13.34.0 → 13.43.0 (com as dependências),
para zerar os 11 alertas do `composer audit --no-dev` (o mais grave médio: injeção em cabeçalho
de e-mail no `symfony/mime`). Compatível com o PHP 8.4.28 do servidor. Sem migrations.

1. SSH testado antes (`php84 artisan --version` = 13.34.0 no servidor, igual ao lock).
2. Suíte (374 testes) e build conferidos; `montar-pacote.sh` (com o `pcre.jit` no `index.php`).
3. `publicar.sh`: `/`, `/login`, `/eventos` e `/up` com 200; `artisan --version` = 13.43.0 no
   servidor; sem erro novo no log.

Repetir o `composer audit` a cada poucas semanas: alertas novos saem mesmo sem mudança no projeto.

## Pendências de produção

- **CSP:** depois de alguns dias de uso, ler `~/po_app/storage/logs/csp-*.log` e, sem relato
  legítimo, trocar `CSP_MODO` para `bloquear` (e rodar `config:cache`).

- **E-mail do administrador:** o admin usa `admin@email.com`, de um domínio que não é nosso
  (`email.com`). Com o SMTP ativo, um "esqueci minha senha" nessa conta envia o link de
  redefinição para a caixa de um terceiro. Trocar por um endereço próprio.
- **Entrega de e-mail para fora do domínio:** o SMTP foi testado enviando para a própria conta
  (`contato@vipti.com.br`). Falta testar Gmail/Outlook; as mensagens saem sem DKIM.
- **Senhas que passaram por conversa:** trocar FTP/SSH e banco no painel; se trocar a do banco,
  atualizar `~/po_app/.env` e o `.Producao_acessos`.
- **Fotos do Wikimedia Commons:** as licenças exigem crédito ao autor; definir como exibir ou
  substituir por fotos licenciadas.
