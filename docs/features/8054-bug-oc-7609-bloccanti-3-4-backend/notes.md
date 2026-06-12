> Ticket: oc:8054

# Notes — [ersu] Bug oc:7609 — Bloccanti 3 e 4 (backend)

## Deviazioni dal piano

- **Test bloccante 4** — impossibile usare `stop_time = 'invalid-time'`: PostgreSQL rifiuta valori non validi per colonne `time`. Usato `'0:00'` (→ midnight dopo str_replace), rinominato il test in `testV1IndexExcludeInProgressWithMidnightStopTimeKeepsToday`. Il comportamento osservabile (giorno non escluso) è identico.
- **Bug city/Comune aggiunto in corso** — emerso durante la fase di analisi delle vecchie app. Risolto senza migrazione: `city` viene appesa a `location_address` con separatore ` — ` e parsata out in Nova e nel template email.
- **`v1update` location_address era già in formato errato** — il metodo metteva `city` prima di `address` separato da `, ` (invece di ` — ` alla fine). Allineato al formato di `v1store`.
- **`CalendarFactory` non include `user_type_id`** — colonna NOT NULL: aggiunto `$this->createUserType()` nell'helper di test `createCalendarWithStopTime`.

## Decisioni

- `isCollectionInProgress` è un metodo `public` su `CalendarController` (non static): chiamato da `TicketController` via `app(CalendarController::class)`. Logger inizializzato con `??` per coprire il caso in cui venga chiamato fuori dal ciclo normale.
- Fail-open esplicito in entrambi i metodi store/update: se `zone_id` non è disponibile, il ticket viene accettato senza validazione.
- Il separatore ` — ` (spazio + em dash + spazio) per city in `location_address` è sufficientemente raro da non causare falsi positivi nel parsing via `explode(' — ', ..., 2)`.

## Follow-up

- Bloccanti 1 e 2 (frontend PAP) rimangono aperti nel ticket oc:8054.
- Cleanup non bloccanti (typo `enableExludeInProgress`, N+1 in v1index, ecc.) non affrontati in questo ciclo.
- La colonna `calendars.user_type_id` è NOT NULL ma `CalendarFactory` non la include come default: potrebbe causare altri fallimenti in test futuri che usano `Calendar::factory()` senza specificarla.
