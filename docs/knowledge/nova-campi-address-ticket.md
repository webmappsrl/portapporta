# Perché i campi Nova di Address e Ticket sono costruiti così

## Come funziona oggi

**Address — Select `user_type` editabile (oc:8101):**
- `userTypeOptionsForCompany(): Builder` è un metodo privato non-static che centralizza il filtro per company, usato sia da `->options()` che da `->rules()` (`Rule::in`): le opzioni mostrate e i valori accettati non possono divergere.
- `->options()` usa `->get()->pluck('label','id')` (idrata i model per `HasTranslations`); `->rules()` usa `->pluck('id')` (query leggera). Stesso Builder, chiamate diverse.
- Fallback per company_admin senza utente sull'address: `admin_company_id` (non `app_company_id`, come fa invece il campo `zone_id`): per un company_admin `app_company_id` è null — divergenza intenzionale.
- `displayUsing` usa `$this->userType?->label` (relazione), non `UserType::find()` (query extra).

**Ticket — campi contatto utente e email (oc:8058):**
- I 4 campi statici (`Name`, `Email`, `BelongsTo User`, `Phone`) in `_headerFields` sono wrappati in `if ($this->user)`: i ticket orfani mantengono visibile il resto dell'header.
- `->with(['user'])` in `indexQuery` previene N+1 col `BelongsTo` visibile in index.
- Nel partial email `user-form-fields.blade.php`, email e nome account precedono i dati TARI dinamici, inline, per tutti i formati (`br`, `table`, `paragraph`).

## Perché così

- La validazione server-side con lo stesso Builder delle opzioni (oc:8101) blocca assegnazioni cross-company anche da richieste costruite a mano.
- I test Nova asseriscono su etichette tradotte con `__('Name')` / `__('Phone')` perché il locale del progetto è `it` (oc:8058).

## Come ci siamo arrivati

- Cantieri: oc:8101 (Select user_type in Nova Address), oc:8058 (ripristino campi contatto in Nova ed email ticket).
