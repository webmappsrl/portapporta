> Ticket: oc:8655

# [pap] api calendar by zone: tipo di utenza nei ritiri e filtro user_type_id

## Cosa cambia

L'endpoint `GET /api/v2/c/{id}/calendar/z/{zone_id}` (route `routes/api.php:171` → `CalendarController::V1IndexByZone`):

1. riporta in ogni ritiro il campo `user_type_id`, cioè l'utenza del calendario da cui il ritiro proviene;
2. accetta il parametro opzionale `?user_type_id=` per restituire solo i ritiri di quell'utenza.

Senza il parametro, la risposta è identica a oggi, con in più `user_type_id` in ogni ritiro. Esempio di ritiro:

```json
"calendar": {
  "2026-09-28": [
    { "trash_types": [...], "frequency": "weekly", "start_time": "07:00", "stop_time": "13:00", "user_type_id": 5 }
  ]
}
```

Estende oc:4752 ("[pap] api calendar by zone", PR #44, commit `6c701d1`), che ha creato l'endpoint senza il tipo di utenza. I numeri di riga di questo documento si riferiscono al commit `22b847c` di `develop`.

## Perché

Il sito di ERSU mostra i calendari divisi per utenza (Domestico, Commerciale, Balneare, Artigianale). Oggi l'endpoint unisce i ritiri di tutte le utenze della zona senza indicare a quale appartengono: per la zona 84 (Forte dei Marmi - Porta a Porta) il 28/09 ci sono quattro ritiri che il sito non riesce a distinguere.

Chiamata di produzione di riferimento (pubblica, senza autenticazione; verificata il 29/09/2026: `success: true`, 4 ritiri il 28/09):
<https://portapporta.webmapp.it/api/v2/c/1/calendar/z/84?start_date=2026-09-28&stop_date=2026-10-04> Con questo dato il cliente passa tutto il sito alle API portapporta (collegando le zone con `import_id`, già presente) e può spegnere apiersu.

## Requisiti

- [ ] In `createCalendar()` ogni ritiro contiene `user_type_id` = `$calendar->user_type_id` (colonna già esistente, NOT NULL, stesso tipo di id di `availableUserTypes` in `zones.geojson`).
- [ ] In `V1IndexByZone` la query dei calendari filtra per `user_type_id` quando il parametro è presente (`$request->filled('user_type_id')`, cast `(int)`). Il resto della query (righe 205–210, compreso `whereDate('start_date', ...)`) resta identico.
- [ ] `user_type_id` senza calendari nella zona (inesistente, di un'altra zona, non numerico → cast a 0): HTTP 400 `{"success": false, "message": "No calendars found for the specified zone."}`, cioè il comportamento già esistente. Nessuna validazione nuova.
- [ ] Il campo compare anche in `v1index` (`/api/v2/c/{id}/calendar`, app mobile), perché `createCalendar()` è condiviso: è voluto secondo il ticket.
- [ ] 3 test nuovi in `tests/Feature/V2/CalendarControllerTest.php`, sezione `// Tests for V1IndexByZone Function`:
  - `testV1IndexByZoneItemsHaveUserTypeId`
  - `testV1IndexByZoneFilterByUserType`
  - `testV1IndexByZoneFilterByUnknownUserTypeReturnsError`
- [ ] Riga per oc:8655 nella tabella "Feature disponibili" del `CLAUDE.md` (moduli: `app/Http/Controllers/CalendarController.php`, `tests/Feature/V2/CalendarControllerTest.php`).
- [ ] PR verso `develop` con il link al ticket oc:8655.

Branch (`feature/oc-8655-calendar-zona-user-type`, da `develop` ≥ `22b847c`), dati di setup dei test e comandi di esecuzione sono dettagliati in `plan.md`.

## Rischi

- **Campo nuovo anche nella risposta dell'app mobile** (`v1index`). ✅ Verificato nel repo dell'app (`webmappsrl/pap`): i ritiri sono tipizzati con `CalendarRow` (`projects/pap/src/app/features/calendar/calendar.model.ts:4-8`, solo `start_time`, `stop_time`, `trash_types`) e salvati nello store NgRx senza trasformazioni (`calendar.reducer.ts:19-22`); nessuna validazione di schema, nessuna iterazione sulle proprietà del ritiro, nessuna persistenza locale. Il campo in più viene ignorato.
- **`user_type_id` non castato nel model** (`Calendar::$casts` ha solo le date). Con PostgreSQL PDO arriva come intero; se servisse garantirlo si può castare nella riga aggiunta.

## Domande aperte per il revisore

1. **Test esistenti: vanno modificati — ✅ approvata da Alessandro Peci in call il 29/09/2026 alle 13:30** ([trascrizione](https://docs.google.com/document/d/11d8MwC9dzAcSAsxcB501TyQIXDm7J8Y9XfWktgtZjr4/edit): «modifica il test ovviamente in modo che […] ci sia anche il controllo sul tipo nuovo e che funzionino quelli vecchi»). Il ticket chiede di non modificare i test esistenti e allo stesso tempo che l'intera suite passi: con il nuovo campo le due cose non possono valere insieme, perché `verifyCalendarItemSchedule` controlla il singolo ritiro senza `->etc()` e 3 test falliscono con "Unexpected properties were found in scope". Per far passare la suite il test va modificato, anche se il ticket dice di no: si aggiunge `->where('user_type_id', $this->userType->id)` in `verifyCalendarItemSchedule`, così il test resta rigoroso e verifica anche il nuovo campo (compreso `v1index`, l'endpoint dell'app). Deviazione dal ticket approvata.

## Out of scope

- Qualsiasi altra modifica a `CalendarController.php`: `index()` (inclusa la riga uguale a `:82`), `v1index()`, `getStartDate()`, `getStopDate()`, `filterExcludeInProgress()`, `isCollectionInProgress()`, logica `biweekly`, filtro sulle date, gestione errori.
- Nuove validazioni dei parametri o messaggi di errore nuovi.
- Modifiche a `routes/api.php`, migrazioni, nuovi endpoint.
- `zone.import_id`: già presente nella risposta, nessuna modifica.

## Moduli toccati

Tutto nel repo principale `portapporta` (nessun submodule):

- `app/Http/Controllers/CalendarController.php` — 2 righe (`createCalendar()` dopo `:367`, `V1IndexByZone` dopo `:207`)
- `tests/Feature/V2/CalendarControllerTest.php` — 3 test nuovi + una riga in `verifyCalendarItemSchedule` (vedi domanda aperta 1)
- `CLAUDE.md` — riga nella tabella "Feature disponibili"
- `docs/knowledge/8655-pap-api-calendar-by-zone-tipo-di-utenza-nei-ritiri-e-filtro-user-type-id.md` — pagina di conoscenza nuova, a cui rimanda la riga del `CLAUDE.md` (aggiunta dopo l'approvazione: vedi `notes.md`, Task 5)
