# Deployment Guide

This project is deployed on a VPS with CyberPanel using a separate Laravel project root and web root.

## Server Paths

- Project root: `/home/sistemeberkat.my/sistemeberkat`
- Web root: `/home/sistemeberkat.my/public_html`
- SQLite database: `/home/sistemeberkat.my/sistemeberkat/database/database.sqlite`

## One-Time Server Setup

After pulling the repository for the first time on the server:

```bash
cd /home/sistemeberkat.my/sistemeberkat
chmod +x deploy.sh
```

Optional: register a short global deploy command:

```bash
cd /home/sistemeberkat.my/sistemeberkat
chmod +x install-deploy-command.sh
./install-deploy-command.sh
```

After that, you can run this from anywhere on the server:

```bash
berkat-deploy
```

Make sure `.env` uses SQLite:

```env
APP_ENV=production
APP_URL=https://sistemeberkat.my
APP_DEBUG=true

DB_CONNECTION=sqlite
DB_DATABASE=/home/sistemeberkat.my/sistemeberkat/database/database.sqlite

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
```

Make sure writable directories stay writable:

```bash
chmod -R 777 /home/sistemeberkat.my/sistemeberkat/storage \
  /home/sistemeberkat.my/sistemeberkat/bootstrap/cache \
  /home/sistemeberkat.my/sistemeberkat/database
```

## Regular Deploy Flow

### 1. On local machine

```bash
cd /Users/user/Sites/e-berkat
git add .
git commit -m "Describe the changes"
git push origin main
```

### 2. On server

```bash
cd /home/sistemeberkat.my/sistemeberkat
./deploy.sh
```

Or, if the shortcut command was installed:

```bash
berkat-deploy
```

## What `deploy.sh` Does

The deployment script performs these steps:

1. Enables Laravel maintenance mode.
2. Pulls the latest code from `origin/main`.
3. Installs PHP dependencies.
4. Clears Laravel caches.
5. Runs database migrations.
6. Builds Vite assets.
7. Copies `public/*` into `/home/sistemeberkat.my/public_html`.
8. Fixes writable permissions for demo SQLite hosting.
9. Disables maintenance mode.

## Notes For This Server

- CyberPanel is serving `/home/sistemeberkat.my/public_html`, not Laravel's internal `public` folder directly.
- Because of that, frontend changes are not live until `public/*` is copied to `public_html`.
- This setup uses SQLite for demo purposes, not MySQL.
- If the site shows a blank page or HTTP 500 after frontend changes, verify that `public_html/build/manifest.json` has the latest timestamp.
- If login/logout fails with SQLite write errors, fix permissions on `storage`, `bootstrap/cache`, and `database`.

## Useful Checks

Check Laravel database connection:

```bash
cd /home/sistemeberkat.my/sistemeberkat
php artisan tinker --execute="dump(config('database.default')); dump(config('database.connections.sqlite.database'));"
```

Check latest Laravel log:

```bash
tail -n 80 /home/sistemeberkat.my/sistemeberkat/storage/logs/laravel.log
```

Check live build manifest:

```bash
curl -I https://sistemeberkat.my/build/manifest.json
```