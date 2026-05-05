# Guia de Deploy - Hostinger

Este documento explica como hospedar as aplicações ForaPix no Hostinger.

---

## Arquitetura

| Aplicação | Tipo | Caminho Sugerido |
|-----------|------|------------------|
| **Frontend** (forapix-web) | Site Estático (HTML/JS/CSS) | `public_html/` (raiz do domínio) |
| **Backend** (forapix-laravel) | Laravel API | `public_html/api/` (pasta dentro do domínio) |

---

## 1. Deploy do Frontend (Estático)

O frontend é um site estático puro — não precisa de Node.js, build ou servidor.

### Upload via FTP / Gerenciador de Arquivos

1. **Compactar os arquivos do frontend:**
   - No Windows, clique com botão direito em `forapix-web/` → Enviar para → Pasta compactada
   - Deve criar `forapix-web.zip`

2. **Acessar o cPanel do Hostinger:**
   - Login no hPanel
   - Vá em **Hospedagem** → **Gerenciar** → **Gerenciador de Arquivos**

3. **Fazer upload:**
   - Navegue até `public_html/` (raiz do domínio)
   - Clique em **Upload** → selecione `forapix-web.zip`
   - Clique com botão direito no arquivo → **Extrair**
   - **IMPORTANTE:** Não sobrescrever a pasta `api/` se já existir

4. **Remover o ZIP** após extração

---

## 2. Deploy do Laravel (Backend)

**IMPORTANTE:** No Hostinger compartilhado, o acesso via CLI/SSH pode não estar disponível. Prepare tudo localmente e faça upload via FTP.

### Preparação Local (Antes do Upload)

1. **No seu computador (Windows):**
   ```bash
   cd forapix-laravel
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Criar e configurar .env localmente:**
   - Copie `.env.example` → `.env`
   - Configure as credenciais do banco de dados do Hostinger (veja seção 3)
   - **IMPORTANTE:** Ajuste `APP_URL` para o domínio principal (ex: `https://seudominio.com`)

3. **Rodar migrations localmente (opcional, mas recomendado):**
   - Se tiver banco de dados local, rode para testar:
   ```bash
   php artisan migrate:fresh --seed
   ```
   - Se não tiver, ignore — as migrations serão rodadas no servidor via URL

4. **Compactar o projeto para upload:**
   - Excluir do ZIP: `.git/`, `node_modules/`, `tests/`, `.env.example`
   - **INCLUIR no ZIP:** `vendor/`, `.env` (já configurado), `storage/` (com estrutura)
   - Criar `forapix-laravel.zip`

### Upload via FTP / Gerenciador de Arquivos

1. **No cPanel → Gerenciador de Arquivos:**
   - Navegue até `public_html/`
   - Crie a pasta `api/` (se não existir)
   - Entre em `api/`
   - Upload do `forapix-laravel.zip`
   - Extrair
   - Remover o ZIP

2. **Rodar migrations via URL (se não rodou localmente):**
   - Acesse: `https://seudominio.com/api/artisan-migrate`
   - Crie um arquivo temporário `artisan-migrate.php` na raiz do projeto:
   ```php
   <?php
   require __DIR__.'/vendor/autoload.php';
   $app = require_once __DIR__.'/bootstrap/app.php';
   $app->make('Illuminate\Contracts\Console\Kernel')->call('migrate', ['--force' => true]);
   $app->make('Illuminate\Contracts\Console\Kernel')->call('db:seed', ['--class' => 'AdminUserSeeder']);
   $app->make('Illuminate\Contracts\Console\Kernel')->call('db:seed', ['--class' => 'DemoDataSeeder']);
   echo "Migrations executadas com sucesso!";
   ```
   - Acesse a URL, veja a mensagem de sucesso, depois **delete este arquivo**

3. **Configurar permissões via painel:**
   - No Gerenciador de Arquivos, clique com botão direito em `storage/` → **Permissões** → `755`
   - Faça o mesmo para `bootstrap/cache/`
   - Se o painel não permitir, crie um arquivo `fix-permissions.php`:
   ```php
   <?php
   chmod(__DIR__.'/storage', 0755);
   chmod(__DIR__.'/bootstrap/cache', 0755);
   echo "Permissões ajustadas!";
   ```
   - Acesse a URL, depois delete o arquivo

### Apontar a pasta api para a pasta public

Crie `.htaccess` em `public_html/api/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L]
</IfModule>
```

Ou, se preferir, mova todo o conteúdo de `public/` para a raiz da pasta `api/` e ajuste o `index.php`:
- Mova `public/index.php` → `index.php` (na raiz de `api/`)
- Edite `index.php`:
  ```php
  require __DIR__.'/vendor/autoload.php';
  $app = require_once __DIR__.'/bootstrap/app.php';
  ```

---

## 3. Configuração .env para Produção

Edite `.env` no servidor:

```env
APP_NAME=ForaPix
APP_ENV=production
APP_KEY=gerado_pelo_artisan_key_generate
APP_DEBUG=false
APP_URL=https://seudominio.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=seu_usuario_bd
DB_USERNAME=seu_usuario_cpanel
DB_PASSWORD=sua_senha_bd

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Admin (opcional, se usar env)
FORAPIX_ADMIN_EMAIL=admin@forapix.com
FORAPIX_ADMIN_PASSWORD=sua_senha_segura
```

---

## 4. Configuração de CORS

No Laravel, edite `config/cors.php` para permitir o domínio:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_origins' => [
    'https://seudominio.com',
],

'allowed_origins_patterns' => [],

'allowed_methods' => ['*'],

'allowed_headers' => ['*'],
```

Ou, se quiser permitir qualquer origem (não recomendado para produção):

```php
'allowed_origins' => ['*'],
```

---

## 5. Atualizar URL da API no Frontend

No arquivo `forapix-web/assets/js/config.js`, altere:

```javascript
const Config = {
    API: {
        BASE_URL: '/api/api',  // ← caminho relativo à pasta api/
        TIMEOUT: 10000,
        HEADERS: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    },
    // ...
};
```

Ou, se preferir URL absoluta:
```javascript
BASE_URL: 'https://seudominio.com/api/api',
```

Refaça o upload do `assets/js/config.js` no servidor.

---

## 6. Banco de Dados

1. No hPanel → **Banco de Dados** → **Bancos de Dados MySQL**
2. Criar novo banco (ex: `usuario_forapix`)
3. Criar usuário e senha
4. Anotar credenciais e usar no `.env`

---

## 7. SSL (HTTPS)

No Hostinger:
- O certificado SSL gratuito Let's Encrypt é ativado automaticamente
- Forçar HTTPS via `.htaccess` na raiz:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 8. Testar

1. **Frontend:** `https://seudominio.com`
2. **API:** `https://seudominio.com/api/api/matches`
3. **Admin:** `https://seudominio.com/api/admin/login`
   - Email: `admin@forapix.com`
   - Senha: a senha definida no `.env` ou `admin123` (padrão do seeder)

---

## 9. Manutenção

### Atualizar código

**Via FTP (sem CLI):**
1. Faça as alterações localmente
2. Rode `composer install --no-dev --optimize-autoloader` localmente
3. Rode `php artisan config:cache`, `route:cache`, `view:cache` localmente
4. Reaplique o `.htaccess` ou estrutura de pasta se necessário
5. Upload dos arquivos modificados para `public_html/api/`
6. Se houver novas migrations, recrie o arquivo `artisan-migrate.php` e acesse a URL

**Se tiver acesso SSH/CLI:**
```bash
cd public_html/api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Logs

Verificar logs em `public_html/api/storage/logs/laravel.log` via Gerenciador de Arquivos.

---

## 10. Resumo de Estrutura de Pastas

```
public_html/
├── index.html           (Frontend estático - forapix-web)
├── assets/              (Frontend estático)
│   ├── css/
│   ├── js/
│   └── images/
├── api/                 (Backend Laravel - forapix-laravel)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/          ← .htaccess aponta aqui
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   ├── .htaccess        ← configuração de rewrite
│   ├── artisan
│   └── composer.json
└── .htaccess            ← forçar HTTPS (opcional)
```

---

## Dicas Extras

- **Desative DEBUG em produção:** `APP_DEBUG=false` no `.env`
- **Use filas se o Hostinger permitir:** `QUEUE_CONNECTION=database` + job workers
- **Configure backup automático** no hPanel para o banco de dados
- **Monitore logs** regularmente
- **Mantenha o Composer atualizado:** `composer self-update`
