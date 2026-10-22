# Come far girare i test in locale

I test girano **solo** sul database `pap_test` (il guard in `TestCase` blocca tutto il resto). Il perché delle scelte è in [docs/knowledge/test-db-dedicato.md](../knowledge/test-db-dedicato.md).

## Setup una tantum

1. Variabili Docker nel `.env` locale (non committate), se mancanti — porte libere sulla propria macchina, bind solo loopback:
   ```
   DOCKER_PROJECT_DIR_NAME=portapporta
   DOCKER_PHP_PORT=9800
   DOCKER_SERVE_PORT=8800
   DOCKER_PSQL_PORT=5433
   DB_PASSWORD=<una password locale qualsiasi>
   ```
2. `docker compose up -d php db redis` — i nomi dei container derivano da `APP_NAME` (`php_PortAPPorta`, `postgres_PortAPPorta`).
3. Database di test (con l'utente del proprio `.env`, **non** `root`):
   ```bash
   docker exec postgres_PortAPPorta createdb -U <DB_USERNAME> pap_test
   docker exec postgres_PortAPPorta psql -U <DB_USERNAME> -d pap_test -c "CREATE EXTENSION IF NOT EXISTS postgis;"
   ```
4. `.env.testing` nella root (non committato):
   ```
   APP_ENV=testing
   APP_KEY=<stessa chiave di .env>
   DB_CONNECTION=pgsql
   DB_HOST=db
   DB_PORT=5432
   DB_DATABASE=pap_test
   DB_USERNAME=<come .env>
   DB_PASSWORD=<come .env>
   CACHE_DRIVER=array
   MAIL_MAILER=array
   ```
   `MAIL_MAILER=array` è necessario: i test di registrazione spediscono una notifica, e senza il mailer array proverebbero un invio SMTP reale (530 Authentication required).
5. Migrazione del DB di test:
   ```bash
   docker exec php_PortAPPorta php artisan migrate --env=testing
   ```

## Ogni run

```bash
docker exec php_PortAPPorta php artisan test
```

Se compaiono 404 inattesi su route esistenti o config strana: cache stantia → `docker exec php_PortAPPorta php artisan optimize:clear` (copre config **e** route cache; il solo `config:clear` non basta se `bootstrap/cache/routes-v7.php` è vecchio).
