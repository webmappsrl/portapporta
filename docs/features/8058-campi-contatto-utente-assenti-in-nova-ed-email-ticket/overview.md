> Ticket: oc:8058

# [Bug] Campi contatto utente assenti in Nova ed email ticket

## Cosa cambia

Il pannello dettaglio ticket in Nova torna a mostrare Name, Email, BelongsTo User e Phone ticket accanto ai campi TARI dinamici. Le email di notifica operatore tornano a includere email e nome account (dati di registrazione) come prime righe nella sezione "CHI HA SEGNALATO", prima dei dati TARI dinamici.

## Perché

Il commit `e42e206` (oc:7599) ha sostituito i 4 campi statici di Nova con la sola sezione dinamica `form_data`, invece di affiancarli. Il partial email `user-form-fields.blade.php` mostra solo i campi del form_json/form_data, omettendo le colonne `email` e `name` della tabella `users`. Gli operatori URP non possono più contattare il cittadino senza aprire manualmente il profilo utente.

## Requisiti

- [ ] Nova `app/Nova/Ticket.php`: ripristinare `use Laravel\Nova\Fields\BelongsTo` e i 4 campi statici in `_headerFields` — nell'ordine originale, prima di `_userFormDataFields`, wrappati in `if ($this->user)`
  - `Text::make(__('Name'))` → `->readonly()->onlyOnDetail()`
  - `BelongsTo::make('User')` → `->readonly()` (visibile anche in index)
  - `Text::make('Email')` → `->readonly()` (visibile anche in index)
  - `Text::make(__('Phone'))` → `->onlyOnDetail()->readonly()`
- [ ] Nova `app/Nova/Ticket.php`: aggiungere `->with(['user'])` in `indexQuery` per prevenire N+1 con `BelongsTo` in index
- [ ] Partial email `resources/views/emails/tickets/partials/user-form-fields.blade.php`: aggiungere email e nome account come prime righe inline (senza header separatore), prima dei dati TARI dinamici; skip se `$user` è null
- [ ] Aggiungere test di regressione in `tests/Unit/Nova/TicketResourceTest.php` per verificare la presenza dei 4 campi statici nel pannello Nova
- [ ] Aggiungere test di regressione in `tests/Feature/Emails/TicketEmailViewsTest.php` per verificare la presenza di email e nome account nel partial email

## Rischi

- **Doppio telefono in email**: se il campo `phone_number` è presente nello schema TARI, il telefono appare due volte (nella riga statica account e nella riga dinamica TARI). Non è un bloccante (i valori sono tipicamente identici) ed è out of scope per questo ticket.
- **Nome account è un'email**: `checkName()` restituisce stringa vuota se `user->name` è un indirizzo email valido — questo era il comportamento originale, si ripristina invariato.

## Out of scope

- Cleanup: Nova mostra telefono da `form_data` invece di `ticket.phone`
- Cleanup: telefono vuoto in email se `ticket.phone` assente ma profilo utente valorizzato
- Cleanup: `filterOnlyFeSchema` non chiamato in Nova Ticket

## Moduli toccati

- `app/Nova/Ticket.php`
- `resources/views/emails/tickets/partials/user-form-fields.blade.php`
- `tests/Unit/Nova/TicketResourceTest.php`
- `tests/Feature/Emails/TicketEmailViewsTest.php`
