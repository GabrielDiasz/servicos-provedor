# Servicos Provedor

Sistema Laravel para gestão de ordens de serviço de provedor de internet, com autenticação via Breeze, interface em Tailwind, integração com WhatsApp por microserviço local e sincronização com o SGP.

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

O `DatabaseSeeder` só cria o usuário admin e o grupo de teste quando essas variáveis estiverem preenchidas.

## WhatsApp

O projeto usa um microserviço Node com `whatsapp-web.js`.

Para iniciar:

```bash
node whatsapp-server.js
```

## SGP

A integração com o SGP é feita por login web autenticado e depende das credenciais configuradas em `SGP_WEB_USERNAME` e `SGP_WEB_PASSWORD`.

A captura do print do endereço usa essas mesmas credenciais e o navegador local definido em `CHROME_PATH` quando ele estiver configurado.

## Execução local

```bash
php artisan serve
```

Em outro terminal, deixe o microserviço do WhatsApp ativo:

```bash
node whatsapp-server.js
```

Para a fila, use um worker persistente com timeout maior para permitir a captura do print do SGP:

```bash
php artisan queue:work --queue=default --sleep=3 --tries=3 --timeout=180
```

## Testes

```bash
php artisan test
```
