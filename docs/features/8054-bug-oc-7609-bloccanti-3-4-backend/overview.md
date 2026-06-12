> Ticket: oc:8054

# [ersu] Bug oc:7609 — Bloccanti 3 e 4 (backend)

## Cosa cambia

- **Bloccante 3** — `v1store` e `v1update` in `TicketController` validano server-side `missed_withdraw_date`: se la data è oggi e il giro di raccolta è ancora in corso per la zona indicata, la richiesta viene rifiutata con HTTP 400.
- **Bloccante 4** — `filterExcludeInProgress` in `CalendarController` aggiunge un log `warning` quando il parsing di `stop_time` fallisce, invece di saltare silenziosamente l'item senza traccia.
- **Bug city/Comune** — i ticket con indirizzo custom (`address_id: null`, `zone_id: null`) non mostrano il comune né in Nova né nella mail. Si aggiunge colonna `city` alla tabella `tickets`, si popola dai metodi store, e si usa come fallback "Comune" solo quando né `zone` né `address` sono presenti.

## Perché

**Bloccante 3:** la regola "segnalazione mancato ritiro solo a fine giro" esiste esclusivamente nell'app mobile. App non aggiornate, calendari in cache o richieste API dirette aggirano il controllo e possono creare ticket `report` con `missed_withdraw_date == oggi` mentre il giro è ancora in corso. L'enforcement lato server è l'unico presidio affidabile.

**Bloccante 4:** se un CalendarItem ha `stop_time` vuoto o malformato, il filtro `exclude_in_progress` viene disattivato silenziosamente per quel giorno — nessuna traccia nel log, nessun alert. Il dato corrotto è invisibile finché non emerge un bug di comportamento.

**Bug city/Comune:** quando l'utente seleziona un indirizzo custom (non registrato), l'app invia `address_id: null` ma manda `city`, `address`, `house_number`. Il server salva `address` e `house_number` in `location_address` ma scarta `city`. Nova e la mail non hanno né `zone` né `address` FK → nessun "Comune" visibile. La colonna `city` sulla tabella `tickets` risolve la persistenza; il fallback in Nova/template risolve la visualizzazione.

## Requisiti

- [ ] `v1store`: se `ticket_type == 'report'` E `missed_withdraw_date == today` E `zone_id` è presente nella request, query sui `CalendarItem` attivi per quella zona nel giorno corrente. Se almeno un item ha `stop_time` ancora nel futuro, restituire `sendError(...)` HTTP 400 con messaggio `"Il giro di raccolta è ancora in corso, riprova più tardi."`.
- [ ] `v1update`: stessa logica; usare `zone_id` dalla request se presente, altrimenti `$ticket->zone_id`. Fail open se nessun `zone_id` disponibile.
- [ ] Fail open esplicito in entrambi i metodi quando `zone_id` non è disponibile (ticket accettato, nessun errore).
- [ ] La logica "giro in corso?" viene estratta in un metodo `isCollectionInProgress(int $zoneId): bool` su `CalendarController`, chiamato da `TicketController` sia in `v1store` che in `v1update`.
- [ ] In `v1update` il check scatta **solo** se `missed_withdraw_date` è presente nel payload della request (`$request->exists('missed_withdraw_date')`), non sul valore già salvato nel ticket.
- [ ] `filterExcludeInProgress`: nel blocco `catch (\Exception $e)` aggiungere `$this->logger->warning(...)` con il valore di `stop_time` che ha causato il fallimento e il giorno corrente.
- [ ] Nuovi test in `TicketControllerTest` per bloccante 3: caso bloccato (giro in corso + zone_id), caso permesso (giro finito + zone_id), caso fail-open (zone_id assente).
- [ ] Nuovo test in `CalendarControllerTest` per bloccante 4: stop_time malformato non genera eccezione e non esclude il giorno.
- [ ] Migrazione `add_city_to_tickets_table`: colonna `city` nullable string su `tickets`.
- [ ] `store`, `v1store`, `v1update`: salvare `$request->city` in `$ticket->city` se presente nel payload.
- [ ] `app/Models/Ticket.php`: aggiungere `city` a `$fillable`.
- [ ] `app/Nova/Ticket.php`: nel ramo `else` (nessun `address` né `zone`), aggiungere campo "Comune" da `$this->city` se valorizzato.
- [ ] `resources/views/emails/tickets/created.blade.php`: quando `$zone` è null, usare `$ticket->city` per la riga "Comune".

## Rischi

- **Query aggiuntiva per ogni `v1store` report con `missed_withdraw_date`** — la validazione esegue un join su `calendars` → `calendar_items`. Mitigazione: il volume di ticket `report` giornalieri è basso; nessun indice aggiuntivo necessario per i volumi attuali.
- **`zone_id` non verificato appartenga alla company** — un client potrebbe passare un `zone_id` di un'altra company per bypassare la regola. Mitigazione: fuori scope per questo fix; il rischio esisteva già per il campo `zone_id` in generale.
- **Dipendenza da `stop_time` nel DB** — se tutti gli item di oggi hanno `stop_time` nullo o malformato, la validazione fallisce open (nessuna esclusione). Comportamento documentato e accettato.

## Prerequisiti di deploy

- Migrazione `oc_7612` (`add_zone_id_to_tickets_table`) deve essere eseguita prima del deploy di questo fix. Senza di essa `zone_id` non esiste sulla tabella `tickets` e le query esplodono con errore SQL.

## Out of scope

- Bloccanti 1 e 2 (frontend PAP — `report-ticket.component.ts`)
- Cleanup non bloccanti: typo `enableExludeInProgress`, duplicazione in `CompanyController`, `Carbon::today()->copy()` nel loop, N+1 in `v1index`

## Moduli toccati

| File | Tipo modifica |
|------|--------------|
| `app/Http/Controllers/TicketController.php` | Aggiunta validazione `missed_withdraw_date` in `v1store` e `v1update` + metodo privato helper |
| `app/Http/Controllers/CalendarController.php` | Aggiunta `warning` log nel catch di `filterExcludeInProgress` + nuovo metodo `isCollectionInProgress(int $zoneId): bool` |
| `tests/Feature/V2/TicketControllerTest.php` | Nuovi test per bloccante 3 |
| `tests/Feature/V2/CalendarControllerTest.php` | Nuovo test per bloccante 4 |
