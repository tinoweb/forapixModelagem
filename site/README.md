# ForaPix - Sistema de Apostas

Sistema de apostas online inspirado no ForRaPix, desenvolvido com JavaScript vanilla, TailwindCSS e arquitetura mobile-first.

## 🚀 Funcionalidades

### Implementadas
- ✅ **Dashboard** - Tela inicial com jogos e serviços
- ✅ **Lista de Partidas** - Exibição de confrontos head-to-head (UFC/MMA)
- ✅ **Sistema de Apostas** - Seleção de lutador e valor da aposta
- ✅ **Validação de Saldo** - Verificação de saldo antes de apostar
- ✅ **Depósito via PIX** - Simulação de depósito com QR Code
- ✅ **Carteira** - Histórico de transações e saque
- ✅ **Menu de Configurações** - Perfil, notificações, segurança

### Fluxo de Apostas
1. Usuário acessa a lista de partidas
2. Seleciona uma partida para apostar
3. Escolhe o lutador vencedor
4. Informa o valor da aposta
5. Sistema valida se há saldo suficiente
6. Se não houver saldo, redireciona para depósito
7. Se houver saldo, confirma a aposta

## 📁 Estrutura do Projeto

```
FORAPIX/
├── index.html              # Página principal
├── README.md               # Documentação
├── assets/
│   ├── css/
│   │   └── main.css        # Estilos principais
│   ├── js/
│   │   ├── config.js       # Configurações globais
│   │   ├── api.js          # Serviço de API
│   │   ├── storage.js      # Gerenciamento de localStorage
│   │   ├── utils.js        # Funções utilitárias
│   │   ├── components.js   # Componentes reutilizáveis
│   │   ├── app.js          # Controlador principal
│   │   └── pages/
│   │       ├── home.js     # Página inicial
│   │       ├── games.js    # Página de jogos
│   │       ├── matches.js  # Lista de partidas
│   │       ├── bet.js      # Página de aposta
│   │       ├── deposit.js  # Página de depósito
│   │       ├── wallet.js   # Carteira
│   │       └── menu.js     # Menu/Configurações
│   └── images/             # Imagens do sistema
```

## 🛠️ Tecnologias

- **HTML5** - Estrutura
- **CSS3 + TailwindCSS** - Estilização
- **JavaScript (ES6+)** - Lógica
- **LocalStorage** - Persistência de dados
- **API Externa** - `api.sispts.com` para dados de partidas

## 🎨 Design

- **Mobile-First** - Otimizado para dispositivos móveis
- **Dark Theme** - Tema escuro moderno
- **Responsivo** - Adapta-se a diferentes tamanhos de tela

## 🔧 Configuração

### API
A configuração da API está em `assets/js/config.js`:

```javascript
API: {
    BASE_URL: 'https://api.sispts.com/api/v1',
    TERMINAL_ID: '121088',
    TERMINAL_SERIAL: 'f65e0eae-a381-4463-9b51-c0e1be6b4681'
}
```

### Apostas
```javascript
BET: {
    MIN_VALUE: 1.00,
    MAX_VALUE: 10000.00,
    QUICK_VALUES: [5, 10, 20, 50, 100, 200]
}
```

## 🚀 Como Executar

1. Clone ou baixe o projeto
2. Abra o arquivo `index.html` em um navegador
3. Ou use um servidor local:

```bash
# Com Python
python -m http.server 8080

# Com Node.js (http-server)
npx http-server -p 8080

# Com PHP
php -S localhost:8080
```

4. Acesse `http://localhost:8080`

## 📱 Telas

### Dashboard
- Banner promocional
- Jogos (Cassino, Bingo)
- Serviços (Depósito, Suporte, Resultados, etc.)

### Lista de Partidas
- Filtro por esporte
- Cards de confrontos
- Informações de odds e deadline

### Tela de Aposta
- Seleção de lutador
- Input de valor
- Valores rápidos
- Resumo da aposta
- Validação de saldo

### Carteira
- Saldo atual
- Ações (Depositar/Sacar)
- Histórico de transações

## 🔐 Armazenamento Local

O sistema utiliza localStorage para persistir:
- Dados do usuário
- Saldo
- Histórico de apostas
- Histórico de transações
- Preferências de tema

## 📝 Licença

Este projeto é apenas para fins educacionais e de demonstração.

---

Desenvolvido com ❤️ usando JavaScript, TailwindCSS e boas práticas de desenvolvimento.
