> Ticket: oc:8113

# Piano implementativo — Ricalcolo automatico coordinate/zona se discrepanza con indirizzo

## Task 1 — Kill switch in config/app.php

**File:** `config/app.php`

Aggiungere in fondo all'array di configurazione:

```php
'address_discrepancy_check_enabled' => env('ADDRESS_DISCREPANCY_CHECK_ENABLED', true),
```

Aggiungere al `.env` (e `.env.example` se presente):

```
ADDRESS_DISCREPANCY_CHECK_ENABLED=true
```

---

## Task 2 — Metodo privato `_correctLocationFromAddress()`

**File:** `app/Http/Controllers/TicketController.php`

Aggiungere un metodo privato dopo `_deriveZoneId()`:

```php
private function _correctLocationFromAddress(Ticket $ticket, Request $request): void
{
    // TODO: rimuovere questo controllo una volta che l'app avrà il fix lato frontend
    // e tutti gli utenti avranno aggiornato. Disabilitabile via ADDRESS_DISCREPANCY_CHECK_ENABLED=false.
    if (!config('app.address_discrepancy_check_enabled', true)) {
        return;
    }

    // Solo per flusso da mappa (nessun indirizzo salvato)
    if ($ticket->address_id) {
        return;
    }

    // city obbligatoria per una query Nominatim significativa
    if (empty($request->city)) {
        return;
    }

    // Coordinate originali necessarie per il confronto
    if (!$ticket->geometry) {
        return;
    }

    // Forward geocoding del testo indirizzo via Nominatim
    $street = trim(($request->address ?? '') . ' ' . ($request->house_number ?? ''));
    $query = http_build_query([
        'street'       => $street,
        'city'         => $request->city,
        'format'       => 'json',
        'limit'        => 1,
        'countrycodes' => 'it',
    ]);
    $url = 'https://nominatim.openstreetmap.org/search?' . $query;
    $response = $this->curlRequest($url);

    if (empty($response) || !isset($response[0]['lat'], $response[0]['lon'])) {
        Log::info('Address discrepancy check: Nominatim returned no results, keeping original coordinates', [
            'ticket_city'    => $request->city,
            'ticket_address' => $street,
        ]);
        return;
    }

    $textLat = (float) $response[0]['lat'];
    $textLon = (float) $response[0]['lon'];

    // Geometria dalle coordinate testuali
    $textGeometry = (DB::select(
        DB::raw("SELECT ST_GeomFromText('POINT($textLon $textLat)') as g;")
    ))[0]->g;

    // Zona dal testo vs zona dalle coordinate originali
    $zoneFromText   = Zone::findByPoint($textGeometry, $ticket->company_id);
    $zoneFromCoords = Zone::findByPoint($ticket->geometry, $ticket->company_id);

    // Nessuna discrepanza o zona testo non trovata: nessuna correzione
    if (!$zoneFromText || ($zoneFromCoords && $zoneFromCoords->id === $zoneFromText->id)) {
        return;
    }

    Log::warning('Address discrepancy detected: overriding coordinates and zone from text address', [
        'original_zone_id'  => $zoneFromCoords?->id,
        'corrected_zone_id' => $zoneFromText->id,
        'corrected_lat'     => $textLat,
        'corrected_lon'     => $textLon,
        'address'           => $street . ' — ' . $request->city,
    ]);

    $ticket->geometry = $textGeometry;
    $ticket->zone_id  = $zoneFromText->id;
}
```

---

## Task 3 — Chiamata in `v1store`

**File:** `app/Http/Controllers/TicketController.php`, metodo `v1store`

Inserire la chiamata a `_correctLocationFromAddress()` **dopo** l'assegnazione di `geometry` e **prima** di `_deriveZoneId()`.

Il codice attuale (righe ~195-217):

```php
if ($request->exists('location')) {
    $ticket->geometry = (DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$request->location[0]} {$request->location[1]})') as g;")))[0]->g;
}
// ... costruzione location_address ...
$ticket->location_address = $location_address;
if (is_null($ticket->zone_id)) {
    $ticket->zone_id = $this->_deriveZoneId($ticket);
}
```

Diventa:

```php
if ($request->exists('location')) {
    $ticket->geometry = (DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$request->location[0]} {$request->location[1]})') as g;")))[0]->g;
}
// ... costruzione location_address ...
$ticket->location_address = $location_address;
$this->_correctLocationFromAddress($ticket, $request);
if (is_null($ticket->zone_id)) {
    $ticket->zone_id = $this->_deriveZoneId($ticket);
}
```

**Nota:** `_correctLocationFromAddress()` può impostare `$ticket->zone_id` direttamente; in quel caso `_deriveZoneId()` viene saltato perché `zone_id` non è più null.

---

## Task 4 — Chiamata in `v1update`

**File:** `app/Http/Controllers/TicketController.php`, metodo `v1update`

Stessa posizione: dopo geometry, prima di `_deriveZoneId()`. In `v1update` il ticket è già persistito (ha zone_id dal DB), ma la correzione deve poter sovrascrivere anche un zone_id esistente. Il metodo lo gestisce assegnando direttamente `$ticket->zone_id`.

Il codice attuale (righe ~280-303):

```php
if ($request->exists('location')) {
    $ticket->geometry = (DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$request->location[0]} {$request->location[1]})') as g;")))[0]->g;
}
// ... costruzione location_address ...
$ticket->location_address = $location_address;
if (is_null($ticket->zone_id)) {
    $ticket->zone_id = $this->_deriveZoneId($ticket);
}
```

Diventa:

```php
if ($request->exists('location')) {
    $ticket->geometry = (DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$request->location[0]} {$request->location[1]})') as g;")))[0]->g;
}
// ... costruzione location_address ...
$ticket->location_address = $location_address;
$this->_correctLocationFromAddress($ticket, $request);
if (is_null($ticket->zone_id)) {
    $ticket->zone_id = $this->_deriveZoneId($ticket);
}
```

---

## Task 5 — Test

**File:** `tests/Feature/TicketControllerDiscrepancyTest.php` (nuovo file)

Scenari da coprire con `RefreshDatabase` su `pap_test`:

| Scenario | Comportamento atteso |
|---|---|
| `address_id` presente → | nessuna correzione, coordinate originali mantenute |
| `city` assente → | nessuna correzione, fail-open |
| Nominatim senza risultati (mock `CurlServiceProvider`) → | fail-open, coordinate originali |
| Zone coincidono → | nessuna correzione |
| Zone differiscono → | geometry e zone_id sovrascritti con quelli del testo |
| Kill switch `ADDRESS_DISCREPANCY_CHECK_ENABLED=false` → | nessuna correzione |

**Mocking di CurlServiceProvider** nei test:
```php
$this->mock(\App\Providers\CurlServiceProvider::class, function ($mock) {
    $mock->shouldReceive('exec')
         ->andReturn(json_encode([['lat' => '44.1234', 'lon' => '9.8765']]));
});
```

---

## Sequenza di esecuzione

1. Task 1 — config/app.php + .env
2. Task 2 — metodo `_correctLocationFromAddress()`
3. Task 3 — chiamata in v1store
4. Task 4 — chiamata in v1update
5. Task 5 — test

**Commit:** `fix(oc:8113): ricalcolo coordinate/zona se discrepanza con indirizzo`

**Branch:** `feature/oc-8113-ricalcolo-automatico-coordinate-zona-discrepanza-indirizzo`

**PR verso:** `develop`
