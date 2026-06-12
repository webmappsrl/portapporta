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

- `filterOnlyFeSchema` non è ancora chiamato in Nova — rimane un cleanup non-bloccante del code review oc:7599.

## Dedup + resolvePhone (aggiunto post-commit iniziale)

Dopo il primo commit è emerso (screenshot) che nome, email e telefono erano mostrati due volte in Nova: una dai 4 campi statici, una dai campi dinamici `form_data` che includevano gli stessi field name. Stesso problema nel partial email.

**Fix applicato in secondo commit:**
- `Ticket::resolvePhone()` — metodo sul model, sorgente unica per la logica telefono (ticket->phone prima, user->phone_number come fallback). Condiviso da Nova e dal partial email.
- `_userFormDataFields` in Nova — filtro `$staticNames = ['name', 'email', 'phone_number', 'phone']` applicato prima di creare i field dinamici.
- `user-form-fields.blade.php` — stesso filtro `$staticNames` applicato dentro il foreach dei `$filtered`, più `$resolveTicketPhone` semplificato a chiamare `$ticket->resolvePhone()`.
