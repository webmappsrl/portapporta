# Come gira PHP su prod e dev (runtime Docker)

## Come funziona oggi

- Sul server `pap` (116.203.127.252) **prod** (`/var/www/html/portapporta`) e **dev** (`/var/www/html/dev.portapporta`) girano col **motore PHP in container Docker** (immagine da `docker/configs/phpfpm/Dockerfile`, orchestrazione e porte in `docker/prod/compose.yml` + `fpm-*.conf`: lì si leggono servizi e listen).
- **Apache resta sull'host** e inoltra i `.php` ai container via `proxy_fcgi`; **mod_php è disabilitato e non va riabilitato**. PostgreSQL 12, Redis e Apache NON sono in container.
- **Artisan in produzione si lancia via container**: `docker exec php-prod-pap php artisan …` (il PHP CLI dell'host è vecchio e non serve più il web). In locale il container si chiama `php_PortAPPorta`.
- **Horizon** gira nei container (sopravvive ai reboot — niente più `screen`). **Lo scheduler** gira via `/etc/cron.d/oc8843-pap-scheduler` (`docker exec`, ogni minuto, solo prod).
- **Limiti richieste**: Apache risponde 413 oltre 16MB (guard `mod_rewrite` sul `Content-Length`, perché su Apache 2.4.41 `LimitRequestBody` **non si applica alle richieste proxate** — fix solo dalla 2.4.53); i valori FPM stanno in `docker/configs/phpfpm/php.ini` e sono tarati sui dati reali (ultimi 300 ticket con foto: max 7,7MB in transito base64).
- ⚠️ `scripts/deploy_prod.sh` e `scripts/launch_horizon.sh` **non sono ancora adeguati al container** (fanno composer/artisan/screen sull'host): da sistemare prima del prossimo deploy — vedi follow-up in `docs/features/8843-messa-in-sicurezza-php2shell-shard-portapporta/notes.md`.

## Perché così

- **Container invece di upgrade OS** (oc:8843): per Ubuntu 20.04 non esiste un PHP patchato per PHP2Shell (il PPA ondrej per focal è stato svuotato a giugno 2025), e l'upgrade in place era rischioso con PostgreSQL 12 locale (jammy non lo distribuisce) e apt stratificato. Il container chiude la CVE con l'immagine ufficiale, dà rollback in minuti e rende banale la futura migrazione di macchina (oc:8835).
- **Porte solo loopback, zero `ports:` in produzione** (oc:8843): la compromissione di maphub (oc:8842) è entrata dalla porta FPM 9000 pubblicata su 0.0.0.0 — l'host non ha firewall.
- **PHP 8.2.35 e non 8.3+** (oc:8843): Nova è pinnata a 4.29.4, rilasciata prima di PHP 8.3 e mai attestata su di esso. Il salto richiede prima l'upgrade di Nova (e PHP 8.2 va in EOL il 31/12/2026).

## Come ci siamo arrivati

- **mod_php da PPA ondrej** (fino al 22/10/2026, superato da oc:8843): abbandonato perché il PPA per focal è morto e quella strada non era più patchabile.
- **Upgrade OS in place 20.04→22.04** (valutato e scartato in oc:8843): PostgreSQL 12 locale (che jammy non distribuisce) e apt stratificato lo rendevano l'opzione più rischiosa; l'OS resta comunque da aggiornare (oc:8835). Chi riproponesse l'upgrade in place deve prima risolvere la migrazione del cluster Postgres.
