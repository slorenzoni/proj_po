# Análise da Modelagem — Sistema de Palpites em Eventos de Luta

> **Fontes analisadas:**
> - `documentacao-sistema-eventos-luta(5)(2).md` (v3)
> - `dicionario-dados-sistema-eventos-luta(4)(2).xlsx` (25 tabelas + legenda)
>
> **Stack:** Laravel 13 (PHP 8.4) + MySQL 8.4 + Inertia v3 + Vue 3 + Fortify + Pest 5 — versões exatas na seção 8.1
>
> **Data da análise:** 30/09/2026 · **Última atualização:** 04/10/2026
>
> **Atenção:** as seções 2 a 5 registram a análise original. Onde uma decisão posterior mudou o rumo, há uma nota "Decidido" apontando para a seção 8, que é a que prevalece.

---

## 1. Resumo

Plataforma de palpites em lutas (MMA, Judô, Boxe) com:

- **Plano Free** — só leitura + escolhe o vencedor.
- **Plano Membro** (pago) — Free + comenta no chat da luta + muda o palpite ao longo dos rounds.
- **Painel admin** — cadastro de organizações, eventos, lutas, atletas, juízes, categorias.
- **Patrocínio** — banners e blog patrocinado.
- **Selo de verificado** — cobrança recorrente à parte, após aprovação de um admin.

### Consistência entre os dois arquivos

A documentação e o dicionário **estão coerentes entre si**. As 25 abas do dicionário correspondem às entidades da documentação v3, e a planilha detalha explicitamente em cada tabela o que a documentação define como convenção global (`uuid`, timestamps, `deleted_at` e os 10 campos `audit_*`). Não foram encontradas divergências de campos.

Tabelas: `users`, `perfil_cliente`, `perfil_administrador`, `Papel`, `UserPapel`, `SolicitacaoVerificacao`, `AssinaturaVerificacao`, `Organizacao`, `Categoria`, `CategoriaPeso`, `Atleta`, `EstiloDeLuta`, `AtletaEstilo`, `AtletaFoto`, `Treinador`, `Juiz`, `Evento`, `Luta`, `Placar`, `Mensagem`, `Palpite`, `Assinatura`, `Patrocinador`, `Banner`, `Postagem`.

Os problemas apontados abaixo surgem ao confrontar a modelagem com o **Laravel** e com o **próprio domínio**.

---

## 2. Problemas técnicos (Laravel)

### 2.1 CPF criptografado não funciona como `UNIQUE` nem como login — **crítico**

> **Decidido (01/10/2026):** o CPF não é armazenado e o login é por e-mail para todos — ver 8.2, item 1. O problema abaixo deixou de existir.

- O cast `encrypted` do Laravel gera um texto cifrado **diferente a cada gravação** do mesmo valor (IV aleatório).
- Consequências:
  - O índice `UNIQUE` em `cpf` não impede duplicatas.
  - `User::where('cpf', $cpf)` no login **nunca encontra** o registro.
  - O texto cifrado tem centenas de caracteres e **não cabe em `VARCHAR(14)`**.
- **Sugestão:**
  - `cpf` → `TEXT`, com cast `encrypted` (para exibição/uso).
  - Nova coluna `cpf_hash` → `VARCHAR(64)`, `UNIQUE`, contendo um HMAC-SHA256 do CPF normalizado (só dígitos), usando uma chave da aplicação. É ela que é usada na busca do login e na validação de unicidade.

### 2.2 Nomes de colunas de `users` fora do padrão Laravel/Fortify

> **Decidido (01/10/2026):** opção A, nomes padrão do Laravel — ver 8.2, item 2.

- O padrão espera `name`, `password` e `remember_token`; o starter kit também cria colunas de 2FA (`two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`).
- O dicionário usa `nome` e `senha_hash`, e **não prevê `remember_token`** (necessário para o "lembrar-me").
- **Opções:**
  - **A)** Adotar os nomes padrão do Laravel em `users` (menos atrito com Fortify, starter kit e pacotes).
  - **B)** Manter os nomes em português e adaptar o model (`getAuthPasswordName()`), as Actions do Fortify e o starter kit.

### 2.3 Método de UUID incorreto na documentação

- A documentação (seção 2.1) cita `uuidColumns()`. No Laravel atual o método é **`uniqueIds()`**:

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

public function uniqueIds(): array
{
    return ['uuid'];
}

public function getRouteKeyName(): string
{
    return 'uuid';
}
```

- Como `id` não está em `uniqueIds()`, ele continua sendo BIGINT auto-incremento — que é o comportamento desejado.
- Sugestão: centralizar isso num trait próprio (ex.: `HasPublicUuid`) para não repetir em 25 models.

### 2.4 Soft delete × índices únicos

> **Decidido (04/10/2026):** não há índice único de negócio no banco; a unicidade é validada só na aplicação — ver 8.2, item 5.

Com soft delete, o registro "apagado" continua na tabela e **continua ocupando a chave única**. Afeta:

| Tabela | Índice único | Efeito |
|---|---|---|
| `users` | `email`, `cpf` | Usuário excluído impede novo cadastro com o mesmo email/CPF |
| `perfil_cliente` / `perfil_administrador` | `user_id` | Perfil excluído impede recriar o perfil |
| `Palpite` | `(luta_id, user_id)` | Palpite excluído impede novo palpite na mesma luta |
| `Postagem` | `slug` | Post excluído "trava" o slug |
| `Atleta` / `Treinador` | `user_id` | Vínculo excluído impede novo vínculo |

**Opções por caso:** restaurar o registro (`restore()`) em vez de criar outro; ou incluir `deleted_at` no índice (atenção: no MySQL, `NULL` não colide em índice único, o que pode reabrir duplicatas); ou não usar soft delete nessa tabela específica.

### 2.5 Pivot `UserPapel` com soft delete, uuid e auditoria

- `belongsToMany` **não respeita soft delete** no pivot — linhas excluídas continuariam aparecendo.
- Exige um model de Pivot próprio (`->using(UserPapel::class)`) e filtro explícito (`->wherePivotNull('deleted_at')`).
- Falta `UNIQUE(user_id, papel_id)`.

### 2.6 Nomenclatura das tabelas

> **Decidido (01/10/2026):** snake_case no plural em português, com o nome da tabela explícito em cada model — ver 8.2, item 3.

- O dicionário usa PascalCase no singular (`Luta`, `UserPapel`, `CategoriaPeso`).
- O Laravel espera snake_case no plural (`lutas`, `papel_user`, `categoria_pesos`).
- **Opções:**
  - **A)** Seguir a convenção Laravel nas tabelas (o dicionário serve de referência lógica).
  - **B)** Manter os nomes do dicionário e declarar `protected $table` em cada model + nomes de pivot/FK explícitos nos relacionamentos.

> Observação: a pluralização automática do Laravel é em inglês (`luta` → `lutas` funciona, mas `papel` → `papels`). Com a opção A, alguns nomes precisarão de `$table` explícito mesmo assim.

### 2.7 Índices faltando

> **Decidido (04/10/2026):** os índices `UNIQUE` desta tabela não serão criados no banco (ver 8.2, item 5); as regras correspondentes passam a ser validações na aplicação.

| Tabela | Índice sugerido | Motivo |
|---|---|---|
| `Placar` | `UNIQUE(luta_id, juiz_id, round)` | Um juiz pontua cada round uma única vez |
| `AtletaEstilo` | `UNIQUE(atleta_id, estilo_id)` | Evitar estilo duplicado no mesmo atleta |
| `UserPapel` | `UNIQUE(user_id, papel_id)` | Evitar papel duplicado |
| `AtletaFoto` | `UNIQUE(atleta_id, ordem)` | Garante no máx. uma foto por posição (ajuda no limite de 3) |
| `Banner` | `INDEX(posicao, status, data_inicio, data_fim)` | Consulta de exibição |

### 2.8 Campos de status/tipo como VARCHAR livre

Sugestão: **backed enums do PHP 8.4** com cast no model (`Evento.status`, `Luta.status`, `Luta.metodo_vitoria`, `Assinatura.plano`, `Assinatura.status`, `Banner.posicao`, `Banner.modelo_cobranca`, `Postagem.status`, `SolicitacaoVerificacao.status` etc.). A coluna no banco pode continuar VARCHAR.

### 2.9 Auditoria

- Os campos `audit_*` guardam apenas **quem fez a última alteração** — não há histórico de alterações anteriores. Confirmar se isso atende ao cliente ou se é necessária uma tabela de log/histórico.
- `audit_db_user` não é obtido pela requisição HTTP; precisa ser lido do banco (ex.: `SELECT CURRENT_USER()`). Definir se é realmente necessário.

---

## 3. Inconsistências de negócio (validar com o cliente)

> **Decidido (01/10/2026):** os pontos 3.1, 3.2, 3.3, 3.6, 3.8 e 3.10 foram resolvidos — ver 8.2, itens 10, 7, 6, 11, 8 e 1, nessa ordem.

| # | Ponto | Detalhe |
|---|---|---|
| 3.1 | **Comentarista não pode comentar** | `Mensagem.user_id` exige `perfil_cliente`, mas Comentarista, Ex-lutador e Treinador existem justamente para comentar e podem não ter esse perfil. |
| 3.2 | **Placar do Judô** | Judô não usa 10-point must (pontua por ippon / waza-ari / shido). `Placar` hoje só atende MMA e Boxe profissional. |
| 3.3 | **Regra 10 × modelo de juízes** | A regra diz "vence quem ganhar 2 de 3 scorecards", mas `Luta` tem só um `juiz_id`; os outros aparecem só via `Placar`. Sugestão: tabela `LutaJuiz` (N:N). |
| 3.4 | **Quem pode mudar o palpite?** | A visão geral diz que só o Membro muda ao longo dos rounds; a regra 6 não faz essa restrição. |
| 3.5 | **Regra 7 possivelmente invertida** | "A luta só é liberada para palpite a partir da data do evento." O usual é abrir antes e **fechar** quando a luta começa (ou quando o round começa, para Membro). |
| 3.6 | **Plano Free gera `Assinatura`?** | Se sim, com `valor = 0` e qual gateway? Se não, "Free" é a ausência de assinatura ativa. |
| 3.7 | **Dados redundantes** | `Luta.categoria_id` (deduzível de `categoria_peso_id`), `Postagem.patrocinado` (deduzível de `patrocinador_id`), `Atleta.invicto` e totais de vitórias/derrotas (deduzíveis dos detalhamentos). Risco de ficarem inconsistentes. |
| 3.8 | **Chat em tempo real** | A documentação não define infraestrutura de tempo real. Vai exigir broadcasting (ex.: Laravel Reverb) — dependência nova. |
| 3.9 | **Ranking do atleta** | `Atleta.ranking` é um único inteiro, mas o próprio dicionário diz "idealmente por categoria de peso". Um atleta pode estar ranqueado em mais de uma categoria/organização. |
| 3.10 | **Login por CPF × Fortify** | Fortify trabalha com um único campo de usuário. Login por CPF (cliente) e por email (admin) exige `Fortify::authenticateUsing()` customizado. |
| 3.11 | **`perfil_cliente.tipo`** | Já listado como ponto em aberto — continua sem definição do que é "classificação comercial". |

---

## 4. Ajustes na própria documentação

- Há **duas seções 4.9** (Palpite e Assinatura).
- Falta a seção **3.3** (pula de 3.2 para 3.4).
- As regras de negócio da seção 6 estão **fora de ordem** (16–19 aparecem antes de 10).
- Seção 2.1 cita `uuidColumns()` — o correto é `uniqueIds()` (ver 2.3).

---

## 5. Decisões necessárias antes das migrations

- [x] **CPF:** adotar `cpf` criptografado (`TEXT`) + `cpf_hash` (`UNIQUE`) para busca? (2.1) → **decidido:** CPF não é armazenado (8.2, item 1)
- [x] **`users`:** nomes padrão Laravel (`name`, `password`, `remember_token`) ou manter em português? (2.2) → **decidido:** padrão Laravel (8.2, item 2)
- [x] **Soft delete × unique:** estratégia por tabela. (2.4) → **decidido:** validação só na aplicação (8.2, item 5)
- [x] **Nomes de tabelas:** convenção Laravel ou nomes do dicionário? (2.6) → **decidido:** snake_case plural em português (8.2, item 3)
- [x] **Juízes por luta:** criar `LutaJuiz`? (3.3) → **decidido:** tabela `luta_juizes` (8.2, item 6)
- [x] **Mensagem:** quem pode comentar além do Cliente Membro? (3.1) → **decidido** (8.2, item 10)
- [x] **Placar do Judô:** modelar agora ou fora do escopo inicial? (3.2) → **decidido:** Judô sem placar (8.2, item 7)
- [x] **Janela de palpite:** quando abre e quando fecha? (3.4 / 3.5) → **decidido**, ver 6.2
- [x] **Plano Free:** gera ou não registro em `Assinatura`? (3.6) → **decidido:** gera (8.2, item 11)
- [x] **Tempo real:** aprovação para usar Reverb (ou alternativa). (3.8) → **decidido:** sem tempo real (8.2, item 8)
- [ ] **Auditoria:** só último autor ou histórico completo? (2.9)

### Decisões derivadas da seção 6

- [ ] **Ranking geral:** acumulado de todo o histórico ou janela móvel (ex.: últimos 10 eventos, como o Tapology)? (6.4)
- [ ] **Prazo de pontuação do placar dos fãs:** quantos minutos após o fim do round? (6.3)
- [x] **Quem atualiza o andamento ao vivo da luta** (`round_atual` / intervalo): admin manualmente ou integração com fonte externa? (6.2) → **decidido:** admin, manualmente (8.2, item 9)
- [x] **Método de vitória no Judô:** as categorias KO/TKO / Finalização / Decisão não se aplicam — definir equivalentes ou excluir Judô dos palpites com método. (6.1) → **decidido:** métodos próprios (8.2, item 7)
- [ ] **Desempate do ranking:** confirmar critérios propostos. (6.4)

---

## 6. Decisões de produto aprovadas

> Aprovadas por Sandro em 30/09/2026. Os valores numéricos (pontos, pesos, prazos) são **ponto de partida** e devem ser **configuráveis** (tabela ou `config/`), não fixos no código.

### 6.1 Palpite com método e round (opcionais)

**Regras:**

- **Vencedor:** obrigatório.
- **Método:** opcional — apenas 3 opções: **KO/TKO**, **Finalização**, **Decisão**. (Não se detalha unânime/dividida/majoritária no palpite.)
- **Round:** opcional — **desabilitado quando o método for "Decisão"**, pois decisão sempre ocorre no último round.
- Método e round só pontuam se o **vencedor estiver correto**.

**Pontuação base:**

| Acerto | Pontos |
|---|---|
| Só vencedor | 10 |
| Vencedor + método | 15 |
| Vencedor + round | 15 |
| Vencedor + método + round (perfeito) | 22 |
| Errou o vencedor | 0 |
| Empate, Sem Resultado (No Contest) ou Desqualificação | 0 para todos |

**Impacto no modelo:**

- `Palpite`: novos campos `metodo_escolhido` (nullable), `round_escolhido` (nullable), `pontos_obtidos` (nullable até a luta ser encerrada).
- `Luta.metodo_vitoria` precisa ser **mapeado** para as 3 categorias do palpite (ex.: Decisão Unânime/Dividida/Majoritária → Decisão).
- A pontuação é calculada quando a `Luta` passa para `encerrada` (processamento assíncrono via Job).

### 6.2 Palpite pré-luta × palpite ao vivo (peso por momento)

**Regras:**

| Tipo | Quem | Quando |
|---|---|---|
| **Pré-luta** | Free e Membro | Do cadastro da luta até o **início da luta**. Edição livre, sem penalidade. Trava ao iniciar. |
| **Ao vivo** | Somente Membro | Apenas **nos intervalos entre rounds**. **Bloqueado durante o round.** |

- O **peso** aplicado sobre a pontuação base (6.1) é definido pelo **momento da última troca**.
- **Voltar ao palpite original não recupera o peso cheio** (evita burla).
- Se um atleta da luta for **substituído**, os palpites daquela luta são **zerados** e os usuários precisam palpitar novamente (comportamento do Verdict MMA). Se a luta for **cancelada**, os palpites são descartados sem pontuação.

**Tabela de pesos (ponto de partida):**

| Momento da última troca | Luta de 3 rounds | Luta de 5 rounds |
|---|---|---|
| Pré-luta | 100% | 100% |
| Intervalo após R1 | 70% | 80% |
| Intervalo após R2 | 40% | 60% |
| Intervalo após R3 | — | 40% |
| Intervalo após R4 | — | 20% |

> Exemplo: palpite perfeito (22 pts) trocado no intervalo após o R2 de uma luta de 3 rounds → 22 × 40% = **8,8 pts**.

**Impacto no modelo:**

- `Palpite`: novos campos `round_da_troca` (INT, 0 = pré-luta) e `peso_aplicado` (DECIMAL). O campo `trocas_de_opiniao` passa a ser derivável do histórico.
- **Nova tabela `PalpiteHistorico`** — log imutável de cada troca: `palpite_id`, `vencedor_escolhido_id`, `metodo_escolhido`, `round_escolhido`, `round_da_troca`, data/hora. Serve para auditoria e contestação.
- `Luta`: novos campos de andamento ao vivo — `round_atual` (INT) e `em_intervalo` (BOOLEAN) — usados para liberar/bloquear a troca. Precisam ser atualizados em tempo real (admin ou integração — ver pendências na seção 5).
- ~~Exige **broadcasting** (ex.: Laravel Reverb) para abrir/fechar a janela de troca na tela dos usuários.~~ **Decidido (01/10/2026):** sem tempo real; o servidor valida a janela de troca no envio do palpite (8.2, item 8).

### 6.3 Placar dos fãs (round a round)

**Regras:**

- Liberado para **Free e Membro**.
- O usuário pontua cada round pelo sistema **10-point must** (vencedor do round = 10; perdedor = 7 a 9; empate = 10-10).
- A pontuação de um round **abre quando o round termina** e fecha após um prazo configurável.
- Cada usuário pontua cada round **uma única vez**.
- O sistema exibe a **média da comunidade** por round e no total, ao lado do placar oficial dos juízes (`Placar`), quando houver.
- **Não se aplica ao Judô** (sem rounds / sem 10-point must).

**Impacto no modelo:**

- **Nova tabela `PlacarFan`**: `luta_id`, `user_id`, `round`, `pontos_atleta_a`, `pontos_atleta_b` + `UNIQUE(luta_id, user_id, round)`.
- Validação: um dos lados = 10; o outro entre 7 e 10.
- A média pode ser calculada sob demanda ou mantida em cache/tabela agregada para exibição ao vivo.

### 6.4 Rankings

**Escopos aprovados:**

| Ranking | Abrangência |
|---|---|
| **Geral** | Todos os eventos (janela de tempo a definir — ver seção 5) |
| **Por evento** | Soma das lutas de um `Evento` |
| **Por organização** | Soma dos eventos de uma `Organizacao` (UFC, PFL etc.) |

**Critérios de desempate (proposta, baseada no EventClock):**

1. Maior número de palpites perfeitos (vencedor + método + round).
2. Maior número de vencedores corretos.
3. Quem palpitou primeiro (data do palpite mais antiga).

**Impacto no modelo:**

- Fonte de verdade: `Palpite.pontos_obtidos` (já com o peso aplicado).
- **Nova tabela `Ranking`** (materializada, para não recalcular a cada acesso): `escopo` (geral / evento / organizacao), `referencia_id` (nullable — id do evento ou da organização), `user_id`, `pontos`, `palpites_perfeitos`, `vencedores_corretos`, `posicao`.
- Atualizada por Job ao encerrar cada luta.

### 6.5 Resumo das mudanças no modelo de dados

| Tabela | Tipo de mudança |
|---|---|
| `Palpite` | + `metodo_escolhido`, `round_escolhido`, `pontos_obtidos`, `round_da_troca`, `peso_aplicado` |
| `Luta` | + `round_atual`, `em_intervalo` |
| `PalpiteHistorico` | **Nova** |
| `PlacarFan` | **Nova** |
| `Ranking` | **Nova** |
| Configuração | Pontos (6.1), pesos (6.2), prazo do placar dos fãs (6.3) |

> Todas as tabelas novas seguem as convenções da documentação: `id` BIGINT + `uuid`, timestamps, soft delete e campos `audit_*` (exceto onde a seção 2.4 recomendar o contrário).

---

## 7. Benchmark de mercado

> Pesquisa realizada em 30/09/2026.

### 7.1 Produtos analisados

| App | Proposta | Monetização |
|---|---|---|
| **Tapology** | Base de dados de lutas + Pick'em + fórum | Assinatura sem anúncios (a partir de US$ 2,99/mês); mantém patrocínios próprios |
| **Verdict MMA** | Palpites + **placar dos fãs round a round** ("Global Scorecard") + fórum | Torneios pagos com prêmio (apenas em regiões licenciadas) |
| **EventClock** | Pick'em com pontuação | Gratuito |
| **MMA Fantasy** (tem versão pt-BR) | Palpites + ligas privadas + duelos 1×1 | Moeda virtual, cosméticos; gratuito |
| **Combatscores / Fourounce** | Pontuação de rounds pelos fãs + placar coletivo | Gratuito / ligas |
| **Underdog / Chalkboard** | Fantasy pago (EUA) | Aposta regulada |

### 7.2 Comparação com o PROJ_PO

| Tema | Mercado | PROJ_PO (original) | PROJ_PO (após seção 6) |
|---|---|---|---|
| Tipo de palpite | Vencedor + método + round, com pontos | Só vencedor, sem pontos | Vencedor obrigatório + método/round opcionais, com pontos |
| Janela de palpite | Abre dias antes; trava no início do evento/luta | Abre só na data do evento | Abre no cadastro; trava no início da luta |
| Troca durante a luta | Nenhum concorrente oferece | Membro troca livremente | Membro troca só nos intervalos, com peso decrescente — **diferencial** |
| Troca de adversário | Verdict zera os palpites da luta | Não tratado | Palpites zerados |
| Ranking | Todos têm (Tapology com níveis, Verdict com faixas de BJJ) | Não existe | Geral, por evento e por organização |
| Placar dos fãs | Verdict e Combatscores (referência citada por atletas) | Não existe | Liberado para Free e Membro |
| Chat / fórum | Gratuito em todos | Pago (Membro) | Pago (Membro) — **reavaliar** |
| Selo de verificado | Nenhum app de luta tem | Pago, recorrente | Sem alteração — **demanda duvidosa** |
| Ligas privadas / gamificação | Todos têm | Não existe | Fora do escopo inicial |

### 7.3 Comparação com uma API real de dados de MMA (UFCalendar)

| Dado | API de mercado | PROJ_PO |
|---|---|---|
| Ranking dos atletas | Histórico por divisão; campeão = posição 0 | Único `Atleta.ranking` (INT) |
| Luta de título | Indicador próprio | Não existe (inferido por `numero_rounds = 5`) |
| Mudanças no card | Histórico de alterações | Não existe |
| Cartel | Por organização e modalidade | Totais únicos no Atleta |
| Juízes | 3 papeletas oficiais por decisão | 1 `juiz_id` + `Placar` |
| Estatísticas por round | Golpes por round | Não existe |

Reforça os pontos **3.3** (juízes por luta) e **3.9** (ranking do atleta) e indica que cartel e ranking do atleta devem ser separados **por modalidade/organização**.

### 7.4 Judô

Pelas regras da IJF, o Judô **não tem rounds nem 10-point must**: usa ippon, waza-ari, yuko (reintroduzido em 2025) e shido; penalidades só decidem a luta em caso de hansoku-make. Confirma o ponto **3.2**: `Luta`, `Placar`, palpite por método/round e `PlacarFan` precisam variar por modalidade — ou o Judô fica fora do escopo inicial.

### 7.5 Aspecto legal no Brasil (não é parecer jurídico)

- **Lei 14.790/2023:** explorar apostas de quota fixa exige autorização do Ministério da Fazenda.
- **Art. 49:** *fantasy sport* não é aposta e dispensa autorização, desde que cumpra as condições da lei.
- O PROJ_PO hoje é palpite **sem prêmio** — fora desse risco.
- **Restrição de produto:** qualquer futura premiação ligada a palpite (especialmente combinada com assinatura paga) deve passar por **avaliação jurídica antes** da implementação.

### 7.6 Fontes

- [Tapology – Pick 'Em Leaderboards](https://www.tapology.com/promotion-predictions-leaderboards)
- [Tapology – Terms of Use](https://www.tapology.com/terms_of_use)
- [Verdict MMA – FAQ](https://verdictmma.com/frequently-asked-questions)
- [Verdict MMA – Round Scoring](https://verdictmma.com/round-scoring)
- [EventClock – Pick 'Em](https://eventclock.org/blog/pickems)
- [MMA Fantasy (pt-BR)](https://www.mma-fantasy.com/pt-BR/)
- [Combatscores](https://combatscores.com/)
- [Underdog – Pick'em Scoring MMA](https://help.underdogsports.com/en/articles/10905385-pick-em-scoring-mma)
- [UFCalendar – UFC API](https://www.ufcalendar.com/developers/ufc-api)
- [Judo rules – Wikipedia](https://en.wikipedia.org/wiki/Judo_rules)
- [IJF rule updates – Olympics.com](https://www.olympics.com/en/news/ijf-announces-judo-rule-updates-la2028-cycle)
- [Lei 14.790/2023 – Planalto](https://www.planalto.gov.br/ccivil_03/_ato2023-2026/2023/lei/l14790.htm)
- [Lei 14.790 – Art. 49](https://modeloinicial.com.br/lei/L-14790-2023/lei-bets/art-49)

---

## 8. Ambiente e decisões técnicas

> Atualizado em 04/10/2026. Em caso de divergência com as seções anteriores, com a documentação original ou com o dicionário de dados, **esta seção prevalece**.

### 8.1 Versões em uso

| Componente | Versão | Observação |
|---|---|---|
| PHP | 8.4.2 | `composer.json` exige `^8.3` |
| Laravel | 13.34.0 | `composer.json` exige `^13.17`. A documentação original cita "Laravel 12" |
| MySQL | 8.4.3 | Banco de produção, local e de testes |
| Inertia (Laravel / Vue) | 3.4.0 / 3.x | |
| Vue | 3.5 | Com TypeScript 5 |
| Tailwind CSS | 4.1 | |
| Vite | 8 | |
| Laravel Fortify | 1.40.0 | Autenticação (login, cadastro, 2FA) |
| Laravel Wayfinder | 0.1.21 | Rotas tipadas no front-end |
| Pest | 5.2.1 | Testes |
| Larastan | 3.12.2 | Análise estática |
| Laravel Pint | 1.32.1 | Formatação |
| Node.js | 22.12.0 | |

**Banco de dados local:** MySQL em `127.0.0.1:3306`, banco `proj_po` para a aplicação e `proj_po_testing` para os testes (`phpunit.xml`). Charset `utf8mb4`, collation `utf8mb4_unicode_ci`. As credenciais ficam só no `.env`.

**Versionamento:** git, branch `main`, remoto `https://github.com/slorenzoni/proj_po.git` (criado em 04/10/2026).

### 8.2 Decisões técnicas fechadas

Aprovadas por Sandro em 01/10/2026, salvo indicação em contrário.

| # | Tema | Decisão |
|---|---|---|
| 1 | CPF e login | CPF **não é armazenado**. Login por e-mail para cliente e administrador, no fluxo padrão do Fortify. Se o gateway de pagamento exigir CPF, ele coleta no checkout. |
| 2 | Tabela `users` | Colunas no padrão Laravel (`name`, `email`, `password`, `remember_token` e as de 2FA). Campos de domínio em português (`papel_padrao`, `verificado`, `verificado_em`). |
| 3 | Nomes de tabelas | snake_case no plural em português (`lutas`, `papeis`, `categorias_peso`, `papel_user`, `atleta_fotos`), com o nome declarado em cada model. Models em PascalCase no singular. |
| 4 | Banco de dados | **MySQL** em produção, local e testes (04/10/2026). Substitui o PostgreSQL definido em 01/10/2026. |
| 5 | Soft delete × unicidade | **Validação só na aplicação**, com `Rule::unique(...)->withoutTrashed()` (04/10/2026). O MySQL não tem índice único parcial, então não há `UNIQUE` de negócio no banco. Substitui o índice parcial `WHERE deleted_at IS NULL` definido em 01/10/2026. |
| 6 | Juízes por luta | Nova tabela `luta_juizes` (`luta_id`, `juiz_id`, `funcao` = árbitro ou juiz lateral). `juiz_id` sai de `lutas`. O placar só aceita juízes laterais vinculados à luta. |
| 7 | Judô | Palpite simplificado: vencedor + método próprio opcional (Ippon, Waza-ari, Decisão/Golden Score, Desclassificação). Sem round, sem palpite ao vivo, sem placar dos fãs e sem placar oficial. O comportamento por modalidade fica na categoria (`usa_rounds`). |
| 8 | Tempo real | **Não há.** Vídeo é link/embed do YouTube. Sem Reverb, Pusher ou polling: o servidor valida a janela de troca do palpite ao vivo, o chat funciona como comentários (aparecem ao recarregar) e a média do placar dos fãs atualiza na recarga. |
| 9 | Andamento da luta | Informado manualmente pelo administrador (iniciar luta → fim do round/intervalo → próximo round → encerrar com vencedor e método). Grava `round_atual` e `em_intervalo`. |
| 10 | Quem comenta | Membro ativo, papel Comentarista, Treinador ou Atleta com conta, e Administrador. Papéis especiais têm destaque visual. |
| 11 | Plano Free | Gera registro em `assinaturas` (plano Free, valor 0, `gateway` e `proxima_cobranca` nulos), criado automaticamente no cadastro do cliente. |
| 12 | Idioma | Interface 100% em português do Brasil (`APP_LOCALE=pt_BR`). Traduções do back-end em `lang/pt_BR`; textos do front-end escritos direto nos componentes Vue, sem biblioteca de i18n. |
| 13 | Configuração da pontuação | **Tabelas tipadas** (04/10/2026): `configuracoes_pontuacao` (pontos do palpite e prazo do placar dos fãs) e `pesos_troca_palpite` (peso por número de rounds e momento da troca). Em ambas, a linha sem categoria é o padrão geral e **uma categoria pode ter configuração própria**, que substitui o padrão por inteiro. Sem histórico de vigência: a alteração sobrescreve o valor, e os palpites já pontuados não mudam porque guardam peso e pontos próprios. |
| 14 | Permissões do painel | **Por função** (04/10/2026). Cadastrador: cadastros básicos, atletas, eventos, lutas, luta ao vivo, placar oficial e patrocínio. Moderador: verificações de selo. Super-admin: tudo, e só ele acessa usuários, papéis, administradores e a configuração de pontuação. Implementado com um gate por área (`admin.cadastros`, `admin.verificacoes`, `admin.usuarios`, `admin.configuracoes`), definido em `NivelAcesso::areas()`. |

**Consequências da decisão 5 a observar no desenvolvimento:**

- Toda regra de unicidade precisa de validação explícita no Form Request. Em 04/10/2026 só `users.email` tem essa validação.
- Sem a garantia no banco, duas requisições simultâneas podem gravar o mesmo valor.
- O filtro de soft delete só vale em consultas pelo Eloquent. Consultas com `DB::table(...)`, SQL bruto ou acesso direto ao banco precisam de `WHERE deleted_at IS NULL` escrito à mão.
- `belongsToMany` não filtra pivots excluídos: as relações usam `->wherePivotNull('deleted_at')`.

### 8.3 Convenções de implementação

- **Migrations:** macros `$table->publicUuid()` e `$table->auditColumns()` em toda tabela de negócio, além de `timestamps()` e `softDeletes()`.
- **Models:** traits `HasPublicUuid`, `SoftDeletes` e `Auditable` obrigatórios — um teste de arquitetura (`tests/Unit/ArchTest.php`) falha se algum model não os tiver. Tabela e campos preenchíveis declarados com os atributos `#[Table]` e `#[Fillable]`.
- **Auditoria:** as colunas `audit_*` ficam ocultas na serialização e registram também quem fez o soft delete. Seeders não podem usar `WithoutModelEvents`, pois isso impede a geração do UUID e da auditoria.
- **Pivots com soft delete:** `detach()` apaga fisicamente. Em `papel_user`, usar `User::atribuirPapel()` e `User::removerPapel()`.
- **Cartel do atleta:** `vitorias`, `derrotas` e `invicto` são recalculados pelo model a cada gravação, a partir do detalhamento por KO, submissão e decisão. Não são informados diretamente.

### 8.4 Estado da implementação em 04/10/2026

**Todas as 31 tabelas de negócio têm migration e model**, com `uuid`, `deleted_at` e colunas de auditoria. O painel administrativo e o site do cliente estão completos; falta o que depende de pagamento online.

| Área | Tabela | Model | Situação |
|---|---|---|---|
| Contas | `users` | `User` | Implementado (login, cadastro, perfil, 2FA) |
| Contas | `perfis_cliente` | `PerfilCliente` | Implementado |
| Contas | `perfis_administrador` | `PerfilAdministrador` | Implementado |
| Contas | `papeis` | `Papel` | Implementado |
| Contas | `papel_user` | `UserPapel` | Implementado |
| Cadastros | `organizacoes` | `Organizacao` | Tela no painel admin |
| Cadastros | `categorias` | `Categoria` | Tela no painel admin |
| Cadastros | `categorias_peso` | `CategoriaPeso` | Tela no painel admin |
| Cadastros | `estilos_luta` | `EstiloDeLuta` | Tela no painel admin |
| Cadastros | `treinadores` | `Treinador` | Tela no painel admin |
| Cadastros | `juizes` | `Juiz` | Tela no painel admin |
| Cadastros | `atletas` | `Atleta` | Tela no painel admin |
| Cadastros | `atleta_fotos` | `AtletaFoto` | Tela no painel admin |
| Cadastros | `atleta_estilos` | `AtletaEstilo` | Tela no painel admin |
| Eventos e lutas | `eventos` | `Evento` | Tela no painel admin |
| Eventos e lutas | `lutas` | `Luta` | Tela no painel admin |
| Eventos e lutas | `luta_juizes` | `LutaJuiz` | Tela no painel admin |
| Eventos e lutas | `placares` | `Placar` | Tela no painel admin |
| Participação | `palpites` | `Palpite` | Tela no site; pontuação implementada |
| Participação | `palpite_historicos` | `PalpiteHistorico` | Tela no site |
| Participação | `placar_fans` | `PlacarFan` | Tela no site |
| Participação | `mensagens` | `Mensagem` | Tela no site |
| Assinaturas | `assinaturas` | `Assinatura` | Sem tela (site do cliente) nem gateway |
| Assinaturas | `solicitacoes_verificacao` | `SolicitacaoVerificacao` | Solicitação no site e análise no painel admin |
| Assinaturas | `assinaturas_verificacao` | `AssinaturaVerificacao` | Sem tela (site do cliente) nem gateway |
| Patrocínio | `patrocinadores` | `Patrocinador` | Tela no painel admin |
| Patrocínio | `banners` | `Banner` | Tela no painel admin |
| Patrocínio | `postagens` | `Postagem` | Tela no painel admin |
| Ranking | `rankings` | `Ranking` | Tela no site; recalculado a cada luta encerrada |
| Configuração | `configuracoes_pontuacao` | `ConfiguracaoPontuacao` | Tela no painel admin; padrão geral semeado |
| Configuração | `pesos_troca_palpite` | `PesoTrocaPalpite` | Tela no painel admin; padrão geral semeado |

**Diferenças em relação ao dicionário de dados nas tabelas criadas em 04/10/2026:**

- `placar_fans`: nome definido por Sandro em 04/10/2026 (a análise chamava de `PlacarFa`).
- `lutas`: sem `juiz_id` (decisão 6); com `round_atual` e `em_intervalo` (decisão 9); `numero_rounds` aceita nulo para modalidades sem rounds (decisão 7); status ganhou `cancelada` (seção 6.2).
- `palpites`: com `metodo_escolhido`, `round_escolhido`, `round_da_troca`, `peso_aplicado` e `pontos_obtidos` (seção 6); sem `trocas_de_opiniao`, que passou a ser derivável de `palpite_historicos`.
- `assinaturas`: `periodicidade`, `gateway` e `proxima_cobranca` aceitam nulo, por causa do plano Free (decisão 11).
- `postagens.patrocinado`: recalculado pelo model a partir de `patrocinador_id`, como o cartel do atleta.
- `rankings.referencia_id`: sem chave estrangeira, porque aponta para evento ou organização conforme o escopo.
- Campos de status e tipo continuam `VARCHAR` no banco, com enums do PHP no model (`app/Enums`), como sugerido em 2.8.
- Nenhum índice único de negócio (decisão 5). Já validados na aplicação: nomes únicos nos cadastros, slug único da postagem, posição única no card, um placar por juiz por round, uma conta por treinador/atleta. Ainda não escrita: "um palpite por usuário por luta", que virá com a tela do palpite.

**Demais pontos:**

- A suíte tem 324 testes, todos passando no MySQL; Pint, Larastan, vue-tsc e lint do front-end sem erros. Os testes de tela dependem do build do front-end (`npm run build`).
- `ConfiguracaoPontuacaoSeeder` grava o padrão geral com os valores de partida da seção 6 (10/15/15/22 e as grades de 3 e 5 rounds). É idempotente e não sobrescreve o que o administrador já alterou.
- Como o ranking geral soma pontos de todas as modalidades, uma categoria com pontuação própria entra nele com régua diferente das demais.
- Painel administrativo em `/admin` (gate `acessar-admin`), com todas as áreas: cadastros básicos, atletas (fotos e estilos), eventos e card de lutas, andamento ao vivo e placar oficial, usuários e papéis, verificações, patrocínio (patrocinadores, banners, blog) e configuração de pontuação. Primeiro administrador: `php artisan app:promover-administrador {email} --nivel=super-admin`.
- O andamento da luta (`App\Services\AndamentoLuta`) só aceita as transições válidas: agendada → em andamento → intervalo → próximo round → encerrada, ou cancelada. Não há intervalo após o último round.
- Registros em uso não podem ser excluídos pelo painel (ex.: organização com eventos, atleta com lutas, juiz com placar).
- Arquivos enviados (logos, fotos, banners, capas) ficam no disco de mídia; o banco guarda só o caminho. O comprovante de verificação é baixado por rota protegida, nunca por URL pública.
- Cadastro público cria o perfil de cliente, exige a confirmação de maioridade e cria a assinatura Free (decisão 11).
- **Ao encerrar uma luta** (05/10/2026): o cartel dos dois atletas é atualizado na hora; a pontuação dos palpites e os rankings geral, do evento e da organização são recalculados em fila (`ProcessarResultadoDaLuta`), o que exige um worker de fila rodando (`php artisan queue:work`).
- **Pontuação do palpite** (`App\Services\Pontuacao`): segue a tabela da seção 6.1, multiplicada pelo `peso_aplicado` do palpite. Empate, sem resultado e desqualificação (em modalidades com rounds) dão zero para todos. O round só conta quando é igual ao `round_fim` informado no encerramento.
- **Site do cliente** (05/10/2026): home, eventos, página da luta (vídeo do YouTube, palpite, placar dos fãs e comentários), perfil do atleta, rankings geral/por evento/por organização, blog, painel "Meus palpites" e solicitação do selo de verificado. As páginas públicas não exigem conta; palpitar, pontuar rounds e comentar exigem conta com e-mail confirmado.
- **Janela do palpite** (`App\Services\Palpites\RegistradorDePalpite`): pré-luta para Free e Membro, com peso cheio; ao vivo só para Membro e só nos intervalos, com o peso do momento. Reenviar o mesmo palpite não conta como troca; qualquer troca num intervalo grava o peso daquele intervalo, mesmo voltando ao palpite original. Cada troca vai para `palpite_historicos`.
- **Placar dos fãs** (`App\Services\PlacarDosFans`): qualquer cliente pontua o round que acabou de terminar, uma vez, dentro do prazo configurado, no sistema 10-point must. Para medir o prazo foi criada a coluna `lutas.round_encerrado_em`, que não estava no dicionário.
- **Comentários:** Membro ativo, comentarista, atleta ou treinador com conta e administrador; papéis especiais aparecem com destaque. Limite de 500 caracteres e 10 comentários por minuto; a página mostra os 50 mais recentes.
- **Blog:** o conteúdo é tratado como Markdown; HTML digitado aparece como texto e nunca é executado. Só postagens publicadas e com data até hoje são públicas.
- **Banners:** exibidos nas posições Home, Evento, Luta, Chat e Blog quando ativos e dentro do período. Cada exibição conta uma impressão; o clique passa por uma rota que conta e redireciona ao link cadastrado.
- **Plano Membro sem gateway:** o super-admin define o plano do cliente na tela do usuário, sem cobrança.
- **Luta cancelada ou atleta substituído:** os palpites da luta são descartados (soft delete) e os usuários precisam palpitar de novo. Inverter os cantos (A ↔ B) não descarta.

### 8.5 Pendências

- Gateway de pagamento.
- Ranking geral: histórico completo ou janela móvel. **Implementado provisoriamente com o histórico completo.**
- Prazo de pontuação do placar dos fãs: o padrão semeado é de 5 minutos, valor provisório ainda não confirmado.
- Critérios de desempate do ranking. **Implementados provisoriamente como propostos em 6.4:** palpites perfeitos, vencedores corretos e quem palpitou primeiro.
- Cartel: desqualificação e os métodos do Judô (Ippon, Waza-ari, Golden Score) são somados em "decisão", porque o cartel só tem KO, submissão e decisão. Confirmar se é o desejado ou se o cartel precisa de mais detalhamentos.
- Desclassificação no Judô pontua o palpite (decisão 7), enquanto a desqualificação nas modalidades com rounds dá zero para todos (seção 6.1). Confirmar se a diferença é intencional.
- Corrigir o resultado de uma luta já encerrada: não há tela nem regra para desfazer o cartel e repontuar.
- Posição de banner "Categoria": pode ser cadastrada, mas o site não tem página de categoria para exibi-la.
- Impressões de banner contam toda exibição, inclusive recarregamentos e robôs.
- Membro sem palpite pode dar o primeiro palpite num intervalo, já com o peso reduzido. Confirmar se é o desejado.
- Assinatura do plano Membro e cobrança do selo pelo próprio cliente: dependem do gateway de pagamento.
- Moderação de comentários (excluir ou ocultar) pelo painel.
- Auditoria: só o último autor ou histórico completo.
- Reavaliar chat pago e selo de verificado pago (ver 7.2).
- Valores válidos de `atletas.tipo` e `atletas.stance`, hoje texto livre.
- Aprovar uma solicitação de verificação só registra a análise; o selo (`users.verificado`) depende da cobrança, ainda sem gateway.
- Não há tela para o administrador cadastrar papéis novos (hoje só o `PapelSeeder`).
