---
paths:
  - "app/Models/**"
  - "app/Http/Controllers/**"
---

# Trappole: ticket, zone e geocoding

- Nelle query spaziali su geometry pre-save usa sempre `ST_SetSRID(?::geometry, 4326)`: senza, `ST_Contains` ritorna `false` in silenzio (SRID 0 vs 4326) (oc:8099)
- `Zone::findByPoint` ordina per `ST_Area ASC` perché le zone ERSU si sovrappongono (27 coppie al momento di oc:8099): togliere l'ordinamento rende la zona non deterministica (oc:8099)
- `sendLunigianaEmails` resta `protected`, non `private`: serve al mock Mockery nei test del fallback (oc:8111)
- Non usare `curlRequest()` verso Nominatim: lo User-Agent di `CurlServiceProvider` è bloccato — usa `Http::withHeaders(['User-Agent' => 'portapporta (+https://portapporta.webmapp.it)'])` e catcha `ConnectionException`, o il fail-open diventa un 500 (oc:8113)
- Il separatore ` — ` in `location_address` è parsato con `explode(' — ', …, 2)` in `app/Nova/Ticket.php` e in `emails/tickets/created.blade.php`: cambiarlo rompe entrambi (oc:8054)
