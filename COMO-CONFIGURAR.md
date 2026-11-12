# Forninho da Lili — como publicar (Render com PHP)

## Estrutura de pastas (precisa ser exatamente esta)

```
index.html
api.php
api-config.php
.htaccess
Dockerfile
docker/
admin/        <- index.html e admin.js do painel
css/          <- style.css
js/           <- backend.js, dados-padrao.js, pedidos.js, site.js, entrar.js
storage/
```

Se `js/`, `css/` ou `admin/` não estiverem no GitHub, o site dá erro 404 e fica em "Carregando...".

## Publicar no Render

1. Envie a pasta inteira para o GitHub (inclusive as pastas `js`, `css`, `admin`).
2. No Render, crie um **Web Service** (não Static Site), ambiente **Docker**, apontando para o repositório.
3. Após o deploy, abra `https://SEU-SITE.onrender.com/admin/`.

## Não perder os dados

No plano gratuito, o disco é apagado a cada deploy/reinício (somem produtos e pedidos).
Para manter: Render > serviço > **Disks** > Add Disk, com **Mount Path** `/var/www/html/storage`.
(Disco persistente é recurso pago.) Faça backup periódico de `storage/dados.json`.

## Acesso da dona

- E-mail: `dmmsleme@gmail.com`
- Senha inicial: `TroqueEstaSenha123!` — troque em **🔒 Mudar senha** logo no primeiro acesso.

## Testes após publicar

- `/js/backend.js` deve mostrar o código.
- `/api.php` deve responder JSON (`{"error":...}`), nunca mostrar código PHP.
- `/api-config.php` e `/storage/dados.json` devem dar erro 403.

## Pix e regras comerciais

Antecedência mínima de 24 h, entrega só em Cajamar-SP, taxa inicial R$ 10,00 (negociável).
Pix: chave telefone 11989634843, beneficiária Daniele Miranda Marques Santos. Faça um pedido de teste e confira no app do banco.
