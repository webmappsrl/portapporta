> Ticket: oc:8655

# Notes — [pap] api calendar by zone: tipo di utenza nei ritiri e filtro user_type_id

## Deviazioni dal piano

Due task di `plan.md` sono stati eseguiti diversamente (Task 5 e Task 6), dettagliati sotto. Le deviazioni rispetto alla **descrizione del ticket** sono in "Decisioni".

## Divergenze dal piano, task per task

### Task 5 documentazione

La riga in `CLAUDE.md` punta alla pagina di conoscenza `docs/knowledge/8655-….md` invece che alla cartella `docs/features/8655-…/`, come chiede la versione attuale del workflow (`update-context`): la pagina descrive il comportamento attuale dell'endpoint, la cartella è la cronaca del lavoro.

Il Task 5 ha prodotto anche un file non previsto dal piano né dai "Moduli toccati" dell'overview: la pagina `docs/knowledge/8655-pap-api-calendar-by-zone-tipo-di-utenza-nei-ritiri-e-filtro-user-type-id.md`, primo file della cartella `docs/knowledge/` nel repo. È la pagina a cui rimanda la riga del `CLAUDE.md`.

### Task 6 review-gate commit e PR

- **Un commit invece di tre.** Il piano prevedeva `test(oc:8655)`, `feat(oc:8655)` e `docs(oc:8655)` separati; è stato fatto un solo `feat(oc:8655)` (`feda8dd`) con codice, test, `plan.md`, `notes.md`, pagina di conoscenza e riga del `CLAUDE.md`. Il Task 6 non è stato riletto prima del commit. Conseguenza: un revert del solo codice si porta dietro anche la documentazione.
- **Review formale dopo il commit.** Su richiesta della dev, `wm-review-ticket oc:8655` è stata fatta dopo il commit invece che prima (vedi anche "Decisioni"). Le correzioni di documentazione emerse dalla review sono in un commit `docs(oc:8655)` successivo.

## Bug trovati

- **Ambiente di test assente in locale.** Mancavano `.env.testing` e il DB `pap_test`: tutti i test abortivano sul guard di `tests/TestCase.php`. Creati seguendo il `CLAUDE.md` (solo ambiente locale, niente di committato).
- **`.env.testing` senza `MAIL_MAILER`.** `AuthApiTest::testRegisterSuccess` e `RegisterControllerTest::testRegisterSuccess` tentavano un invio SMTP reale (errore `530 Authentication required`). Aggiunto `MAIL_MAILER=array` al `.env.testing` locale: suite intera verde (261/261). Il template `.env.testing` nel `CLAUDE.md` non lo prevede → vedi Follow-up.

## Decisioni

- **Test esistente modificato, in deroga al ticket.** Il ticket chiedeva di non toccare i test esistenti ma anche che la suite passasse: impossibile insieme, perché `verifyCalendarItemSchedule` verifica il singolo ritiro senza `->etc()`. Aggiunto `->where('user_type_id', $this->userType->id)`. Approvato da Alessandro Peci in call il 29/09/2026 13:30 ([trascrizione](https://docs.google.com/document/d/11d8MwC9dzAcSAsxcB501TyQIXDm7J8Y9XfWktgtZjr4/edit)).
- **Domande emerse nella challenge e tolte dall'overview** (decisione della dev, confermata da Alessandro Peci nella stessa call come fuori dal ticket):
  - calendari della stessa utenza sovrapposti (ritiri doppi in produzione, zona 84, 13/07/2026): problema di dati inseriti dal cliente;
  - la query di `V1IndexByZone` esclude i calendari che iniziano dentro l'intervallo richiesto (ritiri mancanti ai cambi di stagione): lasciata com'è;
  - utenze di `zones.geojson` (pivot `user_type_zone`) non allineate ai calendari: problema di dati.
- **Compatibilità app mobile verificata** nel repo `webmappsrl/pap`: `CalendarRow` non valida lo schema, il campo in più viene ignorato.
- **Stima:** `wm-estimate` proponeva 4,6h (3,69h pianificazione misurata + 0,9h implementazione); la dev ha scelto di lasciare su Orchestrator le 4h già presenti.
- **Commit dell'overview finito per errore su `develop`** (locale, mai pushato): spostato sul branch `feature/oc-8655-calendar-zona-user-type`, `develop` locale riallineato a `origin/develop`. PR #60 aperta con la sola overview, come richiesto allo scrum del 29/09 08:31.
- **Migrazioni locali:** eseguite sul DB di sviluppo `pap` le 4 migrazioni pendenti (companies, tickets, trash_types); non cambiano i dati di calendari e utenze.
- **Tag Orchestrator:** nessun tag associato né creato (candidati non pertinenti; etichetta `calendario` rifiutata).
- **Review formale dopo il commit:** su richiesta della dev, `wm-review-ticket oc:8655` si lancia sulla PR #60 dopo il commit e il push, non prima del commit come prevede il review-gate.
- **Conoscenza:** il `CLAUDE.md` è ancora nella forma vecchia; aggiunta solo la riga in "Feature disponibili" con rimando a `docs/knowledge/8655-….md`, nessun blocco in "Decisioni architetturali".

## Follow-up

- Aggiungere `MAIL_MAILER=array` al template di `.env.testing` nel `CLAUDE.md` (o in `phpunit.xml`), per evitare tentativi di invio SMTP reali dai test di registrazione.
- Eventuale ticket separato per la query di `V1IndexByZone` sui cambi di stagione, se ERSU segnala ritiri mancanti dopo lo spegnimento di apiersu.
- Pulizia dati calendari sovrapposti in Nova (lato cliente ERSU).
