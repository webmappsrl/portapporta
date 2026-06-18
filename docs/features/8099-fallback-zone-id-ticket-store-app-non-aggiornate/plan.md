> Ticket: oc:8099

# Plan — [ersu] Fallback zone_id in ticket store per app non aggiornate

## Task 1 — `Zone::findByPoint()` su `app/Models/Zone.php`

Aggiungere `use Illuminate\Support\Facades\DB;` agli import (già presente nel file).

Aggiungere il metodo statico alla classe `Zone`:

```php
public static function findByPoint(string $geometry, int $companyId): ?self
{
    $result = DB::selectOne(
        'SELECT id FROM zones WHERE company_id = ? AND geometry IS NOT NULL AND ST_Contains(geometry::geometry, ST_SetSRID(?::geometry, 4326)) ORDER BY ST_Area(geometry::geometry) ASC LIMIT 1',
        [$companyId, $geometry]
    );

    return $result ? self::find($result->id) : null;
}
```

**Nota SRID:** `ST_SetSRID(?::geometry, 4326)` gestisce sia geometrie pre-save (SRID=0, da `ST_GeomFromText` senza argomento) sia post-save (SRID=4326, da Eloquent).

---

## Task 2 — Helper privato `_deriveZoneId()` su `TicketController`

Aggiungere i due import mancanti in testa al file:
```php
use App\Models\Address;
use App\Models\Zone;
```

Aggiungere il metodo privato alla classe `TicketController`:

```php
private function _deriveZoneId(Ticket $ticket): ?int
{
    if ($ticket->address_id) {
        $zoneId = Address::find($ticket->address_id)?->zone_id;
        if ($zoneId) {
            return $zoneId;
        }
    }

    if ($ticket->geometry) {
        return Zone::findByPoint($ticket->geometry, $ticket->company_id)?->id;
    }

    return null;
}
```

---

## Task 3 — Derivazione in `store()`

In `TicketController::store()`, inserire il blocco di derivazione **dopo** l'assegnazione di tutti i campi da request e **prima** di `$res = $ticket->save()` (attualmente riga ~125):

```php
if (is_null($ticket->zone_id)) {
    $ticket->zone_id = $this->_deriveZoneId($ticket);
}
```

---

## Task 4 — Derivazione in `v1store()`

In `TicketController::v1store()`, inserire lo stesso blocco **dopo** l'assegnazione di `$ticket->location_address` e **prima** di `$res = $ticket->save()` (attualmente riga ~217):

```php
if (is_null($ticket->zone_id)) {
    $ticket->zone_id = $this->_deriveZoneId($ticket);
}
```

---

## Task 5 — Derivazione in `v1update()`

In `TicketController::v1update()`, inserire lo stesso blocco **dopo** il blocco di processing dei campi della request e **prima** di `$ticket->save()`:

```php
if (is_null($ticket->zone_id)) {
    $ticket->zone_id = $this->_deriveZoneId($ticket);
}
```

---

## Task 6 — Aggiornamento log in `forwardToLunigiana()`

Modificare il messaggio di warning alla riga 348 da:
```php
Log::warning('ERSU ticket has no zone_id, Lunigiana forward skipped', ['ticket_id' => $ticket->id]);
```
a:
```php
Log::warning('ERSU ticket zone_id derivation failed, Lunigiana forward skipped', ['ticket_id' => $ticket->id]);
```

---

## Task 7 — Test Unit: `tests/Unit/ZoneFindByPointTest.php`

Creare il file. Il test usa `RefreshDatabase` e fixture sintetiche. La `ZoneFactory` esistente usa già una geometria MultiPolygon sintetica intorno a `lon 10-11, lat 45-46` — costruire i punti test coerentemente.

Casi da coprire:
1. **Punto dentro la zona** — `POINT(10.5 45.5)` (dentro il multipolygon della factory), company corretta → ritorna la zona
2. **Punto fuori da tutte le zone** — `POINT(0 0)`, company corretta → ritorna `null`
3. **Company_id errato** — punto dentro, company sbagliata → ritorna `null`
4. **Zone sovrapposte** — due zone con geometry diversa che si sovrappongono, punto nell'area comune → ritorna quella con area minore
5. **SRID=0 pre-save** — geometria costruita con `ST_GeomFromText('POINT(10.5 45.5)')` (SRID=0) → ritorna la zona (verifica che `ST_SetSRID` funzioni)
6. **SRID=4326 post-save** — geometria EWKB letta da un record Ticket salvato in DB → ritorna la zona

Per il caso 5, costruire la geometria SRID=0 via:
```php
$geomSrid0 = DB::selectOne("SELECT ST_GeomFromText('POINT(10.5 45.5)') AS g")->g;
```

Per il caso 6, salvare un ticket con `geometry` standard e usare `$ticket->fresh()->geometry`.

---

## Task 8 — Test Feature: `tests/Feature/Api/ApiTicketTest.php`

Aggiungere i seguenti test alla classe esistente (stile `RefreshDatabase` + `WithoutMiddleware` già presenti):

**Test A — `store()` senza zone_id, con location dentro una zona:**
```
POST api/c/{company_id}/ticket
body: { ticket_type: 'info', location: [45.5, 10.5] }
```
- Creare una zona ERSU con geometry che contenga il punto
- Asserire che il ticket in DB abbia `zone_id` valorizzato con l'ID della zona

**Test B — `v1store()` senza zone_id, con location dentro una zona:**
```
POST api/v1/c/{company_id}/ticket
body: { ticket_type: 'info', location: [10.5, 45.5] }
```
- Stessa logica del Test A (v1store usa lon/lat nell'ordine inverso rispetto a store)

**Test C — `store()` senza zone_id e location fuori da tutte le zone:**
- Asserire che il ticket in DB abbia `zone_id = null` (fail-open)

**Test D — `store()` con address_id che ha zone_id:**
- Creare un Address con `zone_id` valorizzato
- Fare store senza `zone_id` ma con `address_id`
- Asserire che `ticket->zone_id` corrisponda a `address->zone_id`

---

## Commit

```
fix(oc:8099): fallback zone_id pre-save per app non aggiornate
```

Includere: `app/Models/Zone.php`, `app/Http/Controllers/TicketController.php`, `tests/Unit/ZoneFindByPointTest.php`, `tests/Feature/Api/ApiTicketTest.php`, `docs/features/8099-fallback-zone-id-ticket-store-app-non-aggiornate/`.

PR verso `develop`.
