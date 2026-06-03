> Ticket: oc:7991

# Notes — Revisione test suite: db di test dedicato per il backend

## Deviazioni dal piano

**APP_KEY identica tra `.env` e `.env.testing`**
Il piano iniziale prevedeva di generare una APP_KEY separata per `.env.testing`. In esecuzione è emerso che `laravel/serializable-closure` firma le closure con APP_KEY: una chiave diversa causava `InvalidSignatureException` durante i test con `RefreshDatabase`. Soluzione: `.env.testing` usa la stessa APP_KEY di `.env`. La nota è rilevante per nuovi ambienti: lo sviluppatore deve copiare la propria APP_KEY in `.env.testing`.

**`.env.test` rimosso con `git rm` senza aggiunta al `.gitignore`**
Il piano prevedeva di aggiungere `.env.test` al `.gitignore`. L'utente ha preferito rimuoverlo direttamente dal repository (era già tracciato) senza aggiungere regole al `.gitignore`. Il file non esiste più.

## Bug trovati

**Config cacheata ignorava `phpunit.xml`**
La causa reale dei 429 non era solo `CACHE_DRIVER=file` in `.env` ma la presence di `bootstrap/cache/config.php` che sovrastava le variabili di `phpunit.xml`. In produzione, `php artisan optimize` è chiamato dopo i deploy — questo significa che chiunque eseguisse i test localmente dopo un optimize avrebbe avuto la cache attiva. Prerequisito `config:clear` documentato in CLAUDE.md.

## Decisioni

**Throttle disabilitato globalmente in testing via `RouteServiceProvider`**
Alternativa scartata: `withoutMiddleware` per-test sui soli test V2. Scelta la disabilitazione globale perché più semplice e perché nessun test esistente verifica il rate limiting. Conseguenza: non è possibile scrivere test per il rate limiter senza un meccanismo esplicito (es. `withMiddleware` per test dedicati).

**DB guard in `TestCase::setUp()` usa `config('database.connections.X.database')`**
Letto dinamicamente dal connection default invece di `env('DB_DATABASE')` — più robusto perché riflette la configurazione effettiva di runtime, non solo la variabile d'ambiente grezza.

## Follow-up

- Aggiornare `laravel-prod-pipe.yml` è stato incluso in questo ticket per coerenza, ma la prod pipeline non è stata testata end-to-end su GitHub Actions.
- In futuro: valutare `php artisan test --parallel` per ridurre i ~46s di esecuzione locale.
- Il mix `RefreshDatabase` + `DatabaseTransactions` nella suite rimane — nessuna standardizzazione è stata fatta per ora (out of scope). Tenerlo a mente se emergono flakiness per ordine di esecuzione.

## Procedura di rollback

Per tornare allo stato pre-feature:
1. Revert `tests/TestCase.php` — rimuovere il `setUp()` con guard
2. Revert `app/Providers/RouteServiceProvider.php` — rimuovere `if (app()->environment('testing'))`
3. Revert `phpunit.xml` — ripristinare le env vars rimosse
4. Revert entrambe le pipeline CI
5. Comunicare al team: eliminare `pap_test` locale e `.env.testing`
