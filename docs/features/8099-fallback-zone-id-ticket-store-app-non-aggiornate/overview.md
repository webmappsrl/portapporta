> Ticket: oc:8099

# [ersu] Fallback zone_id in ticket store per app non aggiornate

## Cosa cambia

Dopo il salvataggio di un ticket, se `zone_id` è null (client non lo ha mandato), il sistema lo deriva automaticamente — prima dall'`address.zone_id` se `address_id` è presente, poi tramite query PostGIS `ST_Contains` sulla tabella `zones`. Il `zone_id` derivato viene incluso nel primo `save()` del ticket (nessun secondo write). Questo garantisce che `forwardToLunigiana` riceva sempre un `zone_id` valorizzato quando è possibile derivarlo.

## Perché

Bug introdotto con oc:7616: `forwardToLunigiana` dipende da `$ticket->zone_id` impostato dal client mobile. App non aggiornate non mandano `zone_id` → il forward viene saltato silenziosamente anche per segnalazioni provenienti da zone Lunigiana. Il fix rende la derivazione di `zone_id` responsabilità del backend, non del client.

## Requisiti

- [ ] `Zone::findByPoint(string $geometry, int $companyId): ?self` — metodo statico su `Zone` che:
  - accetta una stringa EWKB hex (formato nativo di `$ticket->geometry`) e un `company_id`
  - usa `ST_SetSRID(?::geometry, 4326)` (non `?::geometry` nudo) per forzare SRID=4326 — la geometria pre-save ha SRID=0 (`ST_GeomFromText` senza argomento), quella post-save ha SRID=4326; senza il force il `ST_Contains` ritorna `false` silenziosamente
  - esegue `ST_Contains(geometry::geometry, ST_SetSRID(?::geometry, 4326))` con query parametrizzata (no string interpolation)
  - filtra per `company_id` per evitare di assegnare zone di altre company
  - in caso di zone sovrapposte ordina per `ST_Area(geometry::geometry) ASC` e prende la più specifica (`LIMIT 1`)
  - restituisce `null` se nessuna zona contiene il punto (fail-open)
- [ ] Derivazione `zone_id` in `TicketController::store()` — prima del `$ticket->save()`, se `zone_id` è null:
  1. se `$ticket->address_id` → `Address::find($ticket->address_id)?->zone_id`
  2. se ancora null e `$ticket->geometry` → `Zone::findByPoint($ticket->geometry, $ticket->company_id)?->id`
- [ ] Stessa derivazione in `TicketController::v1store()`, stessa posizione (pre-save)
- [ ] Stessa derivazione in `TicketController::v1update()` — prima del `$ticket->save()`, se `$ticket->zone_id` è ancora null dopo il processing dei campi della request
- [ ] Il guard in `forwardToLunigiana` (riga 347) rimane ma aggiorna il messaggio di log: non è più "app non aggiornata" ma "derivazione fallita" (nessun address né geometry disponibili, o punto fuori da tutte le zone ERSU)
- [ ] Test Unit: `Zone::findByPoint()` — fixture zone sintetiche in DB, verifica: punto dentro zona, punto fuori, zone sovrapposte (prende la più piccola), company_id errato (null)
- [ ] Test Feature: `POST /api/app/{id}/ticket/store` e `POST /api/v1/app/{id}/ticket/v1store` senza `zone_id` ma con `location` → ticket salvato con `zone_id` valorizzato

## Rischi

- **SRID pre-save vs post-save**: risolto con `ST_SetSRID(?::geometry, 4326)` in `Zone::findByPoint`. Il test Unit deve coprire entrambi i casi (geometria con SRID=0 e con SRID=4326) per garantire che il metodo funzioni sia da `store()`/`v1store()` (pre-save, SRID=0) sia da `v1update()` (da DB, SRID=4326).
- **Address con zone_id null**: il caso 1 può restituire null anche con `address_id` presente — il caso 2 entra in cascata. Questo è il comportamento atteso ma va coperto dai test.
- **Performance**: `ST_Contains` su 40 zone ERSU è trascurabile. Se in futuro il numero di zone cresce significativamente, un indice spaziale GIST su `zones.geometry` sarà necessario. Non richiesto ora.
- **v1update con ticket pre-fix**: ticket creati prima di questo deploy hanno `zone_id = null` in DB. Al primo `v1update` successivo il fallback tenterà la derivazione — se il ticket ha geometry, verrà valorizzato. Se non ha né address né geometry (ticket molto vecchi), rimane null. Nessun rischio di regressione.

## Out of scope

- Applicare il fallback a company diverse da ERSU — la logica di derivazione è universale, ma il forward Lunigiana è solo ERSU; non si introduce logica company-specifica nel fallback stesso
- Backfill dei ticket esistenti con `zone_id = null` — operazione separata se necessaria
- Validazione `isCollectionInProgress` sul `zone_id` derivato — fail-open per design: se il client non ha mandato `zone_id` esplicitamente, il blocco raccolta in corso non si attiva
- Refactor dell'inversione lon/lat tra `store()` e `v1store()` — bug pre-esistente, fuori scope

## Moduli toccati

- `app/Models/Zone.php` — aggiunto `Zone::findByPoint(string $geometry, int $companyId): ?self`
- `app/Http/Controllers/TicketController.php` — logica derivazione `zone_id` in `store()`, `v1store()`, `v1update()`; aggiornamento messaggio log in `forwardToLunigiana`
- `tests/Unit/ZoneFindByPointTest.php` — nuovo file
- `tests/Feature/Api/ApiTicketTest.php` — nuovi test casi zone_id derivato
