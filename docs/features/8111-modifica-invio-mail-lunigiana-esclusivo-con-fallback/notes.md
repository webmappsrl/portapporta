> Ticket: oc:8111

# Notes — Modifica invio mail Lunigiana: esclusivo con fallback su mail company

## Deviazioni dal piano

### per-recipient try/catch → protected method + try/catch esterno singolo

Il piano (Task 3) prevedeva un loop `foreach` con try/catch **per-recipient** dentro `sendTicketNotification`, che tentava tutti i destinatari Lunigiana prima di decidere il fallback. L'implementazione ha estratto il loop in `sendLunigianaEmails()` (protected) con un singolo try/catch esterno in `sendTicketNotification`.

**Motivazione:** `sendLunigianaEmails` doveva essere `protected` (non `private`) per permettere il mock via Mockery in `testV1StoreLunigianaFailureFallsBackToCompany`. Con il try/catch interno per-recipient, mockare l'intera funzione e farla lanciare eccezione non era possibile in modo pulito.

**Impatto pratico:** In produzione c'è un solo destinatario Lunigiana (`urp@lunigianaambiente.it`), quindi il comportamento è identico. Con una lista comma-separated di più destinatari, la semantica diverge: al primo SMTP failure gli altri destinatari vengono saltati e scatta il fallback. Questa semantica è conforme al requisito ("se anche un solo destinatario fallisce → fallback") ma non tenta gli altri destinatari prima del fallback.

## Decisioni

- `sendLunigianaEmails` è `protected` per ragioni di testabilità (mock Mockery), non per design architetturale. `sendToCompany` resta `private`. L'asimmetria è intenzionale e documentata qui.
- Il Mailable viene istanziato dal call site e passato già costruito. Il commento nel codice avverte di non riusare la stessa istanza — rischio accettato e mitigato con documentazione, non risolto strutturalmente (out of scope).

## Follow-up

- **SMTP asincrono (priorità bassa):** tutti i `Mail::send()` nel controller sono sincroni (blocking). Sotto carico elevato con SMTP Lunigiana lento, i worker PHP-FPM possono esaurirsi prima del fallback. Il fix richiede refactor verso queue (es. `Mail::to()->queue()`) — out of scope per oc:8111.
- **Magic strings nei test:** `'lunigiana@test.it'` e `'test@example.com'` sono hardcoded nelle asserzioni `hasTo()`. Se `createCompany()` in `FeatureTestV2UtilsFunctions` cambia la `ticket_email` di default, le asserzioni si rompono. Da estrarre in costanti in un prossimo refactor dei test.

## Procedura di rollback

In caso di regressione in produzione dopo il deploy:

1. Ripristinare nel call site `store()` (~riga 132) il blocco:
   ```php
   if ($company->ticket_email) {
       foreach (explode(',', $company->ticket_email) as $recipient) {
           Mail::to($recipient)->send(new TicketCreated($ticket, $company));
       }
   }
   $this->forwardToLunigiana($ticket, $company, new TicketCreated($ticket, $company));
   ```
2. Ripetere per `v1store()` e `v1update()`.
3. Ripristinare il metodo `forwardToLunigiana()` (vedere commit precedente).
4. Rimuovere `sendTicketNotification()`, `sendLunigianaEmails()`, `sendToCompany()`.

Il kill switch via env (`LUNIGIANA_FORWARD_ENABLED=false`) continua a funzionare anche con oc:8111 attivo: invia alla company come prima.
