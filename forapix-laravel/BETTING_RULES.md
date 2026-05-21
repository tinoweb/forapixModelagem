# ForaPix — Documentação do Sistema de Apostas Casadas

> Última atualização: Maio/2026  
> Arquivo de referência técnica e de regras de negócio.

---

## 1. Visão Geral

O ForaPix opera o modelo **"Aposta Casada"** (peer-to-peer betting), onde os apostadores disputam entre si — não contra a casa. A plataforma atua como intermediária, garantindo o casamento das apostas e cobrando uma taxa de serviço sobre os ganhos.

```
Apostador A  ──┐
               ├──► POOL CASADO ──► Vencedores recebem 90%
Apostador B  ──┘                    Casa retém 10%
```

---

## 2. Ciclo de Vida de uma Partida

```
SCHEDULED ──► LIVE ──► FINISHED
    │                     ▲
    └────► CANCELLED ──────┘
           (a qualquer momento pelo admin)
```

| Status | Descrição |
|---|---|
| `scheduled` | Partida agendada, apostas pré-jogo abertas até `betting_deadline` |
| `live` | Partida em andamento; apostas ao vivo controladas por `live_betting_open` |
| `finished` | Partida encerrada; vencedores pagos, não casados devolvidos |
| `cancelled` | Partida cancelada; 100% devolvido a todos |

---

## 3. Regras de Abertura de Apostas (`isBettingOpen`)

A função `GameMatch::isBettingOpen()` é a **única fonte de verdade** sobre se apostas podem ser feitas. Ela retorna `true` apenas quando:

```
PRÉ-JOGO:  status = "scheduled"  AND  agora < betting_deadline
AO VIVO:   status = "live"        AND  live_betting_open = true
```

Em qualquer outro caso (`finished`, `cancelled`, prazo expirado, `live` com apostas fechadas), o retorno é `false` e nenhuma aposta pode ser registrada.

### 3.1 Apostas ao Vivo — Toggle Automático pelo Placar

Quando o admin atualiza o placar via painel, o sistema automaticamente controla `live_betting_open`:

| Placar | Ação automática |
|---|---|
| **Empate** (ex: 2×2) | Abre apostas ao vivo → `live_betting_open = true` |
| **Um jogador na frente** (ex: 3×2) | Fecha apostas ao vivo → `live_betting_open = false` |

**Motivo:** só faz sentido aceitar apostas ao vivo quando o resultado ainda é incerto (empate).

---

## 4. Ciclo de Vida de uma Aposta

```
COLOCADA ──► PENDING ──┬──► WON       (partida encerrada, apostador venceu)
                       ├──► LOST      (partida encerrada, apostador perdeu)
                       └──► CANCELLED (cancelamento admin ou pelo usuário*)
```

> \* Usuário só pode cancelar se `canBeCancelled()` retornar `true`.  
> Ver seção 6 para as regras completas de cancelamento.

### Campos chave de uma aposta

| Campo | Descrição |
|---|---|
| `amount` | Valor total apostado |
| `matched_amount` | Parcela já casada com o lado oposto (0 a `amount`) |
| `status` | Estado atual: `pending`, `won`, `lost`, `cancelled` |
| `bet_type` | Lado da aposta: `first_player` ou `second_player` |
| `placed_at` | Timestamp de quando a aposta foi feita (usado para FIFO) |

---

## 5. Regras de Casamento (Matching)

### 5.1 Lógica FIFO (First In, First Out)

Quando uma nova aposta é registrada, o sistema **imediatamente** tenta casá-la com apostas pendentes do lado oposto, do mais antigo para o mais recente.

```
Ordem de prioridade: placed_at ASC  (quem apostou primeiro é casado primeiro)
```

### 5.2 Casamento Parcial

Apostas são casadas **proporcionalmente** — não é necessário que os valores sejam iguais.

**Exemplo completo:**

```
Estado inicial:
  João  → R$ 100 no Jogador 1  | matched: R$ 0   | pendente: R$ 100

Pedro aposta R$ 50 no Jogador 2:
  João  → R$ 100 no Jogador 1  | matched: R$ 50  | pendente: R$ 50  ← prioridade FIFO
  Pedro → R$ 50  no Jogador 2  | matched: R$ 50  | pendente: R$ 0   ← 100% casado

Maria aposta R$ 30 no Jogador 2:
  João  → R$ 100 no Jogador 1  | matched: R$ 80  | pendente: R$ 20  ← continua sendo casado
  Maria → R$ 30  no Jogador 2  | matched: R$ 20  | pendente: R$ 10  ← casa com o que sobrou do João
```

> A parte **não casada** sempre é devolvida ao apostador no encerramento da partida,
> independente de ter ganhado ou perdido.

---

## 6. Regras de Cancelamento de Aposta (pelo Usuário)

O método `Bet::canBeCancelled()` valida **todas** as condições abaixo simultaneamente:

| # | Condição | Motivo |
|---|---|---|
| 1 | `status = pending` | Aposta já resolvida não pode ser cancelada |
| 2 | `matched_amount = 0` | **Se qualquer valor foi casado, o cancelamento é bloqueado** |
| 3 | `match.status = scheduled` | Uma vez que o jogo começa (`live`), o cancelamento é impossível |
| 4 | `agora < betting_deadline` | O prazo de apostas não pode ter expirado |

**Cancelamento só é possível quando TODAS as 4 condições são verdadeiras.**

```
Exemplos práticos:

✅ PODE cancelar:
   - Apostei R$100 pré-jogo, ainda não foi casado, jogo não começou, prazo não venceu

❌ NÃO PODE cancelar:
   - Apostei R$100, R$1 já foi casado com alguém do outro lado
   - Apostei R$100, o jogo começou (status = live), mesmo que matched = 0
   - Apostei R$100, o prazo de apostas expirou
   - Apostei R$100 e perdi (status = lost)
```

**Cancelamento administrativo** não passa por `canBeCancelled()`. O admin pode cancelar
apostas individuais ou a partida inteira a qualquer momento, com reembolso total.

---

## 7. Encerramento de Partida e Cálculo de Pagamento

### 7.1 Pool de Pagamento

Quando o admin declara o vencedor, o `BetMatchingService::resolveMatch()` é acionado:

```
POOL TOTAL CASADO = soma de matched_amount de TODOS os apostadores
TAXA DA CASA      = 10% do pool total
POOL VENCEDORES   = pool total − taxa da casa (90%)
```

### 7.2 Distribuição para Vencedores

Cada vencedor recebe uma **proporção do pool** baseada em quanto ele contribuiu para o lado vencedor:

```
ganho_individual = (matched_amount_do_apostador / total_matched_lado_vencedor) × pool_vencedores
```

**Exemplo numérico:**

```
João  aposta R$ 100 no Jogador 1 → matched: R$ 80
Ana   aposta R$ 60  no Jogador 1 → matched: R$ 60
Pedro aposta R$ 140 no Jogador 2 → matched: R$ 140

Pool casado total = R$ 80 + R$ 60 + R$ 140 = R$ 280
Taxa da casa (10%) = R$ 28
Pool para vencedores = R$ 252

Jogador 1 vence:
  Total matched lado vencedor = R$ 80 + R$ 60 = R$ 140

  João recebe:  (80/140) × 252 = R$ 144,00  + devolução dos R$ 20 não casados
  Ana recebe:   (60/140) × 252 = R$ 108,00  + devolução dos R$ 0 não casados

Pedro perde: R$ 140 casado vai para o pool. Recebe de volta R$ 0.
```

### 7.3 Devolução do Não Casado

**Independente de ganhar ou perder**, o apostador sempre recebe de volta
a parcela do `amount` que nunca foi casada (`amount - matched_amount`).

```
valor_devolvido = amount - matched_amount
```

---

## 8. Cancelamento de Partida (Admin)

Quando o admin cancela uma partida inteira, o `BetMatchingService::cancelMatch()` é acionado:

- 100% do `amount` original é devolvido a **todos** os apostadores
- Apostas parcialmente casadas também recebem reembolso total
- Status de todas as apostas muda para `cancelled`
- Transações de reembolso são registradas

---

## 9. Restrições de Aposta

| Regra | Valor |
|---|---|
| Valor mínimo por aposta | R$ 10,00 |
| Limites por jogo | Configuráveis por `Game::min_bet` / `Game::max_bet` |
| Tipos de aposta aceitos | `first_player`, `second_player` |
| Usuário suspenso | Bloqueado via `User::canBet()` |
| Saldo insuficiente | Bloqueado com mensagem de valor em falta |

---

## 10. Fluxo de Dados: Do Clique ao Casamento

```
[Usuário clica "Apostar"]
      │
      ▼
BetController::store()
  ├─ Valida: betting open? saldo? limites?
  ├─ User::placeBet() → debita saldo, cria registro Bet{status=pending, matched=0}
  └─ BetMatchingService::matchBet()
       ├─ Busca apostas opostas pendentes com saldo disponível (ORDER BY placed_at ASC)
       ├─ Itera e casa parcialmente (FIFO)
       ├─ Incrementa matched_amount nas apostas opostas
       └─ Salva matched_amount na nova aposta

[Aposta registrada — pode estar: 100% casada | parcialmente | 0%]
```

---

## 11. Arquivos de Referência

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/BetMatchingService.php` | Toda lógica de casamento, resolução e cancelamento de partida |
| `app/Models/Bet.php` | Estado e regras de cancelamento de uma aposta individual |
| `app/Models/GameMatch.php` | Estado da partida, abertura de apostas, toggle ao vivo |
| `app/Http/Controllers/Api/BetController.php` | API: colocar aposta, listar, cancelar |
| `app/Http/Controllers/Admin/GameManagementController.php` | Admin: atualizar placar, toggle ao vivo, cancelar partida |
| `app/Http/Controllers/Admin/BetManagementController.php` | Admin: cancelar aposta individual com reembolso |

---

## 12. Glossário

| Termo | Significado |
|---|---|
| **Aposta casada** | Aposta que encontrou contraparte no lado oposto |
| **Aposta pendente** | Ainda aguardando casamento total ou parcial |
| **Pool** | Soma de todos os `matched_amount` da partida |
| **FIFO** | First In, First Out — quem apostou primeiro é casado primeiro |
| **Live betting** | Apostas aceitas durante a partida (apenas no empate) |
| **Matched amount** | Valor efetivamente em disputa (casado com o outro lado) |
| **Unmatched amount** | Parcela que não encontrou contraparte — devolvida sempre |
