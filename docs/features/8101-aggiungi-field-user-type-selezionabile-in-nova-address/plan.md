> Ticket: oc:8101

# Plan — Aggiungi field user_type selezionabile in Nova Address

## Task 1 — Sostituire il campo User Type in `app/Nova/Address.php`

Sostituire il blocco:

```php
Text::make('User Type', function () {
    if (!is_null($this->user_type_id)) {
        return $this->userType->label;
    }
    return 'ND';
})->onlyOnDetail(),
```

con:

```php
Select::make('User Type', 'user_type_id')
    ->options(function () {
        $addressUser = $this->user;
        if ($addressUser) {
            $companyId = $addressUser->app_company_id;
            return \App\Models\UserType::where('company_id', $companyId)
                ->get()->pluck('label', 'id');
        }
        $authUser = Auth()->user();
        if ($authUser->hasRole('company_admin')) {
            return \App\Models\UserType::where('company_id', $authUser->admin_company_id)
                ->get()->pluck('label', 'id');
        }
        // super_admin senza utente sull'address: mostra tutti
        return \App\Models\UserType::all()->pluck('label', 'id');
    })
    ->displayUsing(function ($value) {
        return \App\Models\UserType::find($value)?->label ?? 'ND';
    })
    ->rules(function () {
        $addressUser = $this->user;
        if ($addressUser) {
            $ids = \App\Models\UserType::where('company_id', $addressUser->app_company_id)
                ->pluck('id')->toArray();
        } else {
            $authUser = Auth()->user();
            if ($authUser->hasRole('company_admin')) {
                $ids = \App\Models\UserType::where('company_id', $authUser->admin_company_id)
                    ->pluck('id')->toArray();
            } else {
                $ids = \App\Models\UserType::pluck('id')->toArray();
            }
        }
        return ['nullable', \Illuminate\Validation\Rule::in($ids)];
    })
    ->nullable()
    ->hideFromIndex(),
```

Aggiungere `use Illuminate\Validation\Rule;` agli import in testa al file se non già presente.

## Task 2 — Verificare gli import

Controllare che in testa a `app/Nova/Address.php` siano presenti tutti gli import necessari:
- `use Laravel\Nova\Fields\Select;` — già presente
- `use Illuminate\Validation\Rule;` — aggiungere se assente

## Task 3 — Smoke test manuale

Aprire Nova e verificare:
1. Detail di un address esistente → il campo mostra il label tradotto del user_type associato
2. Edit di un address → il Select mostra solo i user_type della company dell'utente dell'address
3. Salvataggio con un `user_type_id` valido → viene salvato correttamente
4. Salvataggio senza `user_type_id` → viene salvato con null senza errori
5. (Se testabile) tentativo di POST con `user_type_id` cross-company → Nova restituisce errore di validazione

## Commit

```
feat(oc:8101): aggiungi Select user_type editabile in Nova Address
```
