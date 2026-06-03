> Ticket: oc:7991

# Plan — Revisione test suite: db di test dedicato per il backend

## Contesto

- **Repo:** portapporta_docker (backend Laravel 12 / PHP 8.4)
- **Classificazione:** Custom — tutto nel repo principale, nessun submodule
- **Problemi da risolvere:**
  1. Test girano su DB di sviluppo `pap` → `RefreshDatabase` distrugge dati dev, factory con ID espliciti falliscono
  2. 45 test V2 falliscono con HTTP 429 → config in cache (`bootstrap/cache/config.php`) ignora `CACHE_DRIVER=array` di `phpunit.xml`, il rate limiter usa file cache
  3. CI usa `huaxk/postgis-action@v1` deprecata (punta a `mdillon/postgis` rimossa da Docker Hub)
  4. `.env.test` obsoleto punta a `pap` con `CACHE_DRIVER=file` — fonte di confusione

---

## Step 1 — Crea database `pap_test` in locale

**Manuale (una-tantum per ogni sviluppatore):**

```bash
docker exec postgres_portapporta createdb -U root pap_test
docker exec postgres_portapporta psql -U root -d pap_test -c "CREATE EXTENSION IF NOT EXISTS postgis;"
```

Verifica:
```bash
docker exec postgres_portapporta psql -U root -d pap_test -c "\dx" | grep postgis
```

> Questo step non produce file committati — è un prerequisito di ambiente.

---

## Step 2 — Crea `.env.testing`

Crea il file `.env.testing` nella root del progetto:

```
APP_ENV=testing
APP_KEY=base64:GENERA_CON_artisan_key_generate

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=pap_test
DB_USERNAME=root
DB_PASSWORD=password

CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
MAIL_MAILER=array
TELESCOPE_ENABLED=false
```

> `DB_HOST=db` è il nome del container Docker (come in `.env`). In CI sarà overridato da `.env.example` + variabili d'ambiente del workflow.

Commit: nessuno — `.env.testing` è già in `.gitignore` (riga 7).

---

## Step 3 — Aggiorna `.gitignore`

Aggiungi `.env.test` al `.gitignore`:

**File:** `.gitignore`

Dopo la riga `.env.backup` aggiungi:
```
.env.test
```

Commit: `feat(oc:7991): add .env.test to .gitignore`

---

## Step 4 — Elimina `.env.test`

Elimina il file obsoleto che punta a `pap` con `CACHE_DRIVER=file`:

```bash
git rm .env.test
```

Commit insieme allo Step 3: `feat(oc:7991): add .env.test to .gitignore and remove stale file`

---

## Step 5 — Aggiorna `phpunit.xml`

**File:** `phpunit.xml`

Rimuovi le righe SQLite commentate e allinea alle variabili gestite da `.env.testing`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
    </php>
</phpunit>
```

> Le variabili `CACHE_DRIVER`, `QUEUE_CONNECTION`, `SESSION_DRIVER`, `MAIL_MAILER`, `TELESCOPE_ENABLED` sono ora in `.env.testing` — più esplicite e visibili per chi configura l'ambiente.

Commit: `feat(oc:7991): clean up phpunit.xml, move env vars to .env.testing`

---

## Step 6 — Aggiungi guard in `TestCase.php`

**File:** `tests/TestCase.php`

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $db = config('database.connections.' . config('database.default') . '.database');
        if ($db !== 'pap_test') {
            throw new \RuntimeException(
                "Test aborted: connected to database '{$db}', expected 'pap_test'.\n" .
                "Setup: docker exec postgres_portapporta createdb -U root pap_test\n" .
                "       docker exec postgres_portapporta psql -U root -d pap_test -c \"CREATE EXTENSION IF NOT EXISTS postgis;\"\n" .
                "       php artisan config:clear\n" .
                "       php artisan migrate --env=testing"
            );
        }
    }
}
```

Commit: `feat(oc:7991): add DB guard in TestCase to prevent running tests on dev database`

---

## Step 7 — Disabilita throttle in ambiente testing

**File:** `app/Providers/RouteServiceProvider.php`

Modifica il metodo `configureRateLimiting()`:

```php
protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        if (app()->environment('testing')) {
            return Limit::none();
        }
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
}
```

Assicurarsi che `Limit` sia importato in cima al file (già presente).

Commit: `feat(oc:7991): disable API throttle in testing environment`

---

## Step 8 — Migra `pap_test` in locale

Prima di eseguire i test per la prima volta:

```bash
docker exec php_portapporta php artisan config:clear
docker exec php_portapporta php artisan migrate --env=testing
```

Verifica che la migrazione sia andata a buon fine:
```bash
docker exec postgres_portapporta psql -U root -d pap_test -c "\dt" | head -20
```

> Questo step non produce file committati.

---

## Step 9 — Verifica test in locale

```bash
docker exec php_portapporta php artisan test
```

Risultato atteso: tutti i test passano (0 failed).

Se qualche test fallisce ancora, analizzare il messaggio di errore prima di procedere con la CI.

---

## Step 10 — Aggiorna pipeline develop CI

**File:** `.github/workflows/laravel-develop-pipe.yml`

Sostituisci l'intera sezione `tests` job con il pattern service container nativo di GitHub Actions:

```yaml
name: Laravel DEV
on:
  push:
    branches:
      - develop
jobs:
  tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgis/postgis:14-3.3
        env:
          POSTGRES_USER: root
          POSTGRES_PASSWORD: password
          POSTGRES_DB: pap_test
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
      - name: Copy .env
        run: cp .env.example .env
      - name: Set test DB in .env
        run: |
          sed -i 's/DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/' .env
          sed -i 's/DB_DATABASE=pap/DB_DATABASE=pap_test/' .env
          sed -i 's/DB_PASSWORD=/DB_PASSWORD=password/' .env
          sed -i 's/CACHE_DRIVER=file/CACHE_DRIVER=array/' .env
      - name: Install Dependencies
        run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress -o
      - name: Generate key
        run: php artisan key:generate
      - name: Enable PostGIS extension
        run: PGPASSWORD=password psql -h 127.0.0.1 -U root -d pap_test -c "CREATE EXTENSION IF NOT EXISTS postgis;"
      - name: Migrate test DB
        run: php artisan migrate --force
      - name: Laravel Tests
        run: php artisan test
  deploy:
    # ... invariato
```

> In CI non usiamo `.env.testing` perché il container non ha Docker interno. Operiamo direttamente sull'`.env` con `sed`. Il guard in `TestCase` verifica `DB_DATABASE=pap_test` — il `sed` sopra lo imposta correttamente.

Commit: `feat(oc:7991): update develop CI to use native postgres service with PostGIS`

---

## Step 11 — Aggiorna pipeline prod CI

**File:** `.github/workflows/laravel-prod-pipe.yml`

Stessa sostituzione dell'action deprecata. La prod pipeline esegue i test prima del deploy — aggiornare la base PostgreSQL senza cambiare la logica di test (usa ancora `pap` come DB name perché il guard non è necessario in prod pipeline, oppure usa `pap_test` per coerenza).

Applica lo stesso pattern service container del DEV pipeline.

Commit: `feat(oc:7991): update prod CI to use native postgres service with PostGIS`

---

## Step 12 — Aggiorna `CLAUDE.md`

Aggiungi sezione **Setup ambiente di test** e aggiorna le sezioni feature/decisioni.

Commit: incluso nel commit finale insieme a `notes.md`.

---

## Checklist pre-PR

- [ ] `pap_test` creato localmente con PostGIS
- [ ] `php artisan migrate --env=testing` eseguito
- [ ] `php artisan test` → 0 failed
- [ ] Guard in `TestCase` testato: verificare che fallisca con messaggio chiaro se `DB_DATABASE=pap`
- [ ] CI pipeline aggiornata su entrambi i branch
