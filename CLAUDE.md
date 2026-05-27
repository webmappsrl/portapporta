# CLAUDE.md

## Stack
- Laravel 12, PHP 8.4, PostgreSQL (PostGIS), Redis
- Docker: container PHP `php_portapporta`, DB `postgres_portapporta`
- Comandi artisan vanno lanciati dentro il container: `docker exec php_portapporta php artisan ...`

## Feature disponibili

| Feature | Ticket | Moduli toccati | Note |
|---|---|---|---|
| Smistamento automatico segnalazioni Lunigiana | oc:7616 | `config/lunigiana.php`, `app/Models/Ticket.php`, `app/Http/Controllers/TicketController.php` | Duplica le email ticket verso urp@lunigianaambiente.it per le zone Lunigiana di ERSU |

## Decisioni architetturali

### Smistamento Lunigiana (oc:7616)
- Logica hard-coded per ERSU (company_id=1), non generalizzata ad altre company
- `Ticket::isLunigianaZone()` risponde solo "è zona Lunigiana?" — il controllo `enabled` e il logging restano nel controller
- `TicketController::forwardToLunigiana()` centralizza il blocco forwarding usato in `store()`, `v1store()`, `v1update()`
- Lista `zone_id` Lunigiana configurabile in `config/lunigiana.php`, disabilitabile via `LUNIGIANA_FORWARD_ENABLED=false`
- Dipende dalla migrazione `oc_7612` (`add_zone_id_to_tickets_table`) in produzione
