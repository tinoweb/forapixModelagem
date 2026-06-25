# 📘 Manual de Regras de Negócio e Funcionamento do Sistema — JrPix

Este documento detalha o funcionamento técnico, financeiro e operacional do sistema **JrPix (Aposta Casada)**. Ele serve como especificação clara para clientes interessados em adquirir a plataforma, explicando como o modelo de negócios funciona, as regras de controle financeiro, a mecânica das apostas e o fluxo de operações.

---

## 1. O Modelo de Negócios (P2P - Peer-to-Peer)

Diferente das casas de apostas tradicionais (onde o usuário aposta contra a banca), o **JrPix** opera sob o modelo **Peer-to-Peer (P2P)**, conhecido popularmente como **Aposta Casada**.

* **Aposta entre Usuários:** Os apostadores jogam uns contra os outros. Quem aposta no *Jogador 1* está disputando o dinheiro de quem aposta no *Jogador 2*.
* **Sem Risco para a Banca:** Como a banca apenas gerencia o dinheiro dos próprios apostadores e cobra uma taxa de comissão sobre a disputa, a plataforma **nunca corre o risco de ficar no prejuízo** por causa do resultado de uma partida.
* **Comissão (House Cut):** O lucro da plataforma vem de uma taxa administrativa fixa de **10%** cobrada apenas sobre o montante que foi efetivamente casado (disputado) ao fim de cada partida.

---

## 2. Mecânica de Casamento de Apostas (Bet Matching)

O motor financeiro do sistema realiza o cruzamento de apostas utilizando o algoritmo **FIFO (First In, First Out)**:

### O Fluxo de Casamento
1. Um usuário faz uma aposta de R$ 100 no **Jogador 1**.
2. O sistema busca no banco de dados as apostas pendentes no **Jogador 2**, começando pela mais antiga.
3. Se houver uma aposta de R$ 60 no **Jogador 2**, o sistema casa R$ 60.
   * A aposta no **Jogador 2** fica **100% casada** (R$ 60 casados / R$ 0 pendentes).
   * A aposta no **Jogador 1** fica **parcialmente casada** (R$ 60 casados / R$ 40 pendentes).
4. Os R$ 40 pendentes do *Jogador 1* continuam na fila esperando que outro usuário aposte no *Jogador 2*.

### Matched vs Unmatched (Casado vs Não Casado)
* **Matched Amount (Valor Casado):** É o valor que de fato encontrou um oponente correspondente. Este valor está em risco e participará da divisão final.
* **Unmatched Amount (Valor Não Casado):** É a sobra que não encontrou oponentes. **Este valor nunca fica em risco** e é devolvido integralmente ao saldo do usuário assim que a partida é encerrada ou se ele cancelar o palpite (caso as apostas não estejam trancadas).

---

## 3. Distribuição do Pool de Ganhos (Matemática Payout)

Quando uma partida é finalizada e o administrador define o vencedor, a distribuição do dinheiro ocorre sob a seguinte fórmula matemática:

1. **Soma-se o pool casado total:**
   $$\text{Pool Casado Total} = \sum \text{Matched Amount (Jogador 1)} + \sum \text{Matched Amount (Jogador 2)}$$
2. **Retém-se a comissão da plataforma (10%):**
   $$\text{Taxa da Casa} = \text{Pool Casado Total} \times 0.10$$
3. **Calcula-se o montante de prêmio líquido (90%):**
   $$\text{Pool de Ganhos} = \text{Pool Casado Total} - \text{Taxa da Casa}$$
4. **Divisão Proporcional aos Vencedores:**
   Cada apostador do lado vencedor recebe uma fração do *Pool de Ganhos* baseada no quanto ele casou em relação ao total casado do lado vencedor:
   $$\text{Prêmio Individual} = \left( \frac{\text{Valor Casado do Apostador}}{\text{Total Casado do Lado Vencedor}} \right) \times \text{Pool de Ganhos}$$
5. **Devolução do Não Casado:**
   Tanto vencedores quanto perdedores recebem de volta 100% da sua respectiva sobra não casada ($\text{Amount} - \text{Matched Amount}$).

### Exemplo Prático:
* **Apostas Casadas no Jogador 1:** R$ 1.000,00
* **Apostas Casadas no Jogador 2:** R$ 1.000,00
* **Pool Casado Total:** R$ 2.000,00
* **Taxa da Casa (10%):** R$ 200,00 (Lucro da plataforma)
* **Pool de Ganhos (90%):** R$ 1.800,00
* Se o **Jogador 1** vencer, o pool de R$ 1.800,00 será dividido proporcionalmente entre quem apostou no Jogador 1. Se um único usuário casou R$ 100,00 (10% do total do Jogador 1), ele receberá R$ 180,00 de retorno (1.8x o valor apostado).

> [!IMPORTANT]
> Se uma partida não tiver nenhum casamento (por exemplo, todos apostaram apenas em um jogador e ninguém apostou no outro), **100% do valor é devolvido a todos os usuários** e nenhuma taxa é cobrada.

---

## 4. Ciclo de Vida de uma Partida

Uma partida no JrPix passa pelos seguintes status:

* **Agendada (Scheduled):** A partida é exibida no site e o cronômetro mostra o tempo restante para o início. Usuários podem fazer apostas livremente. As odds são flutuantes de acordo com o volume de dinheiro em cada lado.
* **Ao Vivo (Live):** A partida começou na mesa real. O administrador pode abrir e fechar as apostas em tempo real com um clique de acordo com o andamento do jogo.
* **Finalizada (Finished):** O administrador insere o placar final e seleciona o jogador vencedor. O sistema encerra as apostas pendentes, distribui os prêmios e desconta a taxa da casa.
* **Cancelada (Cancelled):** Usado em caso de imprevistos. O sistema devolve **100% do dinheiro** das apostas (casadas ou pendentes) para o saldo de todos os participantes. Nenhuma comissão é cobrada.

---

## 5. Ferramentas Operacionais e Segurança

O painel de controle administrativo oferece recursos essenciais para proteger a operação:

### 🔒 Trancar Apostas (Lock)
* **O que faz:** Bloqueia novos palpites e impede que os usuários cancelem as apostas que já fizeram.
* **Quando usar:** Quando a partida está prestes a iniciar ou durante lances decisivos onde flutuações rápidas de cotação poderiam ser exploradas.

### 💼 Saldo Disponível vs Saldo Sacável (Regra Anti-Lavagem de Dinheiro / Fraude)
Para evitar abusos com taxas de PIX, o sistema possui dois tipos de saldos:
1. **Saldo Total (Balance):** Todo o saldo disponível para o usuário apostar.
2. **Saldo Sacável (Withdrawable Balance):** Apenas os valores provenientes de **ganhos de apostas** ou **devoluções de frações não casadas**.
   * *Regra de Ouro:* O usuário **não pode** depositar R$ 100 via PIX e solicitar o saque de R$ 100 imediatamente sem ter jogado. Ele precisa colocar o valor em disputa para convertê-lo em saldo sacável.

---

## 6. Integração Financeira (Gateway PIX VeoPag)

Toda a parte financeira de depósitos e saques funciona integrada com a API da **VeoPag**:

* **Depósitos (Automatizados):** 
  * O usuário solicita um depósito (Mínimo padrão: R$ 10).
  * O sistema gera dinamicamente um QR Code Pix e um código Copia e Cola.
  * O processamento é instantâneo via webhook: assim que o usuário paga, o saldo cai na conta automaticamente.
* **Saques (Segurança em 2 Etapas):**
  * O usuário solicita o saque fornecendo sua chave PIX (CPF, Celular, E-mail ou Chave Aleatória) e seu CPF de titularidade.
  * O sistema valida se ele possui `withdrawable_balance` suficiente.
  * O administrador aprova a transação no painel financeiro para que a transferência seja realizada de forma automática via API.
* **Modo de Contingência (Local/Fallback):**
  * Caso as credenciais da VeoPag não estejam preenchidas, o sistema entra em modo manual, gerando um código Pix padrão configurado nas variáveis de ambiente e criando os depósitos/saques em status "Pendente" para aprovação manual do administrador.

---

## 7. Painel Administrativo de Configurações

O dono da plataforma tem controle total sobre o negócio através de uma interface intuitiva:

* **Gestão de Jogadores e Jogos:** Cadastro de competidores, fotos e modalidades esportivas (Sinuca, etc.).
* **Limites Financeiros:** Configuração do valor mínimo de depósito e valor mínimo de saque por transação.
* **Atendimento ao Cliente:** Opção para ativar/desativar o botão flutuante de suporte via WhatsApp e configurar o número de destino e e-mail de suporte.
* **Reconciliação de Depósitos:** Ferramenta para verificar transações pendentes diretamente na API do banco parceiro e forçar a liberação de saldo em lote, caso ocorra alguma lentidão na rede PIX.
