# Come un ticket arriva ad avere zona e posizione coerenti

## Come funziona oggi

- **Derivazione `zone_id` pre-save** (`TicketController` + `Zone::findByPoint`): se l'app non manda `zone_id` (app vecchie), viene derivato prima del `save()` da `address.zone_id` oppure via PostGIS `ST_Contains` sulla geometry — un solo write al DB. Garantisce il routing Lunigiana anche da app non aggiornate.
- `Zone::findByPoint(string $geometry, int $companyId): ?self` usa raw SQL nel model (`DB::selectOne`): è la convenzione per le query spaziali domain-specific.
- **Correzione posizione da indirizzo** (`_correctLocationFromAddress`, chiamato pre-save in `v1store` e `v1update` dopo l'assegnazione di `geometry` e prima della derivazione di zona): forward geocoding Nominatim con campi strutturati (`street`, `city`); se la zona del testo ≠ zona delle coordinate, sovrascrive `geometry` e `zone_id`. Non tocca `store()` (lì l'indirizzo nasce da reverse geocoding, non editabile). Guard conditions tutte fail-open (kill switch `ADDRESS_DISCREPANCY_CHECK_ENABLED`, `address_id` presente, `city` assente, `geometry` null). Un 429 o risposta vuota da Nominatim è fail-open silenzioso. TODO nel codice: rimuovere quando il fix frontend sarà live.
- **Validazione `missed_withdraw_date`** in `v1store` e `v1update` (in update solo se il campo è nel payload); fail-open se `zone_id` non è disponibile. Usa `CalendarController::isCollectionInProgress(int $zoneId): bool` — metodo **public** chiamato via `app(CalendarController::class)`; il blocco si attiva solo se `$request->exists('zone_id')` (intenzionale).
- **`city` in `location_address`**: per ticket senza `address_id` né `zone_id`, la city è appesa con separatore ` — ` (spazio + em dash + spazio); Nova e il template email la riestraggono con `explode(' — ', …, 2)`. Nessuna colonna dedicata.
- `filterExcludeInProgress` logga un `warning` per `stop_time` non parsabile, senza cambio di comportamento.

## Perché così

- **`ST_SetSRID(?::geometry, 4326)` obbligatorio in `findByPoint`** (oc:8099): la geometry pre-save ha SRID=0, quella salvata 4326 — senza il force, `ST_Contains` ritorna `false` in silenzio.
- **`ORDER BY ST_Area(geometry::geometry) ASC`** (oc:8099): le zone ERSU si sovrappongono (27 coppie contate durante oc:8099) — si prende la più specifica, in modo deterministico.
- **`Http` facade invece di `curlRequest()`** (oc:8113): `CurlServiceProvider` usa `User-Agent: PostmanRuntime/7.29.2`, che Nominatim blocca; l'header `portapporta (+https://portapporta.webmapp.it)` rispetta la usage policy. `ConnectionException` va catchata esplicitamente o il fail-open diventa un 500.
- **`ticket_id` null nei log di `v1store`** (oc:8113): il save non è ancora avvenuto — atteso, non un bug.
- **Fail-open ovunque** (oc:8054, oc:8099, oc:8113): un ticket si accetta sempre; la coerenza di zona è best-effort.

## Come ci siamo arrivati

- Ticket: oc:8099 (fallback zone_id), oc:8113 (ricalcolo da discrepanza indirizzo), oc:8054 (bloccanti 3-4 di oc:7609: validazione finestra temporale e city nel location_address). I cantieri completi sono in `docs/features/`.
