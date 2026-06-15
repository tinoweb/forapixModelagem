# Guia de Deploy na VPS (KVM)

Este guia acompanha a arquitetura "Parruda" baseada em Docker Swarm / Compose para o ForaPix, incluindo Cloudflare, Redis, MinIO (S3) e Traefik.

## 1. Preparação da VPS

Acesse a sua nova VPS via SSH:
```bash
ssh root@187.77.33.76
# Senha: JuniorPix1@2
```

### 1.1 Instalar o Docker e Docker Compose
Execute os comandos abaixo na VPS para instalar o Docker:
```bash
apt update && apt upgrade -y
apt install -y ca-certificates curl gnupg
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch="$(dpkg --print-architecture)" signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  "$(. /etc/os-release && echo "$VERSION_CODENAME")" stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

## 2. Envio dos Arquivos para a VPS (Via Git)

Como você vai usar o Git, o processo fica muito mais profissional e fácil de atualizar.

**Na sua máquina local (Windows), faça o commit das nossas novas alterações e envie para o GitHub/GitLab:**
```bash
git add .
git commit -m "feat: adiciona infraestrutura docker e configuracoes parruda"
git push origin main
```

**Na VPS (acesse via SSH), crie a pasta e clone o repositório:**
```bash
mkdir -p /var/www
cd /var/www
# Troque a URL abaixo pela URL do seu repositório:
git clone https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git forapix
cd forapix
```

## 3. Configuração do Ambiente (.env)

Dentro da pasta `/var/www/forapix/forapix-laravel`, certifique-se de que o `.env` existe (copie do `.env.example`).
O banco de dados precisa ter a mesma senha configurada no `docker-compose.yml`.

## 4. Subir a Infraestrutura

Na VPS, entre na pasta do projeto e rode:
```bash
cd /var/www/forapix
docker compose up -d --build
```

Isso vai iniciar:
- Traefik (Roteador Web)
- Frontend (Nginx com React/HTML)
- API (Laravel + Nginx)
- MySQL (Banco de Dados)
- Redis (Sessões e Cache super rápidos)
- MinIO (Storage para os avatares compatível com S3)

### 4.1 Instalar Dependências do Laravel
Logo após os containers subirem, instale as dependências:
```bash
docker exec -it laravel_app composer install --no-dev --optimize-autoloader
docker exec -it laravel_app php artisan key:generate
docker exec -it laravel_app php artisan migrate --force
```

## 5. Cloudflare

No painel da Cloudflare:
- Crie um registro **A** apontando `jrpix.com` para o IP da VPS `187.77.33.76` (Ative a nuvenzinha Laranja).
- Crie um registro **A** apontando `www.jrpix.com` para `187.77.33.76`.
- Crie um registro **A** apontando `s3.jrpix.com` para `187.77.33.76` (Para o MinIO).

> [!TIP]
> Com essa arquitetura, se o fluxo aumentar demais e a VPS com 4 vCPUs não aguentar, você só precisará transformar o Docker Compose em Docker Swarm e adicionar uma nova VPS ao cluster. Toda a estrutura de Redis e MinIO já está preparada para compartilhar dados entre várias máquinas!
