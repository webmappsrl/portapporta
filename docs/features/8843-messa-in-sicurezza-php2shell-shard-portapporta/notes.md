> Ticket: oc:8843

# Notes — Messa in sicurezza PHP2Shell (CVE-2026-109618/109619) sullo shard portapporta

## Divergenze dal piano, task per task

### Task 4 — 503 statico
Applicato editando i 5 vhost con blocchi marcati `oc8843-maintenance` (incluso `000-default`, che a mod_php spento avrebbe servito il sorgente di `/var/www/html/index.php`) invece di una conf globale; abilitato `mod_headers` (assente) per il `Retry-After`. In `000-default` i `.php` restano vietati (`Require all denied`) anche a fine lavoro.

### Task 7 — Avvio container
Gli Horizon host sono stati fermati **prima** del `compose up` (il piano li fermava al Task 9): due Horizon sulla stessa coda Redis avrebbero processato in doppio.

### Task 8 — LimitRequestBody inefficace
Su Apache 2.4.41 `LimitRequestBody` non si applica alle richieste proxate a FPM (fix in 2.4.53 — trappola già nota da oc:8835). Sostituito con guard `mod_rewrite` sul `Content-Length` (>16MB → 413), verificato con POST da 20MB→413 e 5MB→passa. Aggiunto anche `expose_php=off` nel `php.ini` del repo e `Header always unset X-Powered-By` nei vhost (l'immagine base lo esporrebbe).

### Task 9 — Scheduler mai esistito
`schedule:run` non era configurato da nessuna parte (né crontab, né cron.d, né timer): lo scheduler Laravel **non è mai girato in produzione**. Attivato ex novo su prod via `/etc/cron.d/oc8843-pap-scheduler` (`docker exec php-prod-pap … schedule:run`), dopo verifica che il primo run fosse innocuo: `tickets:update-execute` tocca 0 ticket (nessuno status `execute` nel DB), `model:prune` sui PendingAttachment era l'intento di oc:8057, snapshot Hetzner preso in serata. Su dev non attivato (non lo era mai stato).

## Bug trovati
- **Scheduler assente in produzione** (vedi divergenza Task 9): `model:prune` di oc:8057 e `tickets:update-execute` non sono mai girati fino a stasera.
- **Route cache stantia in locale** (`bootstrap/cache/routes-v7.php` del 23/12/2024): causava 30 falsi rossi nella suite; pulita con `optimize:clear`. Non riguarda il server.
- **Chiave SSH senza commento** in `authorized_keys` di root (ed25519): da ricondurre a una persona nota o rimuovere.
- **Sul server gira anche un `mysqld`** oltre a PostgreSQL 12 e Redis: da censire (oc:8835).

## Decisioni
- 22/10/2026 — Tag associati al ticket in fase di setup: `php2shell` (id 706, strategia condivisa della CVE) e `portapporta` (id 607, tag repository). `26Q4` assegnato automaticamente da Orchestrator alla creazione.
- 22/10/2026 sera — **Cambio di percorso: dockerizzazione del motore PHP al posto dell'upgrade OS.** L'ipotesi iniziale (do-release-upgrade 20.04→22.04 + replica oc:8834) è stata scartata quando la ricognizione ha trovato PostgreSQL 12 locale all'host (jammy non lo distribuisce: migrazione cluster forzata) e apt stratificato (PHP 5.6→8.3). Il container con `php:8.2.35-fpm` chiude la CVE senza operazioni irreversibili, con rollback in minuti (riabilitare mod_php) e allinea PAP al pattern degli altri shard. L'upgrade OS resta dovuto → oc:8835.
- 22/10/2026 — Requisito esplicito del dev: **nessuna porta container esposta** — compose locale con bind `127.0.0.1` su tutti i mapping, compose di produzione senza `ports:` (network host, FPM su loopback). Motivazione: la compromissione di maphub (oc:8842) è entrata dalla 9000 pubblicata su 0.0.0.0.
- 22/10/2026 — Durante l'intervento il motore PHP viene spento del tutto (mod_php disabilitato) e Apache serve un **503 statico con Retry-After**: richiesta dev di buttare giù le API con errori puliti, senza lasciare il motore vulnerabile a servire il 503 di Laravel.
- 22/10/2026 — Rotazione segreti fuori scope per decisione dev («ignora rotazione al momento»). Riproposta a fine lavoro alla luce dei 200 sugli exploit-pattern (11–12/10): decisione finale del dev «non si fa». La verifica di compromissione non ha trovato evidenze di esecuzione né persistenza; il rischio residuo (esfiltrazione senza tracce, non dimostrabile per principio) è accettato dal dev.
- 22/10/2026 — Taratura limiti upload confermata dal dev sui dati reali degli ultimi 300 ticket con foto (max 5,8MB decodificata ≈ 7,7MB in transito base64; p95 3,4MB; media 1,1MB): guard Apache 16MB a richiesta (413 oltre, via rewrite perché LimitRequestBody non vale sulle richieste proxate su Apache 2.4.41 — trappola nota da oc:8835), `upload_max_filesize 16M`, `post_max_size 32M` (backstop), media-library 10MB invariato.
- 22/10/2026 — Fase estimation saltata per decisione dev («salta la stima»): intervento d'urgenza in corso la sera stessa, nessuna `estimated_hours` scritta sul ticket. Pianificazione misurata fino a quel punto: 0,70 h.

- 22/10/2026 — Su richiesta del dev, eseguito nello stesso branch il piano di riordino di `wm-context-doctor`: CLAUDE.md riscritto nella forma nuova (Cos'è/Regole/Comandi/Convenzioni/Conoscenza/Trappole, 129→51 righe), «Decisioni architetturali» smontata in 5 pagine `docs/knowledge/` nuove (lunigiana-routing, ticket-zona-e-posizione, nova-campi-address-ticket, prune-pending-attachment, test-db-dedicato), trappole in 4 regole path-scoped `.claude/rules/`, `docs/ticket-answer-via-mail.md` spostata in knowledge, nuova `docs/howto/test-locale.md`, corretto il messaggio del guard in `tests/TestCase.php` (nomi container e utente DB inesistenti). Copertura verificata: tutti gli oc del vecchio file restano raggiungibili.

## Follow-up
- **Script di deploy da adeguare al container**: `scripts/deploy_prod.sh` fa `composer install` e artisan sull'host (PHP 8.2.26 CLI, che resta installato ma non serve più il web); `scripts/launch_horizon.sh` rilancia Horizon in `screen` sull'host — ora Horizon vive nei container (`docker restart horizon-prod-pap`). Da sistemare prima del prossimo deploy.
- **Allineamento post-merge**: i container sul server usano il contesto copiato in `/root/oc8843/` — dopo il merge della PR, puntare build e compose al checkout del repo sul server e rimuovere la copia.
- **oc:8835**: aggiungere il server pap al censimento — Ubuntu 20.04 EOL, PostgreSQL 12 locale (jammy non lo distribuisce), mysqld da censire, OpenSSH datato (key exchange non post-quantum), chiave SSH senza commento da attribuire.
- **PHP 8.2 EOL il 31/12/2026**: il passo a 8.3+ richiede prima l'upgrade di Nova (4.29.4 non attestata su 8.3) — e Laravel 9 è senza security fix da feb 2024: upgrade framework da pianificare.
- **Verifica 413 nei log** fra qualche giorno: controllare che il guard 16MB non respinga richieste legittime (i dati dicono di no: max reale in transito 7,7MB).
- La vecchia immagine host mod_php 8.2.26 resta installata ma scollegata (mod_php disabilitato): eventuale pulizia dei pacchetti PHP host in oc:8835.
