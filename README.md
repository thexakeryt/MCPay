# MCPay

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-%3E%3D7.2.5-777BB4)
![Payments](https://img.shields.io/badge/payments-PayPal-00457C)

A lightweight donation / item shop for Minecraft servers, written in plain PHP.

Players pick an item on the site, enter their nickname, and pay through PayPal. When PayPal confirms the payment, MCPay connects to the server over **RCON** and runs the command attached to that item — the rank, currency or unban is delivered while the player is still in game.

The storefront also shows live server status (online/offline, current and max players, IP) pulled from the [mcsrvstat.us](https://api.mcsrvstat.us) API, and a feed of the latest approved purchases with player heads.

---

## Table of contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [PayPal setup](#paypal-setup)
- [Database schema](#database-schema)
- [Adding products](#adding-products)
- [Promo codes](#promo-codes)
- [Theming](#theming)
- [Localization](#localization)
- [Project structure](#project-structure)
- [Known limitations](#known-limitations)
- [Security notes](#security-notes)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)

---

## Features

- **Product catalog** driven by MySQL — logo, name, price, category and the RCON command to execute
- **Category filter** on the front page, rendered client-side
- **PayPal Checkout (Orders v2)** integration with server-to-server order creation
- **Webhook-based delivery** — the item is issued only after PayPal reports `CHECKOUT.ORDER.APPROVED`
- **RCON command execution** with a `%user%` placeholder replaced by the buyer's nickname
- **Promo codes** with percentage discounts
- **Live server status** — online state, player count, resolved IP:port, progress bar
- **Last purchases** widget with avatars from [mc-heads.net](https://mc-heads.net)
- **Twig themes** with a configurable theme folder
- **Multi-language UI** (English and Ukrainian included)
- **Responsive layout** with a burger menu and a "copy IP" button

---

## Tech stack

| Layer | Technology |
|---|---|
| Language | PHP >= 7.2.5 |
| Routing | [bramus/router](https://github.com/bramus/router) 1.6 |
| Templating | [twig/twig](https://twig.symfony.com/) 3.4 |
| RCON | [thedudeguy/rcon](https://github.com/thedudeguy/PHP-Minecraft-Rcon) 1.0 |
| Database | MySQL / MariaDB via PDO |
| Payments | PayPal REST API (Orders v2) |
| Frontend | Vanilla JS, CSS (no build step), Font Awesome, clipboard.js |
| Server info | mcsrvstat.us API |

Dependencies are vendored in `vendor/`, so the project runs without running Composer first.

---

## How it works

```
Player clicks a product
        │
        ▼
main.js  ──POST──►  /catalog/components/Pay.php
                         │  validates nickname (3–16 word chars)
                         │  looks up price, applies promo
                         │  creates a PayPal order (Orders v2)
                         │  stores purchase with status WAITING
                         ▼
                    returns PayPal approval URL
        │
        ▼
Player pays on paypal.com
        │
        ▼
PayPal  ──webhook──►  /catalog/components/Webhook.php
                         │  matches txn_id and amount
                         │  opens RCON connection
                         │  runs product.command with %user% replaced
                         │  sets purchase status to APPROVED
                         ▼
                    item delivered in game
```

---

## Requirements

- PHP 7.2.5 or newer with `pdo_mysql`, `curl` and `json`
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite` (an `.htaccess` with the rewrite rules is included) or an equivalent nginx config
- A Minecraft server with **RCON enabled** (`enable-rcon=true` in `server.properties`)
- A PayPal Business account with REST API credentials
- A publicly reachable HTTPS domain — PayPal will not deliver webhooks to `localhost`

---

## Installation

```bash
git clone https://github.com/<your-user>/MCPay.git
cd MCPay
```

1. Point your web server's document root at the project root. `.htaccess` rewrites everything that is not an existing file or directory to `index.php?route=...`.
2. Create the database and import the dump:

   ```bash
   mysql -u root -p -e "CREATE DATABASE mcpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p mcpay < mcpay.sql
   ```

3. Create the `promos` table (not included in the dump — see [Promo codes](#promo-codes)).
4. Edit `settings.php` (see below).
5. Enable RCON on the Minecraft server:

   ```properties
   enable-rcon=true
   rcon.port=25575
   rcon.password=your-rcon-password
   ```

6. Register the webhook URL in your PayPal app.

If you prefer to reinstall dependencies yourself:

```bash
composer require bramus/router twig/twig thedudeguy/rcon
```

---

## Configuration

All settings live in `settings.php`:

```php
// Server settings
$ip            = '127.0.0.1';   // Minecraft server IP (used for status and RCON)
$port          = '25565';       // Minecraft port
$rcon_port     = 25575;         // RCON port
$rcon_password = 'password';    // RCON password
$domain        = 'localhost';   // Address copied by the "Begin to play" button
$name          = 'MCPay';       // Server name shown on the site

// Theme settings
$theme            = 'default';  // Folder under content/themes/
$language         = 'en';       // 'en' or 'ukr'
$seo_description  = 'MCPay - the best minecraft server';

// Database settings
$db_host     = 'localhost';
$db_user     = 'root';
$db_password = 'root';
$db_name     = 'mcpay';

// Shop & PayPal
$payment_sytem = 'paypal';
$currency      = 'EUR';
$paypal_email  = '';
$client_id     = 'client_id';
$secret        = 'secret_key';
$return_url    = 'https://example.com';   // Where PayPal sends the buyer back
```

> `settings.php` holds live credentials. Add it to `.gitignore` and keep a `settings.example.php` in version control instead.

---

## PayPal setup

1. Create a REST app at the [PayPal Developer Dashboard](https://developer.paypal.com/dashboard/) and copy the **Client ID** and **Secret** into `settings.php`.
2. Add a webhook pointing at:

   ```
   https://your-domain.com/catalog/components/Webhook.php
   ```

3. Subscribe it to the **`CHECKOUT.ORDER.APPROVED`** event.
4. Set `$return_url` to the page buyers should land on after paying (the router exposes `/success`).

The code calls the **live** endpoint `https://api-m.paypal.com`. For testing, switch the two URLs in `catalog/classes/Paypal.php` to `https://api-m.sandbox.paypal.com` and use sandbox credentials.

---

## Database schema

| Table | Purpose |
|---|---|
| `products` | Shop items: `logo`, `name`, `price`, `category`, `command` |
| `categories` | Filter buttons; `display = 1` shows the category |
| `purchases` | Order log: `item_name`, `payment_status`, `payment_amount`, `payment_currency`, `txn_id`, `player`, `type` |
| `header_nav` | Top navigation links (`name`, `url`) |
| `footer_nav` | Footer links (`name`, `url`) |
| `promos` | Promo codes (`name`, `percent`) — **create manually** |

`payment_status` moves from `WAITING` (order created) to `APPROVED` (paid and delivered). Only `APPROVED` rows appear in the "Last purchases" widget.

---

## Adding products

Insert a row into `products`:

```sql
INSERT INTO `products` (`logo`, `name`, `price`, `category`, `command`) VALUES
('vip.png', '[VIP]', 3, 'Donate', 'pex user %user% group add Vip');
```

- `logo` — file name inside `content/themes/<theme>/assets/img/`
- `category` — must match a `categories.name` value for the filter to work
- `command` — any console command; `%user%` is replaced with the buyer's nickname

Examples of commands: `op %user%`, `pardon %user%`, `eco give %user% 5000`, `lp user %user% parent add vip`.

---

## Promo codes

`Pay.php` looks up promo codes in a `promos` table that the SQL dump does not create. Add it:

```sql
CREATE TABLE `promos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(256) NOT NULL,
  `percent` FLOAT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `promos` (`name`, `percent`) VALUES ('SUMMER', 20);
```

The discount is applied as `price - (percent * price) / 100`.

---

## Theming

Themes live in `content/themes/<name>/` and are selected with `$theme`. The default theme contains:

```
base.twig       page shell (head, header, footer)
index.twig      home page, includes the blocks below
header.twig     logo + navigation + "Begin to play"
filters.twig    category filter bar
products.twig   product grid
info.twig       server status and last purchases
popup.twig      nickname / promo modal
footer.twig     footer links and contact line
button.twig     copy-IP button
```

Variables available to templates: `theme_path`, `site_logo`, `seo_description`, `server_status`, `server_players`, `server_ip`, `server_name`, `domain`, `header_nav`, `footer_nav`, `categories`, `products`, `purchases`, `language`.

Styles are in `assets/css/style.css` (dark `#1A1B26` background, orange `#F26822` accent) and behaviour in `assets/javascript/main.js`.

---

## Localization

Language files return a plain PHP array:

```
content/themes/default/language/en-en/en.php
content/themes/default/language/ukr-ukr/ukr.php
```

To add a new language, create `content/themes/<theme>/language/<code>-<code>/<code>.php` with the same keys and set `$language = '<code>'`. Strings are reachable in Twig as `{{ language.products_title }}` and so on.

---

## Project structure

```
.
├── index.php                     entry point, routes and view data
├── settings.php                  all configuration
├── mcpay.sql                     database dump
├── .htaccess                     rewrite rules
├── catalog/
│   ├── classes/
│   │   ├── App.php               theme paths, components, purchases
│   │   ├── Paypal.php            OAuth token + order creation
│   │   └── ServerInfo.php        mcsrvstat.us status wrapper
│   └── components/
│       ├── Pay.php               AJAX endpoint: validate, discount, create order
│       └── Webhook.php           PayPal webhook: verify, RCON, mark approved
├── content/themes/default/       Twig theme, assets, language files
└── vendor/                       Composer dependencies (vendored)
```

---

## Known limitations

These are worth knowing before running MCPay in production:

- **Only two routes exist** (`/` and `/success`). The navigation links seeded in `header_nav` (`/rules`, `/howtobuy`, `/other`) return 404 until routes are added.
- **No admin panel.** Products, categories, promos and navigation are edited directly in the database.
- **Stray character in `Pay.php`.** There is a lone `-` on its own line between the promo block and the main `if`, which is a PHP parse error. Remove it before deploying.
- **The promo field is never sent.** `main.js` posts only `item` and `nickname`, so `$_POST['promo']` is undefined; either add the field to the request or guard it with `?? ''`.
- **`promos` table missing** from the SQL dump (see above).
- **`Paypal::newShop()` references an undefined `$name`** variable.
- **No caching of server status** — each page render makes several calls to the mcsrvstat.us API (`getServerStatus`, `getPlayers` and `getIp` each fetch separately).
- **Currency symbol is hardcoded** as `€` in `products.twig`, independent of `$currency`.
- **Failed and refunded payments are not handled** — only `CHECKOUT.ORDER.APPROVED` is processed, so `WAITING` rows accumulate.
- **`getPurchases()` returns a `PDOStatement`** rather than an array; it works in Twig but is inconsistent with `getComponent()`.

---

## Security notes

Pull requests addressing these are welcome:

- **The webhook signature is not verified.** `Webhook.php` trusts the incoming JSON body. Anyone who knows the URL and a `txn_id` can trigger delivery. Use PayPal's `/v1/notifications/verify-webhook-signature` endpoint, or re-fetch the order from the PayPal API before issuing the item.
- **SQL string interpolation in `Webhook.php`.** `$txn_id` and `$item_name` are concatenated into queries; switch to prepared statements as done in `Pay.php`.
- **`App::getComponent()` interpolates a table name** into the query. It is only called with hardcoded values today, but it should never receive user input.
- **Credentials live in `settings.php`** inside the document root. Move secrets to environment variables, and at minimum keep the file out of version control.
- **`vendor/` is committed** with a pinned Twig 3.4.1; update dependencies regularly.

---

## Roadmap

- [ ] Admin panel for products, categories and promo codes
- [ ] PayPal webhook signature verification
- [ ] Additional payment providers (Stripe, crypto, local gateways)
- [ ] Static pages (rules, how to buy) backed by the router
- [ ] Caching layer for server status
- [ ] Delivery queue for players who are offline at the time of purchase
- [ ] Composer-based install with `composer.json` in the repository

---

## Contributing

Issues and pull requests are welcome — especially for the items listed under
[Known limitations](#known-limitations) and [Security notes](#security-notes).
Fork the repository, create a branch, and open a PR describing what changed and how you tested it.

---

## License

Released under the [MIT License](LICENSE) — free to use, modify, distribute and sell,
including commercially, as long as the copyright notice is kept. The software is provided
as is, without warranty of any kind.
