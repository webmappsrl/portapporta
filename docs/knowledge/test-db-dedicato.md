# Perché i test girano su un database dedicato

## Come funziona oggi

- La suite gira **solo** sul database `pap_test`, mai su `pap` (sviluppo): un guard in `TestCase::setUp()` abortisce con messaggio esplicito se la connessione punta altrove.
- `.env.testing` (non committato) deve avere la **stessa `APP_KEY` di `.env`** e le credenziali DB del proprio ambiente locale (utente di `.env`, non `root`). La procedura completa è in [docs/howto/test-locale.md](../howto/test-locale.md).
- Il throttle `api` è disabilitato in ambiente `testing` via `RouteServiceProvider` (`Limit::none()`): test di rate limiting solo con `withMiddleware` esplicito.
- In CI i test girano su un service container `postgis/postgis:14-3.3` con utente `root` (lì è corretto: è un Postgres dedicato alla pipeline).

## Perché così

- **DB dedicato con guard** (oc:7991): `RefreshDatabase` cancella e rimigra il database — puntato su `pap` distruggerebbe i dati di sviluppo.
- **`APP_KEY` identica** (oc:7991): `laravel/serializable-closure` firma le closure con la chiave; chiavi diverse causano `InvalidSignatureException` durante `RefreshDatabase`.
- **`config:clear` obbligatorio dopo `php artisan optimize`** (oc:7991): la config cache congelata punta all'ambiente sbagliato. Vale anche per la **route cache**: una `bootstrap/cache/routes-v7.php` stantia fa fallire i test di route recenti con 404 (successo il 22/10/2026 con una cache del dicembre 2024 — `php artisan optimize:clear` risolve, oc:8843).

## Come ci siamo arrivati

- **CI su `huaxk/postgis-action@v1`** (superata da oc:7991): action deprecata con immagine Docker rimossa, sostituita dal service container nativo.
- Cantiere: oc:7991 (revisione test suite).
