> Ticket: oc:7616

# Plan — Smistamento automatico segnalazioni Lunigiana

## Prerequisiti
- Branch `oc_7612` (migrazione `add_zone_id_to_tickets_table`) deve essere in produzione prima del deploy di questa feature.
- Confermare lista definitiva `zone_id` Lunigiana con ERSU prima del deploy.

---

## Step 1 — Crea `config/lunigiana.php`

**File:** `config/lunigiana.php` (nuovo)

```php
<?php

return [
    'enabled'    => env('LUNIGIANA_FORWARD_ENABLED', true),
    'company_id' => env('LUNIGIANA_COMPANY_ID', 1),
    // TODO: confermare lista definitiva zone_id con ERSU (provvisoria al 2026-05-27)
    'zones'      => [108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123],
    'email'      => env('LUNIGIANA_FORWARD_EMAIL', 'urp@lunigianaambiente.it'),
];
```

**Variabili `.env` opzionali da documentare:**
```
LUNIGIANA_FORWARD_ENABLED=true
LUNIGIANA_FORWARD_EMAIL=urp@lunigianaambiente.it
LUNIGIANA_COMPANY_ID=1
```

Commit: `feat(oc:7616): add lunigiana forwarding config`

---

## Step 2 — Aggiungi `isLunigianaZone()` a `Ticket` model

**File:** `app/Models/Ticket.php`

Aggiungere prima del metodo `authorizedToUpdate()`:

```php
public function isLunigianaZone(): bool
{
    return $this->zone_id !== null && in_array($this->zone_id, config('lunigiana.zones', []));
}
```

Il metodo risponde solo alla domanda "il ticket appartiene a una zona Lunigiana?" — la logica di `enabled` e il logging rimangono nel controller.

---

## Step 3 — Aggiungi import `Log` e metodo `forwardToLunigiana()` a `TicketController`

**File:** `app/Http/Controllers/TicketController.php`

Aggiungere dopo `use Illuminate\Support\Facades\Mail;`:
```php
use Illuminate\Support\Facades\Log;
```

Aggiungere metodo privato prima di `_geometryToLatLon()`:
```php
private function forwardToLunigiana(Ticket $ticket, Company $company, \Illuminate\Mail\Mailable $mailable): void
{
    if (!config('lunigiana.enabled')) {
        return;
    }
    if (!$ticket->zone_id && $company->id === config('lunigiana.company_id')) {
        Log::warning('ERSU ticket has no zone_id, Lunigiana forward skipped', ['ticket_id' => $ticket->id]);
        return;
    }
    if ($ticket->isLunigianaZone()) {
        try {
            Mail::to(config('lunigiana.email'))->send($mailable);
        } catch (\Exception $e) {
            Log::warning('Lunigiana forward failed', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }
    }
}
```

---

## Step 4 — Chiamata forwarding in `store()` e `v1store()` (creazione)

**File:** `app/Http/Controllers/TicketController.php`

Aggiungere dopo il blocco `foreach` email esistente in entrambi i metodi:
```php
// LUNIGIANA_FORWARD
$this->forwardToLunigiana($ticket, $company, new TicketCreated($ticket, $company));
```

Commit: `feat(oc:7616): add Lunigiana email forwarding on ticket creation`

---

## Step 5 — Chiamata forwarding in `v1update()` (cancellazione)

**File:** `app/Http/Controllers/TicketController.php` — dentro `if($ticket->status == TicketStatus::Deleted)`, dopo il blocco `foreach`.

```php
// LUNIGIANA_FORWARD
$this->forwardToLunigiana($ticket, $company, new TicketDeleted($ticket, $company));
```

Commit: `feat(oc:7616): add Lunigiana email forwarding on ticket deletion`

---

## Step 7 — Checklist di testing manuale (staging)

- [ ] Creare ticket con `zone_id` Lunigiana (es. 108) → verificare che arrivino 2 email: `urp@ersu.it` e `urp@lunigianaambiente.it`
- [ ] Creare ticket con `zone_id` non-Lunigiana (es. 84 - Forte dei Marmi) → verificare che arrivi solo 1 email: `urp@ersu.it`
- [ ] Creare ticket ERSU senza `zone_id` → verificare `Log::warning` in `laravel.log`
- [ ] Cancellare ticket con `zone_id` Lunigiana → verificare che arrivino 2 email `TicketDeleted`
- [ ] Impostare `LUNIGIANA_FORWARD_ENABLED=false` → verificare che nessuna email secondaria venga inviata
- [ ] Simulare errore SMTP sul secondo invio → verificare che il flusso principale non si interrompa e che il warning sia loggato
- [ ] Confermare lista `zone_id` definitiva con ERSU e aggiornare `config/lunigiana.php` se necessario

---

## File modificati

| File | Tipo | Repo |
|---|---|---|
| `config/lunigiana.php` | Nuovo | Principale |
| `app/Models/Ticket.php` | Modifica (aggiunta metodo) | Principale |
| `app/Http/Controllers/TicketController.php` | Modifica (3 punti) | Principale |
