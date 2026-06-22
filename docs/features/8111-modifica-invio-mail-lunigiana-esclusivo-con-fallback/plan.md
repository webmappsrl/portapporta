> Ticket: oc:8111

# Piano — Modifica invio mail Lunigiana: esclusivo con fallback su mail company

## Task 1 — Crea branch

```bash
git checkout -b feature/oc-8111-modifica-invio-mail-lunigiana-esclusivo-con-fallback
```

---

## Task 2 — Aggiungi `sendToCompany()` in `TicketController`

Estrai il loop di invio alla company in un metodo privato dedicato, così può essere richiamato sia dal path normale sia dal fallback senza duplicare codice.

**In `app/Http/Controllers/TicketController.php`**, aggiungi dopo `forwardToLunigiana`:

```php
private function sendToCompany(Company $company, \Illuminate\Mail\Mailable $mailable): void
{
    if ($company->ticket_email) {
        foreach (explode(',', $company->ticket_email) as $recipient) {
            Mail::to(trim($recipient))->send($mailable);
        }
    }
}
```

---

## Task 3 — Aggiungi `sendTicketNotification()` in `TicketController`

Unico punto di routing email. Gestisce tutti i casi: forwarding disabilitato, zone_id mancante, zona non-Lunigiana, zona Lunigiana con fallback.

**In `app/Http/Controllers/TicketController.php`**, aggiungi dopo `sendToCompany`:

```php
// NOTE: non riusare la stessa istanza $mailable tra invii multipli —
// SymfonyMessage è mutabile e può portare stato residuo dal send precedente.
private function sendTicketNotification(Ticket $ticket, Company $company, \Illuminate\Mail\Mailable $mailable): void
{
    if (!config('lunigiana.enabled')) {
        Log::info('Lunigiana forwarding disabled, sending to company', ['ticket_id' => $ticket->id]);
        $this->sendToCompany($company, $mailable);
        return;
    }

    if (!$ticket->zone_id && $company->id === config('lunigiana.company_id')) {
        Log::warning('ERSU ticket zone_id derivation failed, falling back to company email', ['ticket_id' => $ticket->id]);
        $this->sendToCompany($company, $mailable);
        return;
    }

    if (!$ticket->isLunigianaZone()) {
        $this->sendToCompany($company, $mailable);
        return;
    }

    $failed = false;
    foreach (explode(',', config('lunigiana.email')) as $recipient) {
        try {
            Log::info('Sending Lunigiana email to ' . trim($recipient), ['ticket_id' => $ticket->id]);
            Mail::to(trim($recipient))->send($mailable);
        } catch (\Exception $e) {
            $failed = true;
            Log::error('Lunigiana forward failed', [
                'ticket_id' => $ticket->id,
                'recipient' => $recipient,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    if ($failed) {
        Log::warning('Lunigiana forward failed, falling back to company email', ['ticket_id' => $ticket->id]);
        $this->sendToCompany($company, $mailable);
    }
}
```

---

## Task 4 — Aggiorna `store()`: sostituisci doppio blocco con `sendTicketNotification`

**In `app/Http/Controllers/TicketController.php`**, intorno alla riga 132, sostituisci:

```php
// Send a notification email to company for the newly created ticket
if ($res) {
    $company = Company::find($request->id);
    if ($company->ticket_email) {
        foreach (explode(',', $company->ticket_email) as $recipient) {
            Mail::to($recipient)->send(new TicketCreated($ticket, $company));
        }
    }
    // LUNIGIANA_FORWARD
    $this->forwardToLunigiana($ticket, $company, new TicketCreated($ticket, $company));
}
```

con:

```php
if ($res) {
    $company = Company::find($request->id);
    $this->sendTicketNotification($ticket, $company, new TicketCreated($ticket, $company));
}
```

---

## Task 5 — Aggiorna `v1store()`: sostituisci doppio blocco con `sendTicketNotification`

**In `app/Http/Controllers/TicketController.php`**, intorno alla riga 227, sostituisci:

```php
// Send a notification email to company for the newly created ticket
if ($res) {
    $company = Company::find($request->id);
    if ($company->ticket_email) {
        foreach (explode(',', $company->ticket_email) as $recipient) {
            Mail::to($recipient)->send(new TicketCreated($ticket, $company));
        }
    }
    // LUNIGIANA_FORWARD
    $this->forwardToLunigiana($ticket, $company, new TicketCreated($ticket, $company));
}
```

con:

```php
if ($res) {
    $company = Company::find($request->id);
    $this->sendTicketNotification($ticket, $company, new TicketCreated($ticket, $company));
}
```

---

## Task 6 — Aggiorna `v1update()`: sostituisci doppio blocco con `sendTicketNotification`

**In `app/Http/Controllers/TicketController.php`**, intorno alla riga 323, sostituisci:

```php
if($ticket->status == TicketStatus::Deleted){
    $company = Company::find($ticket->company_id);
    if ($company->ticket_email) {
        foreach (explode(',', $company->ticket_email) as $recipient) {
            Mail::to($recipient)->send(new TicketDeleted($ticket, $company));
        }
    }
    // LUNIGIANA_FORWARD
    $this->forwardToLunigiana($ticket, $company, new TicketDeleted($ticket, $company));
}
```

con:

```php
if ($ticket->status == TicketStatus::Deleted) {
    $company = Company::find($ticket->company_id);
    $this->sendTicketNotification($ticket, $company, new TicketDeleted($ticket, $company));
}
```

---

## Task 7 — Rimuovi `forwardToLunigiana()`

Il metodo è ora sostituito da `sendTicketNotification` + `sendToCompany`. Rimuovilo da `TicketController`.

---

## Task 8 — Aggiungi test in `TicketControllerTest`

I test verificano il comportamento via layer HTTP (`Mail::fake()` + asserzioni sui destinatari). Aggiungere al fondo di `TicketControllerTest.php`.

**Setup comune dei test Lunigiana** (helper nel test o in setUp):
- Creare una company con `id` controllato e `ticket_email` valorizzato
- Usare `Config::set('lunigiana.company_id', $company->id)` per allineare il config alla company di test
- Creare una zona con `zone_id` in `config('lunigiana.zones')` oppure usare `Config::set('lunigiana.zones', [$zone->id])`
- Assegnare il `zone_id` al ticket via il campo diretto (non via derivazione PostGIS)

**Test 1 — Zona Lunigiana: solo mail Lunigiana, non company (`v1store`)**
```
Config::set('lunigiana.enabled', true)
Config::set('lunigiana.company_id', $company->id)
Config::set('lunigiana.email', 'lunigiana@test.it')
Config::set('lunigiana.zones', [$zone->id])
POST /api/v2/c/{company_id}/ticket con zone_id Lunigiana

Mail::assertSent(TicketCreated::class, fn($m) => $m->hasTo('lunigiana@test.it'))
Mail::assertNotSent(TicketCreated::class, fn($m) => $m->hasTo('test@example.com')) // company email
```

**Test 2 — Zona Lunigiana: fallback su company se Lunigiana fallisce (`v1store`)**

Strategia: sovrascrivere il mailer di default con uno che lancia eccezione per l'indirizzo Lunigiana, usando `Mail::shouldSend()` (Laravel 11) oppure impostando un mailer `failmail` nel config di test che lancia sempre eccezione. In alternativa: usare `Event::listen(MessageSending::class, ...)` per intercettare e lanciare prima della consegna solo per l'indirizzo Lunigiana.
```
Config::set('lunigiana.enabled', true)
// ... intercetta invio a lunigiana@test.it e lancia \Exception
POST /api/v2/c/{company_id}/ticket con zone_id Lunigiana

Mail::assertSent(TicketCreated::class, fn($m) => $m->hasTo('test@example.com')) // fallback company
```

**Test 3 — Forwarding disabilitato: mail a company (`v1store`)**
```
Config::set('lunigiana.enabled', false)
POST /api/v2/c/{company_id}/ticket con zone_id Lunigiana

Mail::assertSent(TicketCreated::class, fn($m) => $m->hasTo('test@example.com'))
Mail::assertNotSent(TicketCreated::class, fn($m) => $m->hasTo('lunigiana@test.it'))
```

**Replica Test 1 e Test 3 per `store()`** (endpoint non-v1, stesso comportamento atteso).

---

## Task 9 — Verifica test suite

```bash
docker exec php_portapporta php artisan config:clear
docker exec php_portapporta php artisan test --filter=TicketControllerTest
```

Tutti i test devono passare (nuovi e preesistenti).

---

## Commit

```
feat(oc:8111): invio mail Lunigiana esclusivo con fallback su company
```
