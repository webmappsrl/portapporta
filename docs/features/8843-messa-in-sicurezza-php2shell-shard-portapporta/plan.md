> Ticket: oc:8843

# Piano — Messa in sicurezza PHP2Shell shard portapporta (percorso Docker)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** chiudere PHP2Shell su prod e dev di portapporta spostando il motore PHP in container `php:8.2.35-fpm`, senza toccare OS, Postgres e `.env`.

**Architettura:** Apache resta sull'host e passa da mod_php a `proxy_fcgi` verso 4 container (`network_mode: host`, FPM su `127.0.0.1:9000` prod / `127.0.0.1:9001` dev, 2 container Horizon con `restart: always`). Durante l'intervento il motore è spento (mod_php off, 503 statico).

**Tech stack:** Docker CE (repo ufficiale, focal), immagine dal `docker/configs/phpfpm/Dockerfile` del repo, Apache 2.4.41 `mod_proxy_fcgi`.

**Spec:** `docs/features/8843-messa-in-sicurezza-php2shell-shard-portapporta/overview.md`

## Vincoli globali

- **Nessun `git commit`/`push`/branch senza approvazione esplicita del dev**: i passi di commit sono istruzioni testuali, eseguite solo al review-gate.
- **Nessuna porta container esposta fuori da `127.0.0.1`** (requisito dev; lezione maphub oc:8842).
- Ordine vincolante sul server: maintenance → evidenze fuori macchina → motore spento → patch.
- `.env` di prod e dev **non si toccano**.
- PHP target **8.2.35** (vincolo Nova 4.29.4); immagine sempre buildato con `--pull`.
- Verifica versione sempre dentro i container/SAPI FPM, mai solo CLI host.
- Rollback operativo: `a2enmod php8.2` + vhost originali + reload (i vhost originali si salvano prima di toccarli).

## Review Focus

1. FPM in ascolto su `0.0.0.0` con `network_mode: host` → la 9000/9001 finisce su internet (host senza firewall): verifica esterna `nc -z` obbligatoria (Task 7, 10).
2. Estensione PHP mancante nel container rispetto a mod_php → Nova/app rotte allo switch: confronto `php -m` prima dello switch (Task 6).
3. UID `www-data` diverso tra host e container → storage non scrivibile: smoke di scrittura (upload/log) prima di riaprire (Task 10).
4. Scheduler morto in silenzio (non è nel crontab di root): va trovato e raccordato al container (Task 9).
5. App mobili davanti al fermo → 503 statico con `Retry-After`, mai connection refused (Task 4).

---

### Task 1 — Repo: pin immagine e pulizia pool FPM

**Files:** Modify: `docker/configs/phpfpm/Dockerfile:1`, `docker/configs/phpfpm/www.conf:541-543`, `docker/configs/phpfpm/php.ini:5-8`, `compose.yml:9`

- [ ] **1.1** `Dockerfile:1`: `FROM php:8.2.15-fpm` → `FROM php:8.2.35-fpm`; `compose.yml:9`: `image: wm-phpfpm:8.2.15-fpm` → `wm-phpfpm:8.2.35-fpm`.
- [ ] **1.2** `www.conf:541-543`: `memory_limit 1024M→512M`, `upload_max_filesize 1024M→16M`, `post_max_size 1024M→32M`; aggiungere sotto: `php_admin_value[session.upload_progress.enabled] = off`.
- [ ] **1.3** `php.ini`: `memory_limit 2048M→512M`, `upload_max_filesize 1024M→16M`, `post_max_size 1024M→32M`; aggiungere `session.upload_progress.enabled = off`.
- [ ] **1.4** Build locale: `docker build --pull -t wm-phpfpm:8.2.35-fpm docker/configs/phpfpm/`
  Atteso: build ok.
- [ ] **1.5** `docker run --rm wm-phpfpm:8.2.35-fpm php -v` → `PHP 8.2.35`; `docker run --rm wm-phpfpm:8.2.35-fpm php-fpm -tt 2>&1 | grep -E "upload_progress|upload_max|post_max|memory_limit"` → valori nuovi, upload_progress off.
- [ ] **1.6** (istruzione per il dev, al review-gate) commit `feat(oc:8843): pin php:8.2.35-fpm e pulizia pool FPM`.

### Task 2 — Repo: compose locale solo loopback

**Files:** Modify: `compose.yml` (blocchi `ports:` dei servizi php, db, mailpit, firebase)

- [ ] **2.1** Ogni mapping diventa `127.0.0.1:HOST:CONTAINER`: `"127.0.0.1:${DOCKER_PHP_PORT}:9000"`, `"127.0.0.1:${DOCKER_SERVE_PORT}:8000"`, `"127.0.0.1:${DOCKER_PSQL_PORT}:5432"`, `"127.0.0.1:${FORWARD_MAILPIT_PORT:-1025}:1025"`, `"127.0.0.1:${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}:8025"`, `"127.0.0.1:${FIREBASE_EMULATOR_PORT:-4000}:4000"`.
- [ ] **2.2** `docker compose -f compose.yml config` con le variabili valorizzate → nessun errore, tutti i bind su 127.0.0.1.
- [ ] **2.3** (istruzione per il dev) commit `feat(oc:8843): compose locale con bind solo su 127.0.0.1`.

### Task 3 — Repo: compose di produzione (zero porte pubblicate)

**Files:** Create: `docker/prod/compose.yml`, `docker/prod/fpm-prod.conf`, `docker/prod/fpm-dev.conf`

**Interfaces — Produces:** 4 servizi: `php-prod` (FPM `127.0.0.1:9000`), `php-dev` (FPM `127.0.0.1:9001`), `horizon-prod`, `horizon-dev`; immagine `wm-phpfpm:8.2.35-fpm`; mount `/var/www/html/portapporta` e `/var/www/html/dev.portapporta` agli stessi path del host.

- [ ] **3.1** `fpm-prod.conf` e `fpm-dev.conf`: copia dell'ultima sezione di override (stile `zz-docker.conf`) con `listen = 127.0.0.1:9000` (prod) / `listen = 127.0.0.1:9001` (dev) — montati su `/usr/local/etc/php-fpm.d/zz-docker.conf` nei rispettivi servizi.
- [ ] **3.2** `docker/prod/compose.yml`: 4 servizi, tutti `image: wm-phpfpm:8.2.35-fpm`, `network_mode: host`, `restart: always`, **nessuna chiave `ports:`**; `php-prod`/`php-dev` con volume webroot + override conf; `horizon-prod`: `working_dir: /var/www/html/portapporta`, `command: php artisan horizon`; `horizon-dev` analogo su dev.portapporta.
- [ ] **3.3** `docker compose -f docker/prod/compose.yml config` → valido, `grep -c "ports:"` → 0.
- [ ] **3.4** (istruzione per il dev) commit `feat(oc:8843): compose di produzione network-host senza porte pubblicate`.

### Task 3b — Locale: suite di test sul container nuovo

**Prerequisito:** variabili `DOCKER_PHP_PORT`, `DOCKER_SERVE_PORT`, `DOCKER_PSQL_PORT` assenti dal `.env` locale: valorizzarle (porte libere, i container maphub occupano le standard) senza committare.

- [ ] **3b.1** `docker compose up -d php db redis`; nel container: creazione DB `pap_test` + `CREATE EXTENSION postgis` se assenti, `php artisan config:clear`, `php artisan migrate --env=testing`.
- [ ] **3b.2** `docker exec php_PortAPPorta php artisan test` → suite verde sul motore 8.2.35.
  Un fallimento qui blocca lo switch in produzione finché non è spiegato.

### Task 4 — Server: maintenance, evidenze, motore spento

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-4--503-statico)

- [ ] **4.1** `php artisan down --secret=<token>` in `/var/www/html/portapporta` e `/var/www/html/dev.portapporta` (token annotato fuori repo).
- [ ] **4.2** Evidenze → `/root/evidenze-8843/` e copia locale via scp (fuori dal repo): tar di `/var/log/apache2/` (rotazioni dal 20/10 incluse), `dpkg -l`, `ps auxww`, `crontab -l` per root/www-data + `/etc/cron.d/`, `/etc/passwd`, `authorized_keys`, `find` dei .php modificati dal 20/10 nelle due webroot.
  Atteso: archivio presente in locale, checksum combaciante.
- [ ] **4.3** Vhost di manutenzione: backup dei 7 file di `sites-enabled` in `/root/oc8843/vhost-backup/`; conf che risponde `503` statico con header `Retry-After: 3600` a ogni richiesta dei 4 vhost Laravel; `a2dismod php8.2`; `apache2ctl configtest` → `Syntax OK`; `systemctl reload apache2`.
- [ ] **4.4** Verifica: `curl -sI https://portapporta.webmapp.it/api/...` → `503` + `Retry-After`, nessun `X-Powered-By`; `ps aux | grep php` → nessun processo PHP da Apache (restano Horizon host, che si spegne al Task 9).

### Task 5 — Server: install Docker CE

- [ ] **5.1** Install dal repo ufficiale Docker per focal (gpg key + apt repo + `docker-ce docker-ce-cli containerd.io docker-compose-plugin`).
- [ ] **5.2** `docker run --rm hello-world` → ok; `docker compose version` → ok.

### Task 6 — Server: build immagine e parità estensioni

- [ ] **6.1** `scp` del contesto (`docker/configs/phpfpm/` + `docker/prod/`) in `/root/oc8843/` (il checkout git del server non si tocca: le modifiche repo arrivano col merge, dopo).
- [ ] **6.2** `docker build --pull -t wm-phpfpm:8.2.35-fpm /root/oc8843/phpfpm/` → ok; `docker run --rm wm-phpfpm:8.2.35-fpm php -v` → `PHP 8.2.35`.
- [ ] **6.3** Parità estensioni: `php -m` host (mod_php) vs `docker run --rm wm-phpfpm:8.2.35-fpm php -m` → ogni modulo usato dall'app presente nel container (attesi almeno: pgsql, pdo_pgsql, gd, intl, zip, redis, imagick, exif, mbstring, curl, xml). Differenze → risolvere prima di proseguire.

### Task 7 — Server: avvio container

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-7--avvio-container)

- [ ] **7.1** `docker compose -f /root/oc8843/prod/compose.yml up -d` → 4 container `running` (gli Horizon possono andare in restart finché Redis è raggiungibile: verificare che siano `running` stabili).
- [ ] **7.2** `ss -ltnp | grep -E ":9000|:9001"` → solo `127.0.0.1`; **da fuori** (macchina locale): `nc -z -w3 116.203.127.252 9000; nc -z -w3 116.203.127.252 9001` → entrambe chiuse.
- [ ] **7.3** `docker exec` su php-prod e php-dev: `php -v` → 8.2.35; `php-fpm -tt` → listen 127.0.0.1:900x, upload_progress off, limiti 16M/32M/512M.

### Task 8 — Server: switch vhost a proxy_fcgi

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-8--limitrequestbody-inefficace)

- [ ] **8.1** `a2enmod proxy proxy_fcgi`; nei 4 vhost Laravel: `<FilesMatch \.php$> SetHandler "proxy:fcgi://127.0.0.1:9000"` (prod e prod-ssl) / `9001` (dev e dev-ssl); `LimitRequestBody 16777216`; rimozione della conf 503 dai vhost; `apache2ctl configtest` → `Syntax OK`; `systemctl reload apache2`. mod_php resta disabilitato.
- [ ] **8.2** Con l'app ancora in maintenance: `curl -sI https://portapporta.webmapp.it/` → `503` **servito da Laravel via FPM** (pagina Laravel, non quella statica), nessun `X-Powered-By`; accesso con `?secret=<token>` → app visibile.
- [ ] **8.3** Smoke con secret: home prod e dev → 200; `/nova/login` → 200; una API in lettura (es. calendario) → 200; scrittura su storage ok (log applicativo aggiornato, nessun errore permessi in `storage/logs`). Errori di permessi → allineare UID (`user:` nel compose o chown mirato) prima di proseguire.

### Task 9 — Server: Horizon e scheduler sul motore nuovo

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-9--scheduler-mai-esistito)

- [ ] **9.1** Horizon host: `php artisan horizon:terminate` in entrambe le app; chiusura screen `horizon_PROD_pap_worker` (e l'equivalente DEV se esiste); `ps aux | grep horizon` → restano solo i processi nei container.
- [ ] **9.2** Job di prova processato su prod e dev via container (`docker exec horizon-prod php artisan horizon:status` → running; dispatch di un job innocuo e verifica elaborazione nei log Horizon).
- [ ] **9.3** Scheduler: individuare dove gira oggi `schedule:run` (crontab root vuoto → cercare in `/etc/cron.d/`, crontab `www-data`, systemd timer). Esito documentato in notes.md; raccordo: voce cron ogni minuto `docker exec php-prod php artisan schedule:run` (e dev se oggi previsto). Verifica: `schedule:run` eseguito senza errori al minuto successivo.
- [ ] **9.4** Nota per il deploy futuro: `scripts/launch_horizon.sh` e `deploy_prod.sh` andranno adeguati al container — registrare in notes.md come follow-up (fuori da stanotte se i deploy sono sospesi fino al merge).

### Task 10 — Server: verifiche finali e riapertura

- [ ] **10.1** `php artisan up` (via `docker exec`) su prod e dev.
- [ ] **10.2** Da fuori: home e una API prod/dev → 200; `curl -sI` → nessun `X-Powered-By`; POST oltre 16M → `413` pulito (worker vivi dopo); `nc -z` 9000/9001 → chiuse; `apache2ctl -M | grep php` → vuoto (mod_php off).
- [ ] **10.3** Monitoraggio breve: log Apache e `storage/logs` senza errori nuovi per ~15 minuti; Horizon running.

### Task 11 — Verifica di compromissione (sulle evidenze del Task 4)

- [ ] **11.1** Log Apache dal 20/10: pattern exploit PHP2Shell (`auto_prepend_file`, `allow_url_include`, `php://input`, `%AD`) → nessun 2xx su pattern di exploit, in tutto lo storico disponibile.
- [ ] **11.2** File .php nuovi/modificati dal 20/10 nelle webroot e in `/tmp`; cron, utenti, `authorized_keys`, processi anomali (dalle evidenze pre-patch).
- [ ] **11.3** Esito documentato in notes.md e nel ticket. **Se emergono evidenze: la rotazione segreti rientra subito in scope** (decisione overview) e si avvisa il dev prima di qualsiasi altra cosa.

### Task 12 — Chiusura documentale

- [ ] **12.1** notes.md completato (deviazioni, scoperte del Task 9.3, follow-up: deploy script, oc:8835, PHP 8.2 EOL + Nova, Laravel 9 EOL).
- [ ] **12.2** Pagina `docs/knowledge/` + CLAUDE.md (fase update-context di wm-plan; correggere anche lo stack dichiarato: Laravel 9 / PHP 8.2, non «Laravel 12, PHP 8.4»).
- [ ] **12.3** (istruzioni per il dev, al review-gate) commit degli artefatti + PR verso `develop`.
