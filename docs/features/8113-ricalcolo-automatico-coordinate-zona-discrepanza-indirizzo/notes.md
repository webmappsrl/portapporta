> Ticket: oc:8113

# Note implementative — Ricalcolo automatico coordinate/zona se discrepanza con indirizzo

## Fix emersi dalla review

### User-Agent bloccato da Nominatim (`PostmanRuntime/7.29.2`)
Il `CurlServiceProvider` invia `User-Agent: PostmanRuntime/7.29.2`. Nominatim blocca esplicitamente questo agent (risponde "Access denied" in HTML → `json_decode` → `null` → fail-open silenzioso). Tutte le chiamate reali dal server fallivano.

Soluzione: sostituire `curlRequest()` in `_correctLocationFromAddress()` con l'`Http` facade di Laravel (Guzzle), che permette di impostare un User-Agent conforme alla Nominatim usage policy: `portapporta (+https://portapporta.webmapp.it)`. `CurlServiceProvider` non è stato modificato (continuava in `store()` e altrove senza problemi).

### ConnectionException non gestita (fail-open rotto)
La vecchia `curlRequest()` aveva un try-catch interno che catturava qualsiasi eccezione e restituiva `null`. Passando a `Http::get()`, se Nominatim è irraggiungibile Guzzle lancia `ConnectionException` non gestita → 500 per l'app → ticket non creato. Corretto con un try-catch esplicito attorno alla chiamata Http.

Aggiunto anche il test `testNominatimUnreachableIsFailOpen` che verifica il comportamento.

## Deviazioni dal piano

### Log context ampliato rispetto a plan.md

Il `Log::warning` nel piano originale includeva `original_zone_id`, `corrected_zone_id`, `corrected_lat`, `corrected_lon`, `address`. In implementazione sono stati aggiunti:
- `ticket_id` — identificativo ticket (null pre-save, valorizzato in v1update)
- `original_lat`, `original_lon` — coordinate originali decodificate via `_geometryToLatLon()` per facilità di debug

Stessa aggiunta per `Log::info` (Nominatim senza risultati): aggiunto `ticket_id`.

### `ticket_id` è null nei log di `v1store`

`_correctLocationFromAddress()` viene chiamato prima di `$ticket->save()`, quindi `$ticket->id` è null durante la creazione. In `v1update` il ticket è già persistito e `ticket_id` è valorizzato. Comportamento atteso, non un bug.

## Diagnosi test manuale ticket #1238

Il ticket #1238 (address: "Via Fratelli Rosselli, 25 — Seravezza", zone_id=95 Pietrasanta) ha triggherato il check ma il fail-open ha mantenuto le coordinate originali. Il log mostra:

```
local.INFO: Address discrepancy check: Nominatim returned no results, keeping original coordinates
  {"ticket_id":null,"address":"Via Fratelli Rosselli 25 — Seravezza"}
```

Causa: Nominatim ha risposto con array vuoto `[]` per quella specifica richiesta (probabilmente rate limiting — erano stati effettuati 2 check nei 10 minuti precedenti). Il comportamento fail-open è corretto.

Verifica post-hoc: la stessa query Nominatim restituisce ora `lat=43.9740197, lon=10.2006714` (zona 91 - Seravezza "Piana e centro"), confermando che le coordinate sarebbero state corrette se Nominatim non avesse avuto il momentaneo problema.
