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
| HTTPS termina num proxy antes do PHP | `trustProxies(at: '*')` em `bootstrap/app.php` e `URL::forceScheme('https')` quando `APP_URL` é https — sem isso o login redireciona para `http` e a sessão se perde |
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
| `lib.sh`, `askpass.sh` | Funções comuns e entrega da senha ao `ssh`/`scp` | — |

Os scripts rodam no Git Bash, a partir da raiz do projeto.

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

## Pendências de produção

- **E-mail do administrador:** o admin usa `admin@email.com`, de um domínio que não é nosso
  (`email.com`). Com o SMTP ativo, um "esqueci minha senha" nessa conta envia o link de
  redefinição para a caixa de um terceiro. Trocar por um endereço próprio.
- **Entrega de e-mail para fora do domínio:** o SMTP foi testado enviando para a própria conta
  (`contato@vipti.com.br`). Falta testar Gmail/Outlook; as mensagens saem sem DKIM.
- **Senhas que passaram por conversa:** trocar FTP/SSH e banco no painel; se trocar a do banco,
  atualizar `~/po_app/.env` e o `.Producao_acessos`.
- **Fotos do Wikimedia Commons:** as licenças exigem crédito ao autor; definir como exibir ou
  substituir por fotos licenciadas.
