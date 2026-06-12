> Ticket: oc:8054

# Plan — [ersu] Bug oc:7609 — Bloccanti 3 e 4 (backend)

Commit convention: `fix(oc:8054): ...`
Branch: `oc_558` (branch già esistente per la feature padre)

---

## Step 1 — Bloccante 4: log warning in `filterExcludeInProgress`

**File:** `app/Http/Controllers/CalendarController.php`

Nel metodo `filterExcludeInProgress` (riga ~281), nel blocco `catch (\Exception $e)` aggiungere il log prima del `continue`:

```php
} catch (\Exception $e) {
    if ($this->logger) {
        $this->logger->warning('filterExcludeInProgress: stop_time non parsabile', [
            'stop_time' => $item['stop_time'],
            'day' => $todayKey,
        ]);
    }
    continue;
}
```

Nessuna altra modifica al comportamento.

---

## Step 2 — Aggiunta metodo `isCollectionInProgress` su `CalendarController`

**File:** `app/Http/Controllers/CalendarController.php`

Aggiungere il metodo `public` subito dopo `filterExcludeInProgress`. Il metodo:
1. Inizializza `$this->logger` se `null` (viene chiamato anche da altri controller)
2. Carica i `Calendar` attivi oggi per la zona con i loro `calendarItems`
3. Filtra gli item per `day_of_week == oggi` (in-memory, stesso pattern del controller)
4. Restituisce `true` se almeno uno ha `stop_time` nel futuro

```php
public function isCollectionInProgress(int $zoneId): bool
{
    $this->logger = $this->logger ?? Log::channel('calendars');
    $today = Carbon::today();

    $calendars = Calendar::where('zone_id', $zoneId)
        ->whereDate('start_date', '<=', $today)
        ->whereDate('stop_date', '>=', $today)
        ->with('calendarItems')
        ->get();

    $now = Carbon::now();
    foreach ($calendars as $calendar) {
        foreach ($calendar->calendarItems->where('day_of_week', $today->dayOfWeek) as $item) {
            if (!isset($item->stop_time) || $item->stop_time === '') {
                continue;
            }
            try {
                $stop = Carbon::today()->copy()->setTimeFromTimeString((string) $item->stop_time);
                if ($now->lessThan($stop)) {
                    $this->logger->warning('isCollectionInProgress: giro ancora in corso', [
                        'zone_id' => $zoneId,
                        'stop_time' => $stop->format('H:i:s'),
                    ]);
                    return true;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }
    return false;
}
```

**Note implementative:**
- La query filtra su `calendars.zone_id`, **non** su `calendar_items.zone_id` (che non esiste).
- Il filtro `day_of_week` è in-memory sulla collection eager-loaded — stesso pattern di `createCalendar`.
- `$this->logger` può essere `null` se chiamato da fuori: il `??` lo inizializza prima dell'uso.

---

## Step 3 — Bloccante 3: validazione in `v1store`

**File:** `app/Http/Controllers/TicketController.php`

Aggiungere il check server-side **dopo** che `$ticket->ticket_type` è stato assegnato e **prima** di `$ticket->save()`. Posizionare dopo le righe di assegnazione dei campi opzionali (`trash_type_id`, `address_id`, `zone_id`, `missed_withdraw_date`), prima di `$ticket->geometry`:

```php
if ($ticket->ticket_type === TicketType::Report->value
    && $request->exists('missed_withdraw_date')
    && Carbon::parse($request->missed_withdraw_date)->isToday()
    && $request->exists('zone_id')
    && app(CalendarController::class)->isCollectionInProgress((int) $request->zone_id)
) {
    return $this->sendError('Il giro di raccolta è ancora in corso, riprova più tardi.');
}
```

Aggiungere `use Carbon\Carbon;` agli import se non presente.
Aggiungere `use App\Http\Controllers\CalendarController;` — non serve perché sono nello stesso namespace `App\Http\Controllers`.

---

## Step 4 — Bloccante 3: validazione in `v1update`

**File:** `app/Http/Controllers/TicketController.php`

Aggiungere il check **dopo** l'assegnazione dei campi e **prima** di `$ticket->save()` (o equivalente), **solo** se `missed_withdraw_date` è nel payload:

```php
if ($request->exists('missed_withdraw_date')
    && Carbon::parse($request->missed_withdraw_date)->isToday()
) {
    $zoneId = $request->zone_id ?? $ticket->zone_id;
    if ($zoneId && app(CalendarController::class)->isCollectionInProgress((int) $zoneId)) {
        return $this->sendError('Il giro di raccolta è ancora in corso, riprova più tardi.');
    }
}
```

Il fail-open è garantito dalla condizione `$zoneId &&`: se né la request né il ticket hanno `zone_id`, nessun check.

---

## Step 5 — Test bloccante 4: `CalendarControllerTest`

**File:** `tests/Feature/V2/CalendarControllerTest.php`

Aggiungere un test che verifica che uno `stop_time` malformato non generi un'eccezione e non escluda il giorno:

```php
/** @test */
public function testV1IndexExcludeInProgressWithMalformedStopTimeKeepsToday(): void
{
    Carbon::setTestNow(Carbon::today()->setTime(10, 0));
    $this->calendarItem->update([
        'day_of_week' => Carbon::today()->dayOfWeek,
        'start_time'  => '08:00',
        'stop_time'   => 'invalid-time',
    ]);
    Sanctum::actingAs($this->user);
    $todayKey = Carbon::today()->format('Y-m-d');

    $response = $this->get(self::API_PREFIX . $this->company->id . '/calendar?exclude_in_progress=1');
    $this->assertSuccessResponse($response, self::responseMessages['calendarCreated']);

    $calendar = $response->json('data.0.calendar') ?? [];
    $this->assertArrayHasKey($todayKey, $calendar);

    Carbon::setTestNow();
}
```

---

## Step 6 — Test bloccante 3: `TicketControllerTest`

**File:** `tests/Feature/V2/TicketControllerTest.php`

Aggiungere costante messaggio di errore:
```php
'collectionInProgress' => 'Il giro di raccolta è ancora in corso, riprova più tardi.',
```

Aggiungere helper privato per creare Calendar + CalendarItem per una zona con `stop_time` configurabile:
```php
private function createCalendarWithStopTime(Zone $zone, string $stopTime): void
{
    $calendar = \App\Models\Calendar::factory()->create([
        'zone_id'    => $zone->id,
        'company_id' => $this->company->id,
        'start_date' => Carbon::today()->subDays(5),
        'stop_date'  => Carbon::today()->addDays(30),
    ]);
    $item = \App\Models\CalendarItem::factory()->create([
        'calendar_id' => $calendar->id,
        'day_of_week' => Carbon::today()->dayOfWeek,
        'start_time'  => '08:00',
        'stop_time'   => $stopTime,
        'frequency'   => 'weekly',
    ]);
}
```

**Test 1 — v1store bloccato (giro in corso):**
```php
/** @test */
public function testV1StoreRejectsMissedWithdrawWhileRoundInProgress(): void
{
    Carbon::setTestNow(Carbon::today()->setTime(10, 0));
    $zone = $this->createZone($this->company);
    $this->createCalendarWithStopTime($zone, '12:00');

    Sanctum::actingAs($this->user);
    Mail::fake();

    $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
        'ticket_type'          => 'report',
        'zone_id'              => $zone->id,
        'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
    ]);

    $this->assertErrorResponse($response, self::responseMessages['collectionInProgress'], 400);

    Carbon::setTestNow();
}
```

**Test 2 — v1store permesso (giro finito):**
```php
/** @test */
public function testV1StoreAllowsMissedWithdrawAfterRoundEnds(): void
{
    Carbon::setTestNow(Carbon::today()->setTime(13, 0));
    $zone = $this->createZone($this->company);
    $this->createCalendarWithStopTime($zone, '12:00');

    Sanctum::actingAs($this->user);
    Mail::fake();

    $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
        'ticket_type'          => 'report',
        'zone_id'              => $zone->id,
        'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
    ]);

    $this->assertSuccessResponse($response, self::responseMessages['ticketCreated']);

    Carbon::setTestNow();
}
```

**Test 3 — v1store fail-open (zone_id assente):**
```php
/** @test */
public function testV1StoreAllowsMissedWithdrawWithoutZoneId(): void
{
    Carbon::setTestNow(Carbon::today()->setTime(10, 0));

    Sanctum::actingAs($this->user);
    Mail::fake();

    $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
        'ticket_type'          => 'report',
        'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
    ]);

    $this->assertSuccessResponse($response, self::responseMessages['ticketCreated']);

    Carbon::setTestNow();
}
```

**Test 4 — v1update bloccato (missed_withdraw_date nel payload + giro in corso):**
```php
/** @test */
public function testV1UpdateRejectsMissedWithdrawWhileRoundInProgress(): void
{
    Carbon::setTestNow(Carbon::today()->setTime(10, 0));
    $zone = $this->createZone($this->company);
    $this->createCalendarWithStopTime($zone, '12:00');

    $ticket = Ticket::factory()->create([
        'company_id' => $this->company->id,
        'zone_id'    => $zone->id,
    ]);

    $response = $this->patch(self::API_PREFIX_TICKET . "{$ticket->id}", [
        'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
    ]);

    $this->assertErrorResponse($response, self::responseMessages['collectionInProgress'], 400);

    Carbon::setTestNow();
}
```

**Test 5 — v1update non controlla se missed_withdraw_date non è nel payload:**
```php
/** @test */
public function testV1UpdateSkipsCheckWhenMissedWithdrawDateNotInPayload(): void
{
    Carbon::setTestNow(Carbon::today()->setTime(10, 0));
    $zone = $this->createZone($this->company);
    $this->createCalendarWithStopTime($zone, '12:00');

    $ticket = Ticket::factory()->create([
        'company_id'           => $this->company->id,
        'zone_id'              => $zone->id,
        'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
    ]);

    $response = $this->patch(self::API_PREFIX_TICKET . "{$ticket->id}", [
        'note' => 'solo aggiornamento note',
    ]);

    $this->assertSuccessResponse($response, self::responseMessages['ticketUpdated']);

    Carbon::setTestNow();
}
```

---

## Step 9 — Bug city/Comune: salvataggio in `location_address`

**File:** `app/Http/Controllers/TicketController.php`

In `store`, `v1store` e `v1update`: appendere `city` a `location_address` con separatore ` — ` se presente nel payload.

In `v1store` (righe 192-202), sostituire il blocco costruzione `location_address` con:

```php
$location_address = '';
if (!is_null($request->address)) {
    $location_address .= $request->address;
}
if (!is_null($request->house_number)) {
    if (!empty($location_address)) {
        $location_address .= ', ';
    }
    $location_address .= $request->house_number;
}
if (!is_null($request->city)) {
    if (!empty($location_address)) {
        $location_address .= ' — ';
    }
    $location_address .= $request->city;
}
$ticket->location_address = $location_address;
```

Stesso pattern in `store` (aggiungere dopo il blocco Nominatim come fallback se `location_address` è vuota) e in `v1update`.

---

## Step 10 — Bug city/Comune: Nova fallback

**File:** `app/Nova/Ticket.php`

Nel ramo `else` (linea ~216, quando né `address` né `zone` FK), aggiungere parsing di `city` da `location_address` e campo "Comune":

```php
} else {
    if (!empty($this->location_address)) {
        $locationParts = explode(' — ', $this->location_address, 2);
        $addressPart   = $locationParts[0];
        $cityFallback  = $locationParts[1] ?? '';

        $parts  = explode(', ', $addressPart, 2);
        $via    = $parts[0] ?? '';
        $civico = $parts[1] ?? '';

        if (!empty($cityFallback)) {
            $fields[] = Text::make(__('Comune'), function () use ($cityFallback) {
                return $cityFallback;
            })->onlyOnDetail()->readonly();
        }
        if (!empty($via)) {
            $fields[] = Text::make(__('Address'), function () use ($via) {
                return $via;
            })->onlyOnDetail()->readonly();
        }
        if (!empty($civico)) {
            $fields[] = Text::make(__('House Number'), function () use ($civico) {
                return $civico;
            })->onlyOnDetail()->readonly();
        }
    }
    // ... resto invariato (geometry/coordinate)
```

---

## Step 11 — Bug city/Comune: email template fallback

**File:** `resources/views/emails/tickets/created.blade.php`

Nel blocco PHP iniziale (righe 26-38), aggiungere estrazione `$ticket_city` da `location_address`:

```php
$ticket_city = '';
if ($zone) {
    $ticket_city = $zone->comune ?? '';
} elseif ($ticket->location_address) {
    $cityPart    = explode(' — ', $ticket->location_address, 2)[1] ?? '';
    $ticket_city = $cityPart;
}
```

Aggiungere riga "Comune" nel blocco "DOVE INTERVENIRE" subito prima del blocco `@if($ticket_via)`, mostrandola solo se valorizzata e `$zone` è null (per non duplicare con la riga zona già presente):

```blade
@if(!$zone && $ticket_city)
<tr>
    <td width="130" style="padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">Comune</td>
    <td style="padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">{{ $ticket_city }}</td>
</tr>
@endif
```

---

## Step 7 — Esecuzione test

```bash
docker exec php_portapporta php artisan config:clear
docker exec php_portapporta php artisan test --filter=CalendarControllerTest
docker exec php_portapporta php artisan test --filter=TicketControllerTest
```

---

## Step 8 — Commit

```
fix(oc:8054): add warning log for malformed stop_time in filterExcludeInProgress
fix(oc:8054): add server-side enforcement for missed_withdraw_date during active round
```

> ⚠️ Prerequisito deploy: la migrazione `oc_7612` (`add_zone_id_to_tickets_table`) deve essere eseguita prima del deploy di questo fix.
>
> ⚠️ No commit o push automatici — i commit vanno eseguiti dopo approvazione esplicita del developer.
