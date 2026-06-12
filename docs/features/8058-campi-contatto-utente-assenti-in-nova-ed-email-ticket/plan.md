> Ticket: oc:8058

# Plan — [Bug] Campi contatto utente assenti in Nova ed email ticket

## Branch

```bash
git checkout -b fix/oc-8058-campi-contatto-utente-nova-email
```

---

## Step 1 — Nova: eager load user in indexQuery

**File:** `app/Nova/Ticket.php`

Aggiungere `->with(['user'])` in `indexQuery` per prevenire N+1 quando `BelongsTo::make('User')` viene visualizzato nell'index.

```php
public static function indexQuery(NovaRequest $request, $query)
{
    return $query->where('company_id', $request->user()->companyWhereAdmin->id)
                 ->with(['user']);
}
```

---

## Step 2 — Nova: ripristinare import BelongsTo e 4 campi statici

**File:** `app/Nova/Ticket.php`

**2a.** Aggiungere l'import mancante nella sezione `use`:

```php
use Laravel\Nova\Fields\BelongsTo;
```

**2b.** In `_headerFields`, inserire i 4 campi statici wrappati in `if ($this->user)`, prima della chiamata a `$this->_userFormDataFields($fields)`:

```php
$fields[] = DateTime::make(__('Created At'), 'created_at')->sortable()->readonly();
if ($this->user) {
    $fields[] = Text::make(__('Name'), function () {
        return $this->checkName($this->user->name);
    })->readonly()->onlyOnDetail();
    $fields[] = BelongsTo::make('User')->readonly();
    $fields[] = Text::make('Email', function () {
        return $this->user->email;
    })->readonly();
    $fields[] = Text::make(__('Phone'), function () {
        return $this->phone;
    })->onlyOnDetail()->readonly();
}
$this->_userFormDataFields($fields);
```

---

## Step 3 — Email partial: aggiungere righe statiche email e nome account

**File:** `resources/views/emails/tickets/partials/user-form-fields.blade.php`

Prima del blocco `@foreach ($rows as $row)` (che renderizza i dati TARI dinamici), aggiungere le righe fisse per email e nome dell'account. Le righe usano la stessa struttura condizionale dei tre formati già supportati (`br`, `table`, `paragraph`).

Inserire dopo il blocco `@endphp` (riga 73) e prima di `@foreach ($rows as $row)`:

```blade
@if ($user)
    @if ($format === 'paragraph')
        <p><strong>{{ __('Email') }}:</strong> {{ $user->email ?: '-' }}</p>
        <p><strong>{{ __('Name') }}:</strong> {{ $user->name ?: '-' }}</p>
    @elseif ($format === 'table')
        <tr>
            <td width="130" style="padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;vertical-align:top;">{{ __('Email') }}</td>
            <td style="padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">{{ $user->email ?: '-' }}</td>
        </tr>
        <tr>
            <td width="130" style="padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;vertical-align:top;">{{ __('Name') }}</td>
            <td style="padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">{{ $user->name ?: '-' }}</td>
        </tr>
    @else
        <strong>{{ __('Email') }}:</strong> {{ $user->email ?: '-' }}<br>
        <strong>{{ __('Name') }}:</strong> {{ $user->name ?: '-' }}<br>
    @endif
@endif
```

---

## Step 4 — Test Nova: regressione campi statici

**File:** `tests/Unit/Nova/TicketResourceTest.php`

Aggiungere un test che verifica che `_headerFields` includa i 4 campi statici quando l'utente esiste. Usare `ReflectionMethod` come già fatto nei test esistenti.

```php
/** @test */
public function headerFieldsIncludesStaticUserFields(): void
{
    $company = Company::factory()->create(['form_json' => null]);
    $user = User::factory()->create(['app_company_id' => $company->id]);
    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);

    $resource = new TicketResource($ticket);
    $fields = [];

    $method = new ReflectionMethod(TicketResource::class, '_headerFields');
    $method->setAccessible(true);
    $method->invokeArgs($resource, [&$fields]);

    $fieldNames = array_map(fn($f) => $f->name, $fields);
    $this->assertContains('Name', $fieldNames);
    $this->assertContains('Email', $fieldNames);
    $this->assertContains('User', $fieldNames);
    $this->assertContains('Phone', $fieldNames);
}

/** @test */
public function headerFieldsSkipsStaticUserFieldsWhenUserIsNull(): void
{
    $company = Company::factory()->create(['form_json' => null]);
    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'user_id' => null,
    ]);

    $resource = new TicketResource($ticket);
    $fields = [];

    $method = new ReflectionMethod(TicketResource::class, '_headerFields');
    $method->setAccessible(true);
    $method->invokeArgs($resource, [&$fields]);

    $fieldNames = array_map(fn($f) => $f->name, $fields);
    $this->assertNotContains('User', $fieldNames);
    $this->assertNotContains('Email', $fieldNames);
}
```

---

## Step 5 — Test email: regressione email e nome account nel partial

**File:** `tests/Feature/Emails/TicketEmailViewsTest.php`

Aggiungere test che verificano la presenza di `user->email` e `user->name` nell'output HTML del partial, per tutti e tre i formati rilevanti.

```php
/** @test */
public function partialRendersAccountEmailAndNameBeforeTariData(): void
{
    ['company' => $company, 'user' => $user, 'ticket' => $ticket] = $this->createTicketContext();

    $html = view('emails.tickets.partials.user-form-fields', [
        'user' => $user,
        'company' => $company,
        'ticket' => $ticket,
        'format' => 'table',
    ])->render();

    $this->assertStringContainsString($user->email, $html);
    $this->assertStringContainsString($user->name, $html);
    // email e nome account devono apparire PRIMA dei dati TARI
    $emailPos = strpos($html, $user->email);
    $tariPos  = strpos($html, 'CF123456');
    $this->assertLessThan($tariPos, $emailPos, 'Email account deve precedere i dati TARI');
}

/** @test */
public function partialSkipsAccountFieldsWhenUserIsNull(): void
{
    $html = view('emails.tickets.partials.user-form-fields', [
        'user' => null,
        'company' => null,
        'ticket' => null,
        'format' => 'br',
    ])->render();

    $this->assertSame('', trim($html));
}
```

---

## Step 6 — Eseguire la test suite

```bash
docker exec php_portapporta php artisan config:clear
docker exec php_portapporta php artisan test --filter="TicketResourceTest|TicketEmailViewsTest"
```

Tutti i test devono essere verdi prima di procedere al commit.

---

## Commit

```
fix(oc:8058): restore static user fields in Nova and email partial

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

## PR

Aprire verso `develop` (branch di integrazione Webmapp).
