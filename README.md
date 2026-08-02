# Agrovet POS

A point-of-sale system for a single-shop agrovet (agricultural and veterinary supplies) in Kenya. Built with Laravel 12, Livewire 3, and Tailwind CSS.

## What it does

- **Sales** — barcode/name search, fractional quantities (sell a 50kg bag down to the gram), split payments (cash / M-Pesa / credit), hold & resume, 80mm thermal receipt printing, PIN-gated void and returns.
- **Inventory** — batch and expiry tracking with FEFO (first-expiring, first-out) stock deduction, stock adjustments with mandatory reasons, stock-take/variance workflow, low-stock and expiry alerts.
- **Customers & credit** — per-customer ledger, credit limits with owner-PIN override, aging debtors report, printable statements.
- **Reports** — daily summary, sales by date/product/category/attendant, profit (owner-only), stock valuation, fast/slow movers, cash-up (declared vs. expected drawer cash), filterable audit log.
- **Roles** — Owner, Manager, Attendant. Attendants never see buying prices or profit and cannot edit prices; every sensitive action (void, refund, discount, price change, stock adjustment, login) is audit-logged.

Money is stored as integer cents throughout to avoid floating-point drift. Timezone is `Africa/Nairobi`.

## Tech stack

- PHP 8.3, Laravel 12
- Livewire 3 (server-rendered, minimal JS)
- Tailwind CSS v4
- MySQL in production, SQLite for local development (migrations are MySQL-compatible)

## Local development

Prerequisites: PHP 8.3+, Composer, Node.js, and the PHP extensions Laravel needs (mbstring, xml, curl, zip, bcmath, gd, intl, sqlite3).

```bash
composer install
npm install
cp .env.example .env   # already configured for SQLite; adjust as needed
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve
```

Visit `http://127.0.0.1:8000/login`. Seeded accounts (all password `password`):

| Role | Email | PIN |
|---|---|---|
| Owner | owner@agrovet.test | 1234 |
| Manager | manager@agrovet.test | 5678 |
| Attendant | attendant@agrovet.test | — (attendants have no approval PIN) |

While developing, run `npm run dev` in a separate terminal instead of `npm run build` for hot-reloading assets.

### Running tests

```bash
php artisan test
```

The suite runs against an in-memory SQLite database and covers the areas most likely to cost real money if wrong: money math, unit/fractional-quantity handling, FEFO batch selection, credit ledger balances, and role-based authorization.

### Resetting demo data

```bash
php artisan migrate:fresh --seed
```

## Project structure

Business logic that mutates data lives in single-purpose, tested `Action` classes under `app/Actions/`, grouped by domain (`Sales`, `Inventory`, `Customers`, `Reports`, `Cash`, `Auth`). Livewire components under `app/Livewire/` are thin — they gather input, call an Action, and render. This split is what's unit-tested; the Livewire components are covered by feature tests that drive them the way a browser would.

```
app/
  Actions/          Sales, Inventory, Customers, Reports, Cash, Auth — the tested core logic
  Console/Commands/ BackupDatabase
  Livewire/          One namespace per feature area, matching routes/web.php
  Models/
  Exceptions/        Domain exceptions (InsufficientStock, CreditLimitExceeded, TooManyPinAttempts)
database/
  migrations/  seeders/  factories/
resources/views/livewire/   Blade views, one per Livewire component
tests/
  Unit/Actions/      Business logic tests (money, FEFO, aging, etc.)
  Feature/           HTTP/Livewire-driven tests (role gating, full flows)
```

## Roles and PIN approvals

- **Owner** — everything, including user management and profit visibility.
- **Manager** — everything except user management; sees buying prices and profit.
- **Attendant** — sells, views stock levels and selling prices only. Cannot edit prices, cannot see buying price or profit.

Owner and manager accounts have a separate 4-digit PIN (set from **Users**, distinct from their login password) used for step-up approval at the terminal without requiring a logout:

- **Applying a discount** or **adjusting stock** — requires being logged in as owner/manager directly (no PIN prompt needed since the elevated session already proves it).
- **Processing a return/refund** — any role can initiate it, but completing it needs an **owner or manager** PIN.
- **Voiding a sale** — any role can initiate it, but completing it needs the **owner's** PIN specifically (stricter than a return, since a void fully undoes a transaction).
- **Overriding a customer's credit limit** — owner PIN only.

PIN attempts are rate-limited (5 tries per terminal, then a 5-minute cooldown) to prevent brute-forcing the 4-digit PIN.

## Database backups

`php artisan backup:database` dumps the database to `storage/app/backups/` — a `.sqlite` copy locally, a gzip-compressed `mysqldump` in production — and prunes backups older than 30 days (`--keep-days=N` to change that). It's scheduled to run daily at 02:00 via `routes/console.php`.

This only creates the *local* backup. To ship backups offsite (recommended — a backup that lives on the same VPS as the database doesn't protect against server loss), point a small daily cron job at `storage/app/backups/` with `rclone`, `rsync`, or a similar tool pushing to cloud storage. That's intentionally not built in — set it up with whatever storage the shop owner already trusts.

## Deploying to a VPS

This assumes a fresh Ubuntu 24.04 VPS (a $5–6/month box is plenty for a single-shop till). Run as a non-root sudo user.

### 1. Install the stack

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server \
  php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl \
  unzip curl git

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Create the database

```bash
sudo mysql_secure_installation
sudo mysql -u root -p
```
```sql
CREATE DATABASE agrovet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'agrovet'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
GRANT ALL PRIVILEGES ON agrovet.* TO 'agrovet'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Deploy the code

```bash
sudo mkdir -p /var/www/agrovet
sudo chown $USER:$USER /var/www/agrovet
git clone <your-repo-url> /var/www/agrovet
cd /var/www/agrovet

composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 4. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_NAME=Agrovet
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.co.ke
APP_TIMEZONE=Africa/Nairobi

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agrovet
DB_USERNAME=agrovet
DB_PASSWORD=choose-a-strong-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true

LOG_LEVEL=warning
```

`APP_DEBUG=false` matters — with it `true`, error pages leak stack traces (including buying prices and other data an attendant shouldn't see) to anyone who triggers an error.

### 5. Migrate and create the owner account

Do **not** run the demo seeders in production — they create test accounts with the password `password`. Instead migrate the schema, then create the real owner account directly:

```bash
php artisan migrate --force

php artisan tinker
```
```php
\App\Models\User::create([
    'name' => 'Real Owner Name',
    'email' => 'owner@realdomain.co.ke',
    'phone' => '07XXXXXXXX',
    'password' => 'a-strong-password',
    'role' => 'owner',
    'is_active' => true,
]);
```

The owner can then create manager/attendant accounts from the **Users** screen, and set everyone's approval PIN from there too.

### 6. File permissions

```bash
sudo chown -R www-data:www-data /var/www/agrovet
sudo chmod -R 775 storage bootstrap/cache
```

### 7. Nginx site config

`/etc/nginx/sites-available/agrovet`:

```nginx
server {
    listen 80;
    server_name your-domain.co.ke;
    root /var/www/agrovet/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/agrovet /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 8. HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.co.ke
```

Certbot edits the Nginx config to redirect HTTP → HTTPS and sets up auto-renewal. Once this is live, `SESSION_SECURE_COOKIE=true` (already set above) takes effect correctly.

### 9. Scheduler (for daily backups)

Laravel's scheduler needs one cron entry that fires every minute; it decides internally what actually needs to run:

```bash
crontab -e
```
```
* * * * * cd /var/www/agrovet && php artisan schedule:run >> /dev/null 2>&1
```

Verify it's picked up:

```bash
php artisan schedule:list
```

### 10. Smoke test

- Visit the site over HTTPS, confirm the login page loads.
- Log in as the owner account created in step 5.
- Create a test product, sell it, print a receipt, void it — confirm the PIN prompts work.
- Run `php artisan backup:database` manually once and confirm a file lands in `storage/app/backups/`.

## Updating the deployed app

```bash
cd /var/www/agrovet
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.3-fpm
```

## What's intentionally not built

Per the original scope: multi-branch support, accounting/payroll, supplier purchase orders, M-Pesa Daraja API integration (M-Pesa payments are recorded by manually entering the transaction code), KRA eTIMS, and SMS sending. The schema was designed so these can be added later without a rework — for example, `payments.method` is a plain string rather than a fixed enum specifically so a future `mpesa_api` method needs no migration.
