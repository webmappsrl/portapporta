> Ticket: oc:7991

# Revisione test suite: db di test dedicato per il backend

## Cosa cambia

Il backend smette di girare i test sul database di sviluppo `pap` e usa un database PostgreSQL dedicato `pap_test`. I test attualmente fallenti (45 con HTTP 429, 3 con violazione unique key) tornano tutti verdi. Viene aggiunto un guard che impedisce di eseguire i test sul DB sbagliato. La pipeline CI viene aggiornata di conseguenza.

## Perché

I test backend usano il database di sviluppo `pap`. Questo causa due problemi distinti:

1. **Distruzione dei dati dev**: i test che usano `RefreshDatabase` eseguono drop+migrate su `pap`, cancellando tutti i dati locali di sviluppo.
2. **Fallimenti non deterministici**: i test che usano `DatabaseTransactions` e inseriscono record con ID espliciti (es. `id=1`) falliscono con `SQLSTATE[23505]` perché `pap` contiene già righe con quegli ID. I test V2 falliscono con HTTP 429 perché il rate limiter usa `CACHE_DRIVER=file` che persiste tra i run.

## Requisiti

- [ ] Creare database PostgreSQL `pap_test` nel container Docker locale (`postgres_portapporta`) con estensione PostGIS abilitata
- [ ] Creare `.env.testing` con `DB_DATABASE=pap_test` e `CACHE_DRIVER=array`
- [ ] Disabilitare il middleware `throttle` in ambiente `testing` (via `RouteServiceProvider`)
- [ ] Aggiungere guard in `TestCase::setUp()` che abortisce se `DB_DATABASE !== 'pap_test'`
- [ ] Aggiornare `phpunit.xml`: rimuovere le righe SQLite commentate, allineare alle variabili di `.env.testing`
- [ ] Aggiornare CI (`.github/workflows/laravel-develop-pipe.yml`): creare `pap_test` con PostGIS, migrarlo con `--env=testing`, eseguire `php artisan test`
- [ ] Verificare che tutti i test passino in locale dopo le modifiche
- [ ] Aggiornare `CLAUDE.md` con istruzioni per setup `pap_test` in nuovi ambienti

## Rischi

| Rischio | Mitigazione |
|---|---|
| `pap_test` richiede PostGIS — senza l'estensione i test con geometrie falliscono | Creare `pap_test` con `CREATE EXTENSION postgis` esplicito, verificato in CI |
| Il guard in `TestCase` blocca run su ambienti non configurati | Il messaggio di errore include istruzioni chiare su come creare `pap_test` |
| La CI attuale crea solo `pap` — se dimentichiamo di aggiornare la pipeline i test CI si rompono | Pipeline e config locale vengono aggiornati nello stesso commit |
| Config cacheata (`php artisan optimize`) ignora `.env.testing` in locale | Documentare `php artisan config:clear` come prerequisito, e non cachare config in testing |

## Out of scope

- Test frontend (Angular/Ionic) — repo separato, ticket dedicato se necessario
- Migrazione da `DatabaseTransactions` a `RefreshDatabase` — mantenuto lo stato attuale
- Aggiunta di nuovi test — questa feature si limita a far funzionare quelli esistenti
- Produzione pipeline (`.github/workflows/laravel-prod-pipe.yml`) — non esegue test

## Moduli toccati

| File | Azione |
|---|---|
| `.env.testing` | Creazione — config DB e cache per testing |
| `.env.test` | Eliminazione — file obsoleto con config sbagliata (punta a `pap`) |
| `phpunit.xml` | Modifica — rimozione righe SQLite commentate |
| `tests/TestCase.php` | Modifica — aggiunta guard `DB_DATABASE` con messaggio actionable |
| `app/Providers/RouteServiceProvider.php` | Modifica — disabilita throttle in `testing` |
| `.github/workflows/laravel-develop-pipe.yml` | Modifica — sostituisce action deprecata, crea `pap_test`, migra con `--env=testing` |
| `.github/workflows/laravel-prod-pipe.yml` | Modifica — sostituisce action deprecata `huaxk/postgis-action@v1` |
| `.gitignore` | Modifica — aggiunge `.env.testing` e `.env.test` |
| `CLAUDE.md` | Modifica — istruzioni setup `pap_test` + prerequisito `config:clear` |
