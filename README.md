# Servicos Provedor

Sistema Laravel para gestão de ordens de serviço da GPR Fibra, com autenticação via Breeze, interface em Tailwind, integração com WhatsApp por microserviço local e sincronização com o SGP.

## Visão geral

- Cadastro e acompanhamento de OS
- Gestão de técnicos, usuários e grupos WhatsApp
- Dashboard com indicadores operacionais
- Edição inline de status e técnico na listagem
- Integração com WhatsApp apenas por ação manual do usuário
- Sincronização com o SGP no momento do envio da OS

## Requisitos

- PHP 8.2+
- Composer
- Node.js 20+
- MySQL ou MariaDB
- Google Chrome ou Microsoft Edge para o microserviço do WhatsApp

## Instalação

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

## Configuração do ambiente

Preencha o `.env` com as credenciais do seu ambiente. Nunca envie esse arquivo para o GitHub.

Variáveis principais:

- `APP_URL`
- `DB_*`
- `WHATSAPP_SERVICE_URL`
- `WHATSAPP_TOKEN`
- `WHATSAPP_HOST`
- `CHROME_PATH`
- `SGP_BASE_URL`
- `SGP_APP`
- `SGP_TOKEN`
- `SGP_WEB_USERNAME`
- `SGP_WEB_PASSWORD`
- `SGP_DEFAULT_RESPONSAVEL`
- `ATTENDANT_PABLO_NAME`
- `ATTENDANT_PABLO_EMAIL`
- `ATTENDANT_PABLO_PASSWORD`
- `ATTENDANT_PABLO_SGP_RESPONSAVEL_NOME`
- `ATTENDANT_PABLO_SGP_RESPONSAVEL_LOGIN`
- `ATTENDANT_PAULO_NAME`
- `ATTENDANT_PAULO_EMAIL`
- `ATTENDANT_PAULO_PASSWORD`
- `ATTENDANT_PAULO_SGP_RESPONSAVEL_NOME`
- `ATTENDANT_PAULO_SGP_RESPONSAVEL_LOGIN`
- `SGP_TECH_MATCHER_JHON`
- `SGP_RESPONSAVEL_JHON`
- `SGP_TECH_MATCHER_VANDERLEY`
- `SGP_RESPONSAVEL_VANDERLEY`
- `SGP_TECH_MATCHER_TESTE`
- `SGP_RESPONSAVEL_TESTE`
- `SGP_TECH_MATCHER_A`
- `SGP_RESPONSAVEL_A`
- `SGP_TECH_MATCHER_B`
- `SGP_RESPONSAVEL_B`
- `SGP_TECH_MATCHER_C`
- `SGP_RESPONSAVEL_C`
- `ADMIN_EMAIL`
- `ADMIN_PASSWORD`
- `WHATSAPP_TEST_GROUP_ID`

O `DatabaseSeeder` só cria o usuário admin e o grupo de teste quando essas variáveis estiverem preenchidas.

## WhatsApp

O projeto usa um microserviço Node com `whatsapp-web.js`.

Para iniciar:

```bash
node whatsapp-server.js
```

O serviço expõe, por padrão:

- `http://127.0.0.1:3000/status`
- `http://127.0.0.1:3000/groups`
- `http://127.0.0.1:3000/send-message`

Se você definir `WHATSAPP_TOKEN`, o microserviço exige autenticação Bearer nas requisições.

## SGP

A integração com o SGP é feita por login web autenticado e depende das credenciais configuradas em `SGP_WEB_USERNAME` e `SGP_WEB_PASSWORD`.

## Execução local

```bash
php artisan serve
```

Em outro terminal, deixe o microserviço do WhatsApp ativo:

```bash
node whatsapp-server.js
```

## Testes

```bash
php artisan test
```

## Segurança

- Não comite `.env`
- Não comite `.whatsapp-session`
- Não comite `.wwebjs_cache`
- Não exponha tokens, senhas, IDs de grupos ou credenciais do SGP em arquivos públicos
- Use placeholders no `.env.example` e documente os valores reais apenas fora do repositório

## Observação

Se o projeto já tiver publicado algum segredo em histórico Git, o ideal é rotacionar essas credenciais e reescrever o histórico com cuidado.
