> Ticket: oc:8843

# Messa in sicurezza PHP2Shell (CVE-2026-109618/109619) sullo shard portapporta

## Cosa cambia

**Percorso scelto: dockerizzazione del motore PHP** (deciso col dev il 22/10 sera, al posto dell'upgrade OS — vedi notes.md). L'OS resta Ubuntu 20.04: la falla si chiude spostando il motore PHP in container con immagine ufficiale patchata, senza operazioni irreversibili sull'host.

**Server `pap` (116.203.127.252, Hetzner — unico host: prod + dev + PostgreSQL 12 + Redis locali):**
1. **Maintenance mode prima di ogni cosa**: `php artisan down` su prod e dev.
2. Raccolta evidenze fuori macchina (log Apache dal 20/10, pacchetti/processi/cron/utenti/`authorized_keys`).
3. **Motore PHP spento per tutta la finestra**: `a2dismod php8.2` + vhost temporaneo che risponde **503 statico con `Retry-After`** a tutto (API comprese: errore pulito per le app mobili, nessun PHP in esecuzione → RCE irraggiungibile durante il lavoro).
4. Install **Docker CE** dal repo ufficiale (ancora pubblicato per focal, verificato 22/10).
5. Build dell'immagine dal Dockerfile del repo con pin **`php:8.2.35-fpm`**; container per prod (FPM su `127.0.0.1:9000`) e dev (`127.0.0.1:9001`), più container Horizon per ciascuno (`restart: always` → sostituisce la `screen` manuale, sopravvive ai reboot), tutti in `network_mode: host`: nessuna porta pubblicata, Postgres/Redis raggiunti su `127.0.0.1`, **`.env` delle app invariati**. Bind-mount delle webroot agli **stessi path** (`/var/www/html/portapporta`, `/var/www/html/dev.portapporta`).
6. Vhost Apache: da mod_php a **`proxy_fcgi`** (`SetHandler proxy:fcgi://127.0.0.1:900x`) per i 4 vhost Laravel; mod_php resta disabilitato per sempre.
7. Mitigazioni permanenti nel pool FPM del container: `session.upload_progress.enabled=off`, limiti `16M/32M/512M`; su Apache: `expose_php` non applicabile a FPM via header (verifica `X-Powered-By` assente), `LimitRequestBody 16M`.
8. Verifiche (vedi Requisiti), poi `artisan up` e riapertura.

**Repo `webmappsrl/portapporta`:**
- `docker/configs/phpfpm/Dockerfile`: pin `php:8.2.15-fpm` → **`php:8.2.35-fpm`**.
- `compose.yml` (sviluppo locale): **nessuna porta esposta fuori da `127.0.0.1`** — tutti i mapping (FPM 9000, serve 8000, Postgres, Mailpit, Firebase) col bind esplicito su loopback; tag immagine allineato al pin.
- Nuovo compose di produzione (`docker/prod/compose.yml` o equivalente): servizi php+horizon per prod e dev, `network_mode: host`, **zero `ports:`**, restart policy.
- Pool FPM ripulito: `session.upload_progress.enabled=off`, `upload 16M / post 32M / memory 512M` (oggi 1024M su tutto e upload_progress attivo).

**Verifica di compromissione** sulle evidenze raccolte prima della patch, esito documentato nel ticket.

## Perché

PHP2Shell (CVSS 9.2): RCE non autenticata a livello di motore PHP, exploit pubblico, attacchi in rete dal 20/10. Il server espone prod e dev su mod_php 8.2.26. Per Ubuntu 20.04 non esiste alcun pacchetto PHP corretto (PPA ondrej svuotato per focal — ultimo publish 09/06/2025; ESM copre solo php7.4, incompatibile con Laravel 9). Le alternative erano upgrade OS in place (scartata: `do-release-upgrade` irreversibile su host con apt stratificato e **PostgreSQL 12 locale** che jammy non distribuisce) o container: il container chiude la falla con immagine ufficiale indipendente dall'OS, rollback in minuti, e allinea PAP al pattern degli altri shard (procedura condivisa wm-package).

## Requisiti

- [ ] **Maintenance mode prima di ogni cosa** (`artisan down` prod+dev), tolto solo a verifiche concluse
- [ ] Snapshot Hetzner preso prima di ogni modifica (fatto dal dev, 22/10 sera)
- [ ] Evidenze copiate fuori macchina prima di ogni modifica
- [ ] **Motore PHP non raggiungibile durante l'intervento**: mod_php disabilitato + 503 statico con `Retry-After` servito da Apache a tutte le richieste, API comprese
- [ ] PHP **8.2.35** verificato **via SAPI web** (`php -v` dentro i container E versione vista da una richiesta HTTP) su prod e dev
- [ ] **Nessuna porta container esposta**: FPM in ascolto solo su `127.0.0.1`; verifica dall'esterno che 9000/9001 siano chiuse (`nc -z`); `compose.yml` locale con tutti i bind su `127.0.0.1`
- [ ] Parità estensioni PHP: le estensioni usate dall'app presenti nel container (confronto `php -m` host vs container prima dello switch)
- [ ] Horizon running nei container con `restart: always` (niente più `screen`), job di prova processato su prod e dev
- [ ] Scheduler verificato: individuare come gira oggi (`schedule:run` non è nel crontab di root) e raccordarlo al container (`docker exec`)
- [ ] Mitigazioni verificate: `session.upload_progress` off via web; header `X-Powered-By` assente; POST oltre 16M → 413 pulito
- [ ] App funzionanti: home/API prod e dev, Nova raggiungibile, smoke test calendario e creazione ticket; permessi `www-data` corretti sui mount (storage scrivibile)
- [ ] Repo: pin + pulizia pool + compose loopback-only; **suite di test verde in locale sul container nuovo** (DB `pap_test`) prima dello switch sul server
- [ ] Verifica di compromissione eseguita sulle evidenze, esito documentato nel ticket
- [ ] Ticket oc:8843 aggiornato (note dev + risposta cliente)

## Rischi

Dalla challenge del 22/10, con le mitigazioni recepite:

- **Esposizione durante la finestra di intervento** (REALE): il 503 di Laravel passa dal motore vulnerabile → mitigato col punto 3: mod_php off e 503 statico, nessun PHP esegue richieste durante il lavoro.
- **Porta FPM esposta = vettore di compromissione** (REALE, già successo su maphub oc:8842): mitigato con `network_mode: host` + listen `127.0.0.1` + zero `ports:` + verifica esterna a fine lavoro. L'host non ha firewall: la verifica esterna è obbligatoria, non opzionale.
- **Parità mod_php → FPM container** (REALE): SAPI e set di estensioni diversi possono rompere l'app allo switch → mitigato con confronto `php -m` prima dello switch, suite verde in locale sulla stessa immagine, smoke test prima di `artisan up`, e rollback in minuti (riabilitare mod_php nei vhost).
- **La verifica di compromissione non può provare l'assenza di compromissione** (REALE): log pre-20/10 già ruotati, un attaccante con RCE può ripulirli. La rotazione segreti resta comunque fuori scope per decisione dev (22/10); rientra immediata se emergono evidenze. Rischio residuo accettato e documentato.
- **Snapshot come rollback può essere esso stesso inquinato** (REALE): lo snapshot del 22/10 fotografa una macchina esposta dal 20/10 → declassato a recovery di catastrofe; il rollback operativo del percorso Docker è «fermi i container, riabiliti mod_php», che non dipende dallo snapshot.
- **L'host resta Ubuntu 20.04 EOL** (REALE): Apache 2.4.41, OpenSSH e kernel vecchi restano esposti ad altre CVE → dichiarato e rimandato a oc:8835 (upgrade o macchina nuova; con l'app containerizzata la migrazione diventa banale).
- **Scheduler non trovato nel crontab di root** (REALE): se oggi non gira, va scoperto dove sta (altro utente? systemd timer?) prima di raccordarlo — altrimenti `model:prune` e gli altri job notturni muoiono in silenzio.

## Out of scope

- **Upgrade OS / macchina nuova** → oc:8835 (lì anche: migrazione PostgreSQL 12→14, OpenSSH, kernel).
- **Rotazione segreti** (AWS, DB, Redis, SMTP): decisione dev 22/10 — rientra subito se la verifica di compromissione trova evidenze.
- **PHP 8.3+**: richiede prima upgrade Nova (4.29.4 mai attestata su 8.3). Follow-up: PHP 8.2 EOL 31/12/2026.
- **Laravel 9 EOL** (senza security fix da feb 2024): debito noto, da pianificare a parte.
- Pulizia delle versioni PHP stratificate sull'host (5.6–8.3): mod_php va solo disabilitato, la rimozione dei pacchetti non è necessaria per chiudere la falla.

## Moduli toccati

| Dove | Cosa | Destinazione |
|---|---|---|
| `docker/configs/phpfpm/Dockerfile` | pin `php:8.2.35-fpm` | webmappsrl/portapporta |
| `compose.yml` | bind `127.0.0.1` su tutti i mapping, tag immagine | webmappsrl/portapporta |
| `docker/prod/compose.yml` (nuovo) | php+horizon prod e dev, network host, zero ports | webmappsrl/portapporta |
| `docker/configs/phpfpm/www.conf`, `php.ini` | pulizia pool (upload_progress off, 16M/32M/512M) | webmappsrl/portapporta |
| `docs/features/8843-…/` | artefatti | webmappsrl/portapporta |
| Server `pap`: Docker CE, vhost Apache (proxy_fcgi), conf 503 manutenzione | esecuzione | fuori repo (host) |
