# Site da APE

Portal de emprego da APE, Agência Privada de Emprego (Associação Comercial da Beira). Laravel 13 com o site público em Blade + Tailwind 4 e o painel de gestão em `/admin`, uma aplicação React 19 + MUI (Able Pro) que fala com a API `/api/admin` (Sanctum, sessão por cookie), tudo compilado com Vite.

## Requisitos

- PHP 8.4 ou superior com `intl`, `gd`, `pdo_sqlite`, `zip` e `memory_limit` de pelo menos 256M
- Composer 2 e Node 20 ou superior

## Instalação (desenvolvimento)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# definir MEDIA_GLIDE_TOKEN no .env (php -r "echo bin2hex(random_bytes(32));")
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve --port=8021
```

A conta de administração é criada pelo `AdminUserSeeder` com os valores `ADMIN_*` do `.env`. Se `ADMIN_PASSWORD` estiver vazio, é gerada uma palavra-passe e mostrada no terminal. A verificação em dois passos é obrigatória por omissão (`ADMIN_MFA_REQUIRED=true`). Noutra porta, ajustar `APP_URL` e `SANCTUM_STATEFUL_DOMAINS`, senão o início de sessão falha sem aviso.

## Site público

- `/vagas`: grelha ou lista, com pesquisa e filtros por estado, categoria, local e tipo de contrato
- `/vagas/{slug}`: ficha, descrição e candidatura. Os campos vazios não aparecem e as vagas encerradas não aceitam candidaturas
- `/candidatura`: candidatura a uma vaga aberta ou espontânea
- `/empresas`: vantagens e inscrição de empresa
- `/servicos`, `/parceiros`, `/noticias`, `/sobre-nos`, `/contactos`, `/politica-de-privacidade`, `/termos-de-utilizacao`

A página inicial é feita de blocos editáveis no painel (Conteúdo > Páginas), incluindo o topo com pesquisa, categorias, faixa para empresas, vagas e vagas por local.

## Painel de gestão (/admin)

- **Caixa de entrada:** candidaturas, empresas inscritas e mensagens.
- **Vagas:** vagas e categorias.
- **Conteúdo:** páginas por blocos, menus, serviços e biblioteca de média.
- **Notícias**, com categorias e etiquetas. **Institucional:** equipa.
- **Definições:** site e contactos, SEO e Analytics.
- **Administração:** utilizadores, perfis e permissões.

```bash
npm run dev      # Vite com recarregamento
npx tsc --noEmit # verificação de tipos
npm run build    # compilação de produção em public/build
```

## Dados pessoais

- O CV fica em `storage/app/private/candidaturas`, sem endereço público, e descarrega-se só pelo painel.
- Ver candidaturas e CV exige a permissão **Candidaturas e currículos**, que o perfil "Gestor de conteúdos" não recebe por omissão.
- O número do documento de identificação fica cifrado na base de dados, com a `APP_KEY`. **Mudar a `APP_KEY` torna esses números ilegíveis.**
- O e-mail de aviso à APE não leva o CV nem dados de identificação.
- "Quero receber um e-mail de confirmação" envia um texto fixo, sem nada do que foi escrito no formulário.
- Apagar uma candidatura apaga também o CV.

## Publicação

O repositório traz um `Dockerfile` que compila o site e o painel e serve a aplicação com PHP, FPM e Nginx na porta 8080.

1. Criar a aplicação a partir do repositório, com *Build Type* **Dockerfile**.
2. Criar uma base de dados MySQL e definir as variáveis de ambiente:

   ```env
   APP_NAME="APE, Agência Privada de Emprego"
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:...            # php artisan key:generate --show
   APP_URL=https://ape.acb.co.mz
   TRUSTED_PROXIES=*
   MEDIA_GLIDE_TOKEN=...         # php -r "echo bin2hex(random_bytes(32));"

   DB_CONNECTION=mysql
   DB_HOST=...
   DB_PORT=3306
   DB_DATABASE=ape
   DB_USERNAME=...
   DB_PASSWORD=...

   SESSION_SECURE_COOKIE=true
   SANCTUM_STATEFUL_DOMAINS=ape.acb.co.mz

   ADMIN_NAME="Administrador"
   ADMIN_EMAIL=...
   ADMIN_PASSWORD=...
   ADMIN_MFA_REQUIRED=true

   MAIL_MAILER=smtp
   MAIL_HOST=...
   MAIL_PORT=587
   MAIL_USERNAME=...
   MAIL_PASSWORD=...
   MAIL_FROM_ADDRESS=...
   ```

3. Domínio apontado à porta **8080**, com HTTPS.
4. Volumes persistentes: `/var/www/html/storage/app/public` (imagens e vídeos) e `/var/www/html/storage/app/private` (**currículos**).
5. Publicar. A cada arranque o contentor corre as migrações e o `storage:link`. Só na **primeira vez**, no terminal do contentor: `php artisan db:seed --force`, que cria os perfis, a conta de administração e todo o conteúdo inicial.

O *healthcheck* usa `/up`. Depois de publicar, confirmar: um endereço antigo (`/index.php/vagas/20-mecanica/31-vaga-de-chefe-de-oficina`), uma candidatura com CV, o e-mail e a verificação em dois passos.

## Testes

```bash
php artisan test
```
