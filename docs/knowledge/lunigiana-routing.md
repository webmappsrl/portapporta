# Come viene instradata l'email di una segnalazione Lunigiana

## Come funziona oggi

- `TicketController::sendTicketNotification(Ticket, Company, Mailable)` è **l'unico punto di routing email** dei ticket: i 3 call site (`store`, `v1store`, `v1update`) chiamano solo questo metodo.
- Zona Lunigiana → `sendLunigianaEmails()` invia **solo** a `urp@lunigianaambiente.it` (routing esclusivo); un'eccezione SMTP produce `Log::error` + fallback su `sendToCompany()`; forwarding disabilitato (`LUNIGIANA_FORWARD_ENABLED=false`) o `zone_id` non derivabile → direttamente company (graceful degradation).
- `Ticket::isLunigianaZone()` risponde solo «è zona Lunigiana?» — il controllo `enabled` e il logging restano nel controller.
- Le zone Lunigiana e la company stanno in `config/lunigiana.php`: la company è configurabile via `LUNIGIANA_COMPANY_ID` (default 1 = ERSU). Il file porta un TODO: lista `zone_id` provvisoria al 2026-05-27, da confermare con ERSU.
- Con più destinatari nella lista comma-separated: al primo fallimento SMTP gli altri vengono saltati e scatta il fallback (semantica abort-on-first-failure). In produzione c'è un solo destinatario.
- Dipende dalla migrazione `oc_7612` (`add_zone_id_to_tickets_table`) in produzione.

## Perché così

- **Routing esclusivo con fallback** (oc:8111): la mail va a Lunigiana *oppure* alla company, mai a entrambe; il fallback garantisce che un problema SMTP non perda la segnalazione.
- **`sendLunigianaEmails` è `protected` (non `private`)** (oc:8111): esclusivamente per permettere il mock via Mockery in `testV1StoreLunigianaFailureFallsBackToCompany` — non è una scelta architetturale.

## Come ci siamo arrivati

- **Doppio blocco company + `forwardToLunigiana()`** (oc:7616, superato da oc:8111): inviava alla company e inoltrava a Lunigiana; sostituito dal routing esclusivo. `forwardToLunigiana()` non esiste più nel codice.
- **«Logica hard-coded per ERSU (company_id=1)»** (scritto in oc:7616, non più vero): oggi la company è configurabile via `LUNIGIANA_COMPANY_ID`.

## Follow-up

- `Mail::send()` è sincrono: sotto uno spike di ticket con SMTP Lunigiana lento può esaurire i worker FPM. Richiede refactor verso queue (aperto da oc:8111).
