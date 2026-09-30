# Calendario per zona: tipo di utenza nei ritiri

## Come funziona oggi
- `GET /api/v2/c/{id}/calendar/z/{zone_id}` (`CalendarController::V1IndexByZone`) riporta in ogni ritiro `user_type_id`, cioè l'utenza del calendario da cui il ritiro proviene.
- `?user_type_id=` è opzionale e restituisce solo i ritiri di quell'utenza. Senza parametro la risposta è quella di sempre.
- Un `user_type_id` senza calendari nella zona risponde 400 `No calendars found for the specified zone.`. Vale anche per un valore non numerico: `?user_type_id=abc` non viene ignorato, restituisce 400.
- Il campo compare anche in `v1index` (`/api/v2/c/{id}/calendar`, app mobile), perché `createCalendar()` è condiviso. L'app (`webmappsrl/pap`, `CalendarRow`) non valida lo schema e lo ignora.
- Nei test, `verifyCalendarItemSchedule` confronta il singolo ritiro senza `->etc()`: ogni campo aggiunto a un ritiro va aggiunto anche lì, altrimenti i test esistenti falliscono con "Unexpected properties were found in scope".

## Perché così
- **Campo per ritiro invece di raggruppare per utenza** (oc:8655): la struttura della risposta resta la stessa. Il sito ERSU può così distinguere Domestico, Commerciale, Balneare e Artigianale e passare alle API portapporta, spegnendo apiersu.
- **Nessuna validazione nuova sul parametro** (oc:8655): si usa l'errore che esiste già per una zona senza calendari.

## Come ci siamo arrivati
- **Endpoint senza tipo di utenza** (oc:4752, superata): univa i ritiri di tutte le utenze della zona senza indicare da quale calendario venivano, e il sito non riusciva a distinguerli.
