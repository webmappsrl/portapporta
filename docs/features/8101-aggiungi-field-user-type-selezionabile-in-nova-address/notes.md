> Ticket: oc:8101

# Notes — Aggiungi field user_type selezionabile in Nova Address

## Deviazioni dal piano

- La logica di filtro per company era duplicata tra `->options()` e `->rules()` nella prima implementazione. Dopo la code review è stata estratta nel metodo privato `userTypeOptionsForCompany()` — deviazione migliorativa rispetto al piano originale.

## Bug trovati

Nessuno.

## Decisioni

- `displayUsing` usa `$this->userType?->label` invece di `UserType::find($value)?->label` — evita una query extra sfruttando la relazione Eloquent già disponibile sul model.
- `userTypeOptionsForCompany()` restituisce un `Builder` anziché una Collection — così `->options()` può aggiungere `->get()->pluck()` (necessario per `HasTranslations`) e `->rules()` può aggiungere `->pluck('id')` (query leggera senza idratazione).
- Il metodo è `private` e non `static`: accede a `$this->user` e `Auth()->user()` — pattern coerente con `indexQuery` già presente nel file.

## Follow-up

- Il campo `zone_id` nello stesso file usa ancora FQCN inline (`\App\Models\Zone`) e `app_company_id` come fallback per tutti i ruoli (incluso `company_admin`) — potrebbe essere allineato al pattern più corretto introdotto qui, ma è fuori scope di questo ticket.
