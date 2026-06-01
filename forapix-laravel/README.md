# ForaPix — Plataforma de Apostas Casadas PIX

> Plataforma P2P de apostas esportivas com gateway de pagamento via PIX integrado à VeoPag.

---

## Visão Geral

O **ForaPix** (também publicado como **ApostaCasada**) é uma plataforma de apostas no modelo **peer-to-peer (P2P)**, onde os apostadores disputam entre si — não contra a casa. A plataforma intermedeia o casamento das apostas, garante o pagamento via PIX e retém uma taxa de serviço de **10%** sobre o pool casado.

```
Apostador A ──┐
              ├──► POOL CASADO ──► Vencedores recebem 90% do pool
Apostador B ──┘                    Plataforma retém 10%
```

---

## Arquitetura

| Camada | Tecnologia | Localização |
|---|---|---|
| **Frontend** | HTML + Vanilla JS + TailwindCSS | `forapix-web/` |
| **Backend** | Laravel 10 (API REST) | `forapix-laravel/` |
| **Banco de Dados** | MySQL | Via `.env` |
| **Pagamentos** | VeoPag (PIX depósito + saque) | `app/Services/VeoPagService.php` |
| **Hospedagem** | Hostinger (compartilhado) | `apostacasada.net` |

---

## Funcionalidades Principais

### Para o Usuário
- **Cadastro e autenticação** via email/senha (Laravel Sanctum)
- **Carteira digital** — depósito e saque via PIX (gateway VeoPag)
- **Apostas casadas** — apostar no jogador 1 ou jogador 2 de uma partida
- **Casamento parcial** — apostas são casadas por FIFO proporcionalmente
- **Cancelamento de aposta** — permitido apenas enquanto não casada e dentro do prazo
- **Histórico** de transações e apostas com status em tempo real
- **Apostas ao vivo** — aceitas durante a partida apenas quando placar está empatado

### Para o Administrador
- **Gerenciamento de partidas** — criar, iniciar, encerrar, cancelar
- **Atualização de placar** — abre/fecha apostas ao vivo automaticamente
- **Declaração de vencedor** — dispara cálculo e pagamento automático
- **Gerenciamento financeiro** — visualização de saques pendentes e histórico
- **Gerenciamento de usuários** — suspender, editar, ajustar saldos

---

## Fluxo de Pagamento (VeoPag)

### Depósito
1. Usuário solicita depósito → sistema gera QR Code PIX via VeoPag
2. Usuário paga o PIX no banco
3. VeoPag envia webhook `POST /api/webhooks/deposit` confirmando pagamento
4. Saldo é creditado automaticamente na carteira

### Saque
1. Usuário solicita saque informando valor, chave PIX e CPF do titular
2. Sistema envia requisição à VeoPag (`POST /api/withdrawals/withdraw`)
3. VeoPag processa o PIX e envia webhook `POST /api/webhooks/withdraw` com status final
4. Saque registrado como `pending` até confirmação; `completed` após webhook

> Apenas o saldo proveniente de **ganhos** (`withdrawable_balance`) pode ser sacado. Valor mínimo: **R$ 10,00**.

---

## Regras de Negócio (Resumo)

| Regra | Valor |
|---|---|
| Taxa da plataforma | 10% do pool casado |
| Valor mínimo de aposta | R$ 10,00 |
| Valor mínimo de saque | R$ 10,00 |
| Saldo sacável | Apenas ganhos (não depósitos) |
| Casamento de apostas | FIFO — quem apostou primeiro é casado primeiro |
| Apostas ao vivo | Só permitidas com placar empatado |
| Cancelamento | Somente se `matched_amount = 0` e partida não iniciada |

> Documentação completa das regras em [`BETTING_RULES.md`](./BETTING_RULES.md)

---

## Estrutura de Pastas (Backend)

```
forapix-laravel/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/              # Endpoints públicos (auth, apostas, carteira, webhooks)
│   │   └── Admin/            # Painel administrativo
│   ├── Models/               # User, Bet, GameMatch, Transaction, Game...
│   └── Services/
│       ├── BetMatchingService.php   # Casamento, resolução e pagamento de apostas
│       └── VeoPagService.php        # Integração PIX (depósito e saque)
├── database/migrations/      # Migrações do banco de dados
├── routes/
│   ├── api.php               # Rotas da API REST
│   └── web.php
└── BETTING_RULES.md          # Documentação técnica das regras de apostas
```

---

## Configuração e Deploy

### Requisitos
- PHP 8.1+
- MySQL 8+
- Composer
- Conta ativa na [VeoPag](https://veopag.com) com IP do servidor na whitelist

### Variáveis de Ambiente (`.env`)

```env
APP_URL=https://apostacasada.net/api

DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

VEOPAG_CLIENT_ID=
VEOPAG_CLIENT_SECRET=
```

> Guia completo de deploy na Hostinger: [`DEPLOY_HOSTINGER.md`](../DEPLOY_HOSTINGER.md)

---

## Acesso Administrativo

```
URL:   https://apostacasada.net/admin
Email: admin@apostacasada.net
```

> ⚠️ Altere a senha padrão após o primeiro acesso em produção.

---

## Integrações Externas

| Serviço | Função | Documentação |
|---|---|---|
| **VeoPag** | Gateway PIX (depósito e saque) | [veopag.readme.io](https://veopag.readme.io) |
| **Resend** | Envio de emails transacionais | — |

---

## Status do Projeto

- ✅ Autenticação e cadastro de usuários
- ✅ Sistema de apostas casadas (FIFO, parcial, ao vivo)
- ✅ Depósito via PIX (VeoPag)
- ✅ Saque via PIX (VeoPag) com fallback manual
- ✅ Webhooks de confirmação (depósito e saque)
- ✅ Painel administrativo completo
- ✅ Histórico de transações e apostas