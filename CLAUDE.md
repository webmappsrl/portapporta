# portapporta

## Cos'è questo repo

API Laravel 9 (PHP 8.2) di PortAPPorta: calendari di raccolta rifiuti, segnalazioni (ticket) e push per le app mobile (`webmappsrl/pap`) e pannello Nova (4.29.4) per i gestori. Usa PostgreSQL con PostGIS e Redis. Nessun submodule. **Prod e dev girano con il motore PHP in container Docker** (vedi Conoscenza → runtime). Attenzione alle versioni PHP: in locale e in produzione gira la 8.2 (immagine `wm-phpfpm:8.2.35-fpm`), la CI usa 8.4.

## Regole

- **I test girano solo sul database `pap_test`, mai su `pap`** (un guard in `TestCase::setUp()` abortisce altrimenti).
- **Non lanciare `scripts/deploy_prod.sh` né `scripts/launch_horizon.sh`**: fanno composer/artisan/screen sull'host, non sui container (oc:8843, follow-up aperto). Prima di un deploy leggi [docs/knowledge/8843-runtime-php-docker-prod-dev.md](docs/knowledge/8843-runtime-php-docker-prod-dev.md).
- **Non riabilitare mod_php su Apache del server** e non pubblicare porte dei container fuori da `127.0.0.1` (oc:8843).
- La documentazione del repo è in italiano.

## Comandi

| Cosa | Comando |
|---|---|
| artisan in locale | `docker exec php_PortAPPorta php artisan …` (nomi container da `APP_NAME` in `.env`) |
| artisan in produzione | `docker exec php-prod-pap php artisan …` (dev sul server: `php-dev-pap`) |
| test | `docker exec php_PortAPPorta php artisan test` — setup e prerequisiti in [docs/howto/test-locale.md](docs/howto/test-locale.md) |
| cache sporca (anche route) | `docker exec php_PortAPPorta php artisan optimize:clear` |
| CI | `.github/workflows/laravel-develop-pipe.yml` / `laravel-prod-pipe.yml` (migrate + test su PHP 8.4) |
| build/lint | non configurati nel repo (niente Pint/PHPStan) |

## Convenzioni

- Ticket Orchestrator referenziati come `oc:<ID>`; commit `tipo(oc:<ID>): descrizione`.
- Documentazione: cantieri in `docs/features/<ID>-<slug>/` (`overview.md`, `plan.md`, `notes.md`), conoscenza per argomento in `docs/knowledge/`, procedure in `docs/howto/`.
- Le trappole (comportamenti che fanno sbagliare chi non li conosce) vivono in regole path-scoped sotto `.claude/rules/`, non qui.

## Conoscenza

| Argomento | Cosa copre | Pagina |
|---|---|---|
| Runtime PHP di prod e dev (Docker) | Container, proxy_fcgi, Horizon, scheduler, limiti richieste — oc:8843 | [docs/knowledge/8843-runtime-php-docker-prod-dev.md](docs/knowledge/8843-runtime-php-docker-prod-dev.md) |
| Zona e posizione dei ticket | Derivazione zone_id, geocoding Nominatim, missed_withdraw_date, city in location_address — oc:8099, oc:8113, oc:8054 | [docs/knowledge/ticket-zona-e-posizione.md](docs/knowledge/ticket-zona-e-posizione.md) |
| Instradamento email Lunigiana | Routing esclusivo con fallback, config, follow-up queue — oc:7616, oc:8111 | [docs/knowledge/lunigiana-routing.md](docs/knowledge/lunigiana-routing.md) |
| Calendario per zona: tipo di utenza | `user_type_id` nei ritiri e filtro — oc:8655 | [docs/knowledge/8655-pap-api-calendar-by-zone-tipo-di-utenza-nei-ritiri-e-filtro-user-type-id.md](docs/knowledge/8655-pap-api-calendar-by-zone-tipo-di-utenza-nei-ritiri-e-filtro-user-type-id.md) |
| Campi Nova di Address e Ticket | Select user_type, campi contatto, email ticket — oc:8101, oc:8058 | [docs/knowledge/nova-campi-address-ticket.md](docs/knowledge/nova-campi-address-ticket.md) |
| Pulizia PendingAttachment | Prune dei file Trix e sua attivazione reale — oc:8057 | [docs/knowledge/prune-pending-attachment.md](docs/knowledge/prune-pending-attachment.md) |
| DB di test dedicato | Guard, APP_KEY, throttle, CI — oc:7991 | [docs/knowledge/test-db-dedicato.md](docs/knowledge/test-db-dedicato.md) |
| Risposta ai ticket via mail | Azione Nova `TicketAnswerViaMail`: rich text, immagini inline, allegati | [docs/knowledge/ticket-answer-via-mail.md](docs/knowledge/ticket-answer-via-mail.md) |

## Trappole

Regole path-scoped in `.claude/rules/`, caricate quando si tocca il codice che le riguarda:

- `ticket-zona.md` — SRID nelle query spaziali, ordinamento zone, Nominatim, separatore ` — ` (`app/Models`, `app/Http/Controllers`)
- `nova.md` — company fallback e options/rules in Address (`app/Nova`)
- `test.md` — APP_KEY, throttle, `verifyCalendarItemSchedule`, etichette tradotte (`tests`)
- `scheduler.md` — `model:prune --model` e cron di produzione (`app/Console`)
