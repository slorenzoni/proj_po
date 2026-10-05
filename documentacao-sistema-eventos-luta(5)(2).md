# Documentação do Sistema — Plataforma de Palpites em Eventos de Luta

## 1. Visão geral

Plataforma onde clientes acompanham eventos de esportes de combate (MMA, Judô, Boxe), veem informações dos atletas e dão palpites sobre o vencedor de cada luta. Existe um plano gratuito (só escolhe o vencedor) e um plano pago (pode opinar/comentar no chat do evento e mudar de opinião ao longo dos rounds). Administradores cadastram organizações, eventos, lutas, categorias e atletas. Há também um módulo de patrocínio (banners + blog patrocinado).

> 📚 **Histórico de revisões:**
> - **v1**: modelo inicial a partir do rascunho original.
> - **v2**: pesquisa de domínio — separou Evento/Luta, adicionou CategoriaPeso, Placar (10-point must), Organizacao e enriqueceu Atleta.
> - **v3 (atual)**: unificou **Cliente** e **Administrador** numa única tabela de autenticação **`users`** (padrão Laravel), com tabelas de perfil 1:1 para os dados específicos de cada papel.

---

## 2. Convenções aplicadas a TODAS as tabelas

### 2.1 Chave primária + UUID para binding
Todo `id` é **BIGINT auto-incremento** — é a PK real, usada internamente e nas FKs. Além dele, toda tabela tem uma coluna **`uuid`** (única, gerada automaticamente), usada **só para expor o registro na URL** via route model binding.

**Laravel 12:**
- Migration: `$table->id();` + `$table->uuid('uuid')->unique();`
- Model: trait `HasUuids`, sobrescrevendo `uuidColumns()` para `['uuid']`
- `getRouteKeyName()` retorna `'uuid'`
- FKs continuam referenciando o `id` (BIGINT)

### 2.2 Soft delete
Toda tabela tem `deleted_at` (DATETIME, nullable) — trait `SoftDeletes` do Laravel, nenhum registro é apagado fisicamente.

### 2.3 Timestamps
Toda tabela tem `created_at` e `updated_at`.

### 2.4 Auditoria
Toda tabela tem o bloco `audit_*` (10 campos), preenchido via Observer/Middleware a cada escrita.

| Campo | Tipo | Tamanho |
|---|---|---|
| audit_id_user | BIGINT UNSIGNED | - |
| audit_name_user | VARCHAR | 255 |
| audit_origin_url | TEXT | - |
| audit_request_method | VARCHAR | 255 |
| audit_http_referer | TEXT | - |
| audit_route_name | TEXT | - |
| audit_controller_action | TEXT | - |
| audit_origin_ip | VARCHAR | 45 |
| audit_browser | TEXT | - |
| audit_db_user | VARCHAR | 255 |

---

## 3. Autenticação: `users` + perfis (v3)

### 3.1 Por que unificar?
Cliente e Administrador são, no fundo, a mesma coisa do ponto de vista de **autenticação**: alguém que loga com credencial + senha. Ter duas tabelas separadas faz o Laravel perder o padrão nativo de `Authenticatable` (um único guard, um único `Auth::user()`) e obriga a duplicar lógica de login. A solução adotada é o padrão **tabela de autenticação + perfis por papel**:

- **`users`** — tudo que é comum à autenticação (nome, email, senha, CPF).
- **`perfil_cliente`** e **`perfil_administrador`** — relação **1:1** com `users`, guardando só os campos que fazem sentido pra aquele papel.

### 3.2 Papéis não são exclusivos — uma pessoa pode ter os dois
**Correção importante em relação à v3 original:** o papel de um usuário **não** é definido por um campo fixo tipo `tipo_user = 'cliente' OU 'administrador'`. Um administrador pode também querer participar dando palpites — e nesse caso ele **não cria uma segunda conta**, ele simplesmente passa a ter **as duas linhas de perfil** apontando para o mesmo `user_id`:

- `users` (id = 42) → 1 registro, 1 login, 1 identidade
- `perfil_administrador` (user_id = 42) → existe, porque ele é admin
- `perfil_cliente` (user_id = 42) → também existe, porque ele decidiu participar

O papel de alguém no sistema é definido pela **existência da linha de perfil correspondente**, não por um valor de enum. Isso é o que permite acumular papéis sem duplicar identidade.

O campo `users.papel_padrao` continua existindo, mas com um propósito bem mais modesto: é **nullable** e serve só de dica de UX (pra onde a pessoa cai depois do login, se ela tiver os dois perfis) — nunca é usado para autorização.

**Como identificar um Administrador na prática (Laravel):**

```php
class User extends Authenticatable
{
    public function perfilAdministrador()
    {
        return $this->hasOne(PerfilAdministrador::class);
    }

    public function perfilCliente()
    {
        return $this->hasOne(PerfilCliente::class);
    }

    public function isAdministrador(): bool
    {
        return $this->perfilAdministrador()->exists();
    }

    public function isCliente(): bool
    {
        return $this->perfilCliente()->exists();
    }
}
```

```php
// Autorização de rotas do painel admin
Gate::define('acessar-admin', fn (User $user) => $user->isAdministrador());

Route::middleware(['auth', 'can:acessar-admin'])->prefix('admin')->group(function () {
    // rotas do painel administrativo
});
```

Dentro do admin, o `nivel_acesso` de `perfil_administrador` ainda decide **o que** ele pode fazer (super-admin, moderador, cadastrador) — a existência do perfil só responde "ele é admin?"; o nível responde "o que ele pode fazer como admin?". Se a granularidade crescer muito, migrar esse campo para um pacote de permissões (ex: Spatie Laravel-Permission) em vez de manter como enum simples.

**E se ele tiver os dois perfis, como a tela sabe em qual "modo" ele está?** Login autentica a *pessoa*, não o *papel*. Isso normalmente se resolve com um "contexto ativo" guardado na sessão (não é uma coluna de banco) — um toggle tipo "Ver como Admin / Ver como Cliente" no menu, trocando o que aparece na tela sem trocar de conta.

### 3.4 users

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| papel_padrao | VARCHAR | 20 | Nullable. Só dica de UX (tela inicial pós-login) — NÃO define autorização nem é exclusivo |
| nome | VARCHAR | 150 | |
| email | VARCHAR | 150 | Único. Login do Administrador; contato do Cliente |
| cpf | VARCHAR | 14 | Único quando preenchido (só Cliente). Criptografado. **Login do Cliente** |
| senha_hash | VARCHAR | 255 | Hash via Laravel `Hash::make()` |
| email_verified_at | DATETIME | - | Campo padrão do Laravel para confirmação de e-mail |
| verificado | BOOLEAN | - | Selo de verificado (cache de leitura). Só `true` enquanto houver `AssinaturaVerificacao` ativa + `SolicitacaoVerificacao` aprovada |
| verificado_em | DATETIME | - | Data em que o selo foi concedido pela última vez |

> ⚠️ **Login diferente por tipo de usuário:** Cliente loga por CPF, Administrador loga por email. Isso é tratado na camada de autenticação (dois `LoginRequest`/formulários diferentes, ou um único formulário que aceita "CPF ou email" e resolve por regex), não no banco — o banco só guarda as duas credenciais possíveis na mesma linha.

### 3.5 perfil_cliente (1:1 com users)

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| user_id | BIGINT | - | FK -> users.id (único — garante 1:1) |
| foto_perfil_url | VARCHAR | 255 | Foto de perfil — **apenas 1** (diferente do Atleta, que pode ter até 3) |
| email_secundario | VARCHAR | 150 | Opcional |
| telefone | VARCHAR | 20 | |
| endereco | VARCHAR | 255 | |
| maior_de_18 | BOOLEAN | - | Validação obrigatória no cadastro |
| tipo | VARCHAR | 30 | Classificação comercial do cliente — **não é o plano** (isso vem de `Assinatura`) |
| data_cadastro | DATE | - | |

### 3.6 perfil_administrador (1:1 com users)

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| user_id | BIGINT | - | FK -> users.id (único — garante 1:1) |
| nivel_acesso | VARCHAR | 30 | Ex: super-admin, moderador, cadastrador |

### 3.7 Subtipos de usuário além de Cliente/Administrador
Além de Cliente e Administrador, o sistema vai ter outros papéis: **Comentarista**, **Treinador**, **Ex-lutador**, e possivelmente outros no futuro. Eles se dividem em dois grupos, tratados de forma diferente:

**Grupo 1 — papéis que já são uma entidade real do domínio:** em vez de criar uma tabela de perfil nova (o que duplicaria dado que já existe), a entidade existente ganha um `user_id` opcional:

- **Treinador** → `Treinador.user_id` (nullable, único). O registro continua sendo o mesmo referenciado em `AtletaEstilo`; só passa a poder logar quando esse campo é preenchido.
- **Ex-lutador** → `Atleta.user_id` (nullable, único). Mesma lógica — o Atleta continua com seu cartel, fotos, etc., e opcionalmente vira também uma conta de usuário (ex: pra comentar como ex-lutador).

**Grupo 2 — papéis que são só uma permissão, sem dado próprio de peso:** em vez de tabela nova a cada papel, um sistema genérico de papéis:

- **Papel** — catálogo (id, nome, descrição). Ex: "Comentarista", "Moderador".
- **UserPapel** — pivot N:N (`user_id`, `papel_id`). Um `user` pode acumular vários papéis desse grupo.

Adicionar um papel novo no futuro vira **inserir uma linha em `Papel`**, não criar uma tabela/migration nova.

| Papel | Como fica |
|---|---|
| Cliente | `perfil_cliente` (dado próprio) |
| Administrador | `perfil_administrador` (dado próprio) |
| Treinador | `Treinador.user_id` (reaproveita entidade existente) |
| Ex-lutador | `Atleta.user_id` (reaproveita entidade existente) |
| Comentarista, e futuros só-permissão | `Papel` + `UserPapel` (genérico) |

#### Papel
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| nome | VARCHAR | 60 | Ex: Comentarista, Moderador |
| descricao | VARCHAR | 255 | |

#### UserPapel
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| user_id | BIGINT | - | FK -> users.id |
| papel_id | BIGINT | - | FK -> Papel.id |

### 3.8 Selo de verificado (pago, como X/Instagram)
O selo mora em `users` (é um atributo da identidade, não de um papel específico), mas não é gratuito nem automático — exige **comprovação analisada por um admin** e uma **cobrança extra recorrente**, além da Assinatura Membro.

**Fluxo:**
1. Usuário envia comprovação → cria uma `SolicitacaoVerificacao` (`status = pendente`).
2. Um Administrador analisa e aprova ou rejeita.
3. Se aprovada, o usuário contrata a `AssinaturaVerificacao` (cobrança recorrente separada da Assinatura normal).
4. Enquanto a `AssinaturaVerificacao` estiver `ativa`, `users.verificado = true`. Se for cancelada/ficar inadimplente, o selo cai (`verificado = false`) — igual acontece em redes sociais quando a assinatura paga do selo é cancelada.

#### SolicitacaoVerificacao
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| user_id | BIGINT | - | FK -> users.id — quem está solicitando |
| documento_url | VARCHAR | 255 | Comprovação enviada (documento/prova de identidade ou atividade) |
| descricao | TEXT | - | Justificativa do usuário (nullable) |
| status | VARCHAR | 20 | pendente / aprovada / rejeitada |
| motivo_rejeicao | TEXT | - | Nullable |
| analisado_por_user_id | BIGINT | - | FK -> users.id (admin), nullable até ser analisada |
| analisado_em | DATETIME | - | Nullable |

#### AssinaturaVerificacao
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| user_id | BIGINT | - | FK -> users.id |
| solicitacao_verificacao_id | BIGINT | - | FK -> SolicitacaoVerificacao.id (a que foi aprovada) |
| periodicidade | VARCHAR | 20 | Mensal |
| gateway | VARCHAR | 30 | A definir |
| valor | DECIMAL | 10,2 | Taxa extra do selo — cobrada **além** da Assinatura Membro |
| status | VARCHAR | 20 | ativa / cancelada / inadimplente |
| data_inicio | DATE | - | |
| proxima_cobranca | DATE | - | |

> ⚠️ **Pré-requisito a validar com o negócio:** provavelmente só faz sentido oferecer o selo pago pra quem já é **Membro** (`Assinatura.plano = 'Membro'` ativa) — validar isso na aplicação antes de liberar a `AssinaturaVerificacao`.


Todas as tabelas que antes referenciavam `Cliente.id` ou `Administrador.id` (Assinatura, Mensagem, Palpite, Postagem) agora referenciam **`users.id`** diretamente via `user_id`. A regra de negócio (ex: "Assinatura só existe para quem tem perfil_cliente") passa a ser validada na aplicação, checando a existência do perfil correspondente — e não mais garantida estruturalmente por duas tabelas de identidade separadas.

---

## 4. Demais entidades e atributos

### 4.1 Organizacao
Promotora do evento (UFC, ONE Championship, PFL, Bellator etc.).

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| nome | VARCHAR | 150 | |
| logo_url | VARCHAR | 255 | |
| pais_origem | VARCHAR | 60 | |

### 4.2 Categoria
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| nome | VARCHAR | 50 | MMA, Judô, Boxe |

### 4.3 CategoriaPeso
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| categoria_id | BIGINT | - | FK -> Categoria |
| nome | VARCHAR | 60 | Ex: Peso Palha, Peso Mosca, Peso Galo, Peso Leve |
| peso_minimo_kg | DECIMAL | 5,2 | |
| peso_maximo_kg | DECIMAL | 5,2 | |

### 4.4 Atleta
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| nome | VARCHAR | 150 | |
| apelido | VARCHAR | 100 | Nickname, ex: "The Spider" |
| tipo | VARCHAR | 30 | Ex: lutador |
| equipe | VARCHAR | 150 | |
| pais | VARCHAR | 60 | |
| cidade_natal | VARCHAR | 100 | |
| data_nascimento | DATE | - | |
| altura_cm | INT | - | |
| peso_kg | DECIMAL | 5,2 | Peso de caminhada |
| alcance_cm | INT | - | Envergadura/reach |
| stance | VARCHAR | 20 | Ortodoxo / Canhoto / Switch |
| biografia | TEXT | - | |
| vitorias | INT | - | Total |
| vitorias_ko | INT | - | |
| vitorias_submissao | INT | - | |
| vitorias_decisao | INT | - | |
| empates | INT | - | |
| derrotas | INT | - | Total |
| derrotas_ko | INT | - | |
| derrotas_submissao | INT | - | |
| derrotas_decisao | INT | - | |
| invicto | BOOLEAN | - | true se derrotas = 0 |
| ranking | INT | - | Idealmente por CategoriaPeso |
| user_id | BIGINT | - | FK -> users.id, nullable e único. Preenchido só se este atleta (ex: ex-lutador) tiver conta pra logar, ex: como comentarista |

#### Estilos de luta (N:N) e Treinador
`EstiloDeLuta` (id, nome) + `AtletaEstilo` (atleta_id, estilo_id, treinador_id) — o `treinador_id` fica na associativa porque o atleta pode ter um treinador por estilo, ou um único para tudo.

**Treinador**

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| user_id | BIGINT | - | FK -> users.id, nullable e único. Preenchido só se este treinador tiver conta pra logar no sistema |
| nome | VARCHAR | 150 | |
| pais | VARCHAR | 60 | |

#### AtletaFoto
Um atleta pode ter **até 3 fotos** (diferente do cliente, que tem só 1). Por isso é uma tabela `1—N` separada, não uma coluna `foto_url` direto no Atleta.

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| atleta_id | BIGINT | - | FK -> Atleta |
| foto_url | VARCHAR | 255 | |
| ordem | INT | - | 1, 2 ou 3 — ordem de exibição |
| principal | BOOLEAN | - | true = foto de capa, exibida em listagens/cards |

> ⚠️ O limite de 3 fotos não é garantido pelo banco (não existe "máximo de linhas relacionadas" nativo em SQL) — precisa ser validado na aplicação, no `FormRequest`/`Action` que processa o upload (ex: `if ($atleta->fotos()->count() >= 3) { throw ValidationException }`).

#### Juiz
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| nome | VARCHAR | 150 | |
| pais | VARCHAR | 60 | |
| certificado_por | VARCHAR | 150 | Ex: ABC (Association of Boxing Commissions) |

### 4.5 Evento
Representa a noite do evento (o "card" completo).

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| nome | VARCHAR | 200 | Ex: "UFC 300" |
| organizacao_id | BIGINT | - | FK -> Organizacao |
| data | DATETIME | - | |
| local | VARCHAR | 200 | Arena/ginásio |
| cidade | VARCHAR | 100 | |
| pais | VARCHAR | 60 | |
| link_canal_youtube | VARCHAR | 255 | |
| tipo_transmissao | VARCHAR | 20 | Free / PPV |
| status | VARCHAR | 20 | agendado / ao_vivo / encerrado |

### 4.6 Luta
Cada combate dentro de um Evento.

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| evento_id | BIGINT | - | FK -> Evento |
| categoria_id | BIGINT | - | FK -> Categoria |
| categoria_peso_id | BIGINT | - | FK -> CategoriaPeso |
| participante_a_id | BIGINT | - | FK -> Atleta |
| participante_b_id | BIGINT | - | FK -> Atleta |
| ordem_na_card | INT | - | 1 = luta principal |
| tipo_card | VARCHAR | 20 | Main Card / Prelims |
| numero_rounds | INT | - | 3 (padrão) ou 5 (título/principal) |
| chance_do_a | DECIMAL | 5,2 | Probabilidade implícita (%) |
| chance_do_b | DECIMAL | 5,2 | |
| juiz_id | BIGINT | - | FK -> Juiz (juiz principal) |
| status | VARCHAR | 20 | agendada / em_andamento / encerrada |
| vencedor_id | BIGINT | - | FK -> Atleta (nullable até o fim) |
| metodo_vitoria | VARCHAR | 30 | KO/TKO, Submissão, Decisão Unânime/Dividida/Majoritária, Empate, Sem Resultado, Desqualificação |
| round_fim | INT | - | |
| tempo_fim | VARCHAR | 10 | mm:ss |

**Regras:** a luta só fica disponível para palpite a partir da data do evento; clientes Membro podem mudar de opinião ao longo dos rounds.

### 4.7 Placar
Pontuação round a round de cada juiz — sistema **10-point must**.

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| luta_id | BIGINT | - | FK -> Luta |
| juiz_id | BIGINT | - | FK -> Juiz |
| round | INT | - | |
| pontos_atleta_a | INT | - | 10, 9, 8, 7... |
| pontos_atleta_b | INT | - | |

### 4.8 Mensagem
Histórico de chat da luta — **uma linha por mensagem enviada**, sem carregar o palpite.

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| luta_id | BIGINT | - | FK -> Luta |
| user_id | BIGINT | - | FK -> users.id (usuário deve possuir perfil_cliente) |
| mensagem | TEXT | - | Texto do comentário |

### 4.9 Palpite
Estado **atual** do palpite de cada usuário para cada luta — **uma linha por usuário por luta** (não por mensagem), atualizada in-place a cada troca.

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| luta_id | BIGINT | - | FK -> Luta |
| user_id | BIGINT | - | FK -> users.id (usuário deve possuir perfil_cliente) |
| vencedor_escolhido_id | BIGINT | - | FK -> Atleta — palpite vigente |
| trocas_de_opiniao | INT | - | Incrementado a cada vez que `vencedor_escolhido_id` é alterado |

**Constraint importante:** `UNIQUE(luta_id, user_id)` em `Palpite` — garante que existe **no máximo um palpite vigente** por usuário por luta. No Laravel, usar `updateOrCreate(['luta_id' => ..., 'user_id' => ...], ['vencedor_escolhido_id' => ..., 'trocas_de_opiniao' => DB::raw('trocas_de_opiniao + 1')])` (com a devida checagem de que o valor realmente mudou antes de incrementar).

**Como as duas se relacionam:** `Mensagem` e `Palpite` não têm FK direta entre si — ambas apontam para `luta_id` + `user_id`. Na tela, a UI cruza as duas (busca o `Palpite` vigente do usuário e mostra ao lado do histórico de `Mensagem`), mas são entidades independentes no banco: uma é log imutável (mensagens), a outra é estado mutável (palpite atual).

### 4.9 Assinatura
| Plano | Nível de acesso |
|---|---|
| **Free** | Só leitura + escolhe o vencedor |
| **Membro** (paga) | Free + opina no chat |

| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| user_id | BIGINT | - | FK -> users.id (usuário deve possuir perfil_cliente) |
| plano | VARCHAR | 20 | Free / Membro |
| periodicidade | VARCHAR | 20 | Mensal |
| gateway | VARCHAR | 30 | **Em aberto** |
| status | VARCHAR | 20 | ativa / cancelada / inadimplente |
| valor | DECIMAL | 10,2 | |
| data_inicio | DATE | - | |
| proxima_cobranca | DATE | - | |

### 4.10 Patrocinador
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| nome | VARCHAR | 150 | |
| logo_url | VARCHAR | 255 | |
| link_site | VARCHAR | 255 | |
| email_contato | VARCHAR | 150 | |
| status | VARCHAR | 20 | ativo / inativo |
| data_inicio_contrato | DATE | - | |
| data_fim_contrato | DATE | - | |

### 4.11 Banner
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| patrocinador_id | BIGINT | - | FK -> Patrocinador |
| imagem_url | VARCHAR | 255 | |
| link_destino | VARCHAR | 255 | |
| posicao | VARCHAR | 30 | Home, Evento, Luta, Chat, Categoria, Blog |
| data_inicio | DATE | - | |
| data_fim | DATE | - | |
| status | VARCHAR | 20 | ativo / pausado / expirado |
| modelo_cobranca | VARCHAR | 20 | CPM, CPC ou Fixo |
| valor_contrato | DECIMAL | 10,2 | |
| impressoes | INT | - | |
| cliques | INT | - | |
| ordem_exibicao | INT | - | |

### 4.12 Postagem
| Atributo | Tipo | Tamanho | Observações |
|---|---|---|---|
| id | BIGINT | - | PK |
| titulo | VARCHAR | 200 | |
| slug | VARCHAR | 220 | URL amigável, único |
| conteudo | TEXT | - | |
| meta_description | VARCHAR | 160 | |
| imagem_capa | VARCHAR | 255 | |
| user_id | BIGINT | - | FK -> users.id — autor (usuário deve possuir perfil_administrador) |
| patrocinador_id | BIGINT | - | FK -> Patrocinador (nullable) |
| patrocinado | BOOLEAN | - | true se patrocinador_id preenchido |
| fonte_original_url | VARCHAR | 255 | Repostagens |
| categoria_id | BIGINT | - | FK -> Categoria (nullable) |
| status | VARCHAR | 20 | rascunho / publicado / arquivado |
| data_publicacao | DATE | - | |

---

## 5. Relacionamentos (resumo)

- **users** 1—1 **perfil_cliente** E/OU 1—1 **perfil_administrador** (não é exclusivo — um usuário pode ter os dois)
- **users** N—N **Papel** (via UserPapel) — papéis só-permissão, ex: Comentarista
- **users** 1—0/1 **Treinador** (opcional — treinador pode ou não ter conta)
- **users** 1—0/1 **Atleta** (opcional — ex-lutador pode ou não ter conta)
- **users** 1—N **SolicitacaoVerificacao**, 1—N **AssinaturaVerificacao**
- **users** 1—N **Assinatura**, 1—N **Mensagem**, 1—N **Palpite**, 1—N **Postagem** (como autor)
- **Organizacao** 1—N **Evento**
- **Evento** 1—N **Luta**
- **Luta** N—1 **Categoria**, N—1 **CategoriaPeso**
- **Luta** N—1 **Atleta** (A, B e vencedor)
- **Luta** N—1 **Juiz** (principal) e 1—N **Placar**
- **Placar** N—1 **Juiz**
- **CategoriaPeso** N—1 **Categoria**
- **Atleta** N—N **EstiloDeLuta** (via AtletaEstilo, que define o Treinador por estilo)
- **Atleta** 1—N **AtletaFoto** (máx. 3, validado na aplicação)
- **Luta** 1—N **Mensagem**, 1—N **Palpite**
- **Patrocinador** 1—N **Banner**, 1—N **Postagem**
- **Categoria** 1—N **Postagem** (opcional)

---

## 6. Regras de negócio consolidadas

1. Um `user` pode ter `perfil_cliente`, `perfil_administrador`, ou os dois ao mesmo tempo — o papel é definido pela existência do perfil, não por um campo exclusivo.
2. Cliente precisa ser maior de 18 anos (`perfil_cliente.maior_de_18`).
3. **Cliente loga por CPF**; **Administrador loga por email** — mesma tabela `users`, fluxo de login diferente por tipo.
4. CPF é criptografado no banco.
5. Plano Free: só leitura + escolhe vencedor. Plano Membro: + opina no chat.
6. O palpite pode mudar ao longo dos rounds — cada mudança é contabilizada.
7. A luta só é liberada para palpite a partir da data do evento.
8. Um atleta pode ter múltiplos estilos de luta, cada um com seu próprio treinador (ou um único para todos).
9. Toda luta tem juízes que pontuam round a round pelo sistema 10-point must.
16. Atleta pode ter até 3 fotos (`AtletaFoto`); Cliente tem apenas 1 foto de perfil (`perfil_cliente.foto_perfil_url`).
17. Papéis "só-permissão" (Comentarista etc.) usam `Papel` + `UserPapel` (N:N) — não geram tabela nova por papel.
18. Treinador e Atleta podem, opcionalmente, ter conta de login (`user_id` nullable nessas tabelas) sem duplicar seus dados de domínio.
19. O selo de verificado (`users.verificado`) exige uma `SolicitacaoVerificacao` aprovada por um Administrador **e** uma `AssinaturaVerificacao` ativa — é uma cobrança recorrente separada da Assinatura Membro, e cai automaticamente se essa cobrança for cancelada ou ficar inadimplente.
10. Decisão: vence quem tiver mais pontos em pelo menos 2 dos 3 scorecards.
11. Um Evento agrupa várias Lutas, organizadas por `ordem_na_card`.
12. Todo `id` é BIGINT PK; toda tabela tem também `uuid` só para uso em URLs.
13. Toda tabela usa soft delete e carrega os campos de auditoria `audit_*`.
14. Banner só aparece se `status = ativo` e dentro do período contratado.
15. Postagem com patrocinador deve ser marcada `patrocinado = true` e exibir identificação de publicidade.

---

## 7. Pontos em aberto

- [ ] Definir o Gateway de pagamento (Stripe, Mercado Pago, PagSeguro etc.)
- [ ] Confirmar se `perfil_cliente.tipo` é realmente necessário ou é redundante com `Assinatura.plano`
- [ ] Definir se uma Luta pode ter mais de um juiz vinculado diretamente (hoje `juiz_id` é único na Luta; os demais aparecem via Placar)
- [ ] Definir regras de cancelamento/inadimplência do plano Membro
- [ ] Definir se `chance_do_a`/`chance_do_b` são manuais ou calculadas
- [ ] Definir periodicidades futuras além de Mensal
- [ ] Definir preços/planos fixos dos banners por posição
- [ ] Definir painel de métricas para o Patrocinador
- [ ] Definir política de moderação para repostagens (direitos autorais)
- [ ] Validar no código de aplicação (não há mais garantia estrutural do banco) que `Assinatura.user_id`, `Mensagem.user_id` e `Palpite.user_id` sempre apontam para usuários com `perfil_cliente`, e que `Postagem.user_id` sempre aponta para usuários com `perfil_administrador`
- [ ] Definir como a UI representa o "contexto ativo" (ver como Admin / ver como Cliente) para usuários com os dois perfis — sugestão: guardar em sessão, não em coluna de banco
- [ ] Confirmar se o selo pago exige ser Membro ativo como pré-requisito, ou se qualquer usuário (mesmo Free) pode contratar
- [ ] Definir o valor da `AssinaturaVerificacao` e se ela usa o mesmo Gateway da Assinatura normal ou um separado
- [ ] Definir prazo/SLA para o Administrador analisar uma `SolicitacaoVerificacao`
- [ ] Garantir a constraint `UNIQUE(luta_id, user_id)` na tabela `Palpite` na migration (`$table->unique(['luta_id', 'user_id'])`)
- [ ] Implementar validação de limite de 3 fotos por atleta na camada de aplicação (não é garantido pelo banco)
