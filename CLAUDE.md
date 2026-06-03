# CLAUDE.md

## Stack
- Laravel 12, PHP 8.4, PostgreSQL (PostGIS), Redis
- Docker: container PHP `php_portapporta`, DB `postgres_portapporta`
- Comandi artisan vanno lanciati dentro il container: `docker exec php_portapporta php artisan ...`

## Setup ambiente di test

I test girano su un database PostgreSQL dedicato `pap_test` (mai sul DB di sviluppo `pap`).

**Setup una-tantum per ogni sviluppatore:**
```bash
docker exec postgres_portapporta createdb -U root pap_test
docker exec postgres_portapporta psql -U root -d pap_test -c "CREATE EXTENSION IF NOT EXISTS postgis;"
docker exec php_portapporta php artisan config:clear
docker exec php_portapporta php artisan migrate --env=testing
```

Creare il file `.env.testing` nella root (non committato) con almeno:
```
APP_ENV=testing
APP_KEY=<stessa chiave di .env>
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=pap_test
DB_USERNAME=root
DB_PASSWORD=password
CACHE_DRIVER=array
```

**Prerequisito prima di ogni run di test:** se hai eseguito `php artisan optimize`, pulisci la cache:
```bash
docker exec php_portapporta php artisan config:clear
```

**Esecuzione test:**
```bash
docker exec php_portapporta php artisan test
```

Un guard in `TestCase::setUp()` abortisce con messaggio esplicito se i test vengono lanciati puntando a `pap`.

**Note:** il middleware `throttle:api` è disabilitato in ambiente `testing` — i test che verifichino il rate limiting vanno scritti con asserzioni esplicite e disabilitando il guard.

## Feature disponibili

| Feature | Ticket | Moduli toccati | Note |
|---|---|---|---|
| Revisione test suite: db di test dedicato | oc:7991 | `tests/TestCase.php`, `phpunit.xml`, `app/Providers/RouteServiceProvider.php`, `.github/workflows/*.yml` | DB `pap_test` dedicato, guard anti-dev-db, throttle disabilitato in testing, CI su PostgreSQL 14+PostGIS |
| Smistamento automatico segnalazioni Lunigiana | oc:7616 | `config/lunigiana.php`, `app/Models/Ticket.php`, `app/Http/Controllers/TicketController.php` | Duplica le email ticket verso urp@lunigianaambiente.it per le zone Lunigiana di ERSU |

## Decisioni architetturali

### Test suite (oc:7991)
- DB di test `pap_test` separato da `pap` (sviluppo) — `TestCase::setUp()` abortisce con messaggio actionable se il DB è sbagliato
- `APP_KEY` identica in `.env` e `.env.testing` — `laravel/serializable-closure` firma le closure con la chiave; chiavi diverse causano `InvalidSignatureException` durante `RefreshDatabase`
- Throttle `api` disabilitato in `testing` via `RouteServiceProvider` — i test che verificano rate limiting non sono possibili senza `withMiddleware` esplicito
- CI aggiornata da `huaxk/postgis-action@v1` (deprecata, immagine Docker rimossa) a service container nativo `postgis/postgis:14-3.3`
- `config:clear` obbligatorio prima dei test se è stato eseguito `php artisan optimize`

### Smistamento Lunigiana (oc:7616)
- Logica hard-coded per ERSU (company_id=1), non generalizzata ad altre company
- `Ticket::isLunigianaZone()` risponde solo "è zona Lunigiana?" — il controllo `enabled` e il logging restano nel controller
- `TicketController::forwardToLunigiana()` centralizza il blocco forwarding usato in `store()`, `v1store()`, `v1update()`
- Lista `zone_id` Lunigiana configurabile in `config/lunigiana.php`, disabilitabile via `LUNIGIANA_FORWARD_ENABLED=false`
- Dipende dalla migrazione `oc_7612` (`add_zone_id_to_tickets_table`) in produzione
