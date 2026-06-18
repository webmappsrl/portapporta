> Ticket: oc:8101

# Aggiungi field user_type selezionabile in Nova Address

## Cosa cambia
Il campo "User Type" nella risorsa Nova `Address` passa da un `Text` read-only visibile solo in detail a un `Select` editabile disponibile in creazione, modifica e dettaglio. Le opzioni mostrate sono filtrate per la company dell'utente associato all'address (o del company_admin loggato se l'address non ha ancora un utente).

## Perché
Gli admin non possono assegnare o modificare il tipo utente di un address dal pannello Nova: il campo è visibile ma non editabile. Questo costringe interventi manuali diretti sul DB per correggere o assegnare il tipo utente.

## Requisiti
- [ ] Sostituire `Text::make('User Type', fn() => ...)->onlyOnDetail()` con un `Select::make('User Type', 'user_type_id')` editabile
- [ ] Le opzioni del Select sono filtrate per company:
  - Se l'address ha un utente associato (`$this->user`): filtra per `$this->user->app_company_id`
  - Se nessun utente + ruolo `company_admin`: filtra per `Auth()->user()->admin_company_id`
  - Se nessun utente + ruolo `super_admin`: mostra tutti i user_type
- [ ] `displayUsing` mostra il label tradotto nella locale corrente (usa `->get()->pluck('label','id')` per rispettare `HasTranslations`)
- [ ] Il campo è **nullable** (non required) — coerente con la colonna DB `nullable()`
- [ ] Il campo è visibile in detail e nei form (create + edit), **non** in index (`hideFromIndex()`)
- [ ] Validazione server-side con `->rules([Rule::in(...)])` che limita i valori accettabili ai soli `user_type_id` della company corretta — blocca assegnazioni cross-company anche se il Select mostrasse opzioni errate
- [ ] Nessun reset automatico di `user_type_id` al cambio di `user_id`

## Rischi
- **Label JSON in pluck:** `UserType::pluck('label','id')` restituisce il JSON grezzo invece del testo tradotto. Mitigazione: usare `->get()->pluck('label','id')` che passa per l'accessor `HasTranslations`.
- **Fallback `app_company_id` vs `admin_company_id`:** il campo Zone usa `app_company_id` come fallback, ma per un `company_admin` quella property è null — per questo campo si usa intenzionalmente `admin_company_id`. Divergenza documentata.
- **Performance:** `->get()->pluck()` carica tutta la collection in memoria per ogni rendering del form. Accettato come known limitation — il numero di user_type per company è piccolo.
- **Rollback e audit:** Nova non ha audit trail abilitato. Se venissero salvati dati cross-company prima del rollback, sarebbero irrecuperabili senza snapshot DB. La validazione server-side è la principale mitigazione.
- **Fallback super_admin su address orfano:** mostra tutti i user_type cross-company nel Select — edge case accettato (0 orfani in produzione), ma la validazione server-side blocca comunque il salvataggio cross-company.

## Out of scope
- Reset automatico di `user_type_id` al cambio di `user_id`
- Filtro user_type in index (il campo non compare nell'elenco)
- Migrazione DB (la colonna `user_type_id` nullable esiste già)
- Audit trail dei cambiamenti

## Moduli toccati
- `app/Nova/Address.php` — unico file modificato
