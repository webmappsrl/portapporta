> Ticket: oc:8058

# Notes — [Bug] Campi contatto utente assenti in Nova ed email ticket

## Deviazioni dal piano

- **Test Nova con locale italiano**: le asserzioni sui campi tradotti (`__('Name')` → `'Nome'`, `__('Phone')` → `'Telefono'`) richiedono `__()` invece delle stringhe hardcoded in inglese. Il piano non lo specificava. Risolto usando `__('Name')` e `__('Phone')` nelle assertion.
- **Test `headerFieldsSkipsStaticUserFieldsWhenUserIsNull`**: `user_id` è NOT NULL in DB, impossibile creare un ticket senza utente via factory. Soluzione: `$ticket->setRelation('user', null)` per simulare il caso orfano senza toccare il DB.
- **Test `indexQueryEagerLoadsUser`**: `NovaRequest::create(...)` senza utente autenticato causa null pointer su `companyWhereAdmin`. Soluzione: `$request->setUserResolver(fn() => $admin)` con un utente che ha `admin_company_id` impostato.

## Decisioni

- I 4 campi statici sono wrappati in `if ($this->user)` invece di un early return globale — così gli altri campi dell'header (Ticket ID, Tipo, Data, Status, Zona) rimangono visibili anche per ticket orfani.
- `->with(['user'])` aggiunto in `indexQuery` (non solo `detailQuery`) per prevenire N+1 con `BelongsTo::make('User')` visibile in index.
- Le righe email + nome nel partial usano `__('Email')` / `__('Name')` per coerenza con il resto del template (già internazionalizzato).

## Follow-up

- I 3 cleanup non-bloccanti del code review oc:7599 rimangono aperti: telefono da `ticket.phone` vs `form_data` in Nova, fallback telefono in email, `filterOnlyFeSchema` non chiamato in Nova.
