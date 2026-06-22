> Ticket: oc:8113

# Ricalcolo automatico coordinate/zona se discrepanza con indirizzo

## Cosa cambia

Quando il backend riceve un ticket da `v1store` o `v1update` senza `address_id` (flusso custom da mappa), controlla se le coordinate ricevute e il testo dell'indirizzo scritto dall'utente appartengono alla stessa zona. Se appartengono a zone diverse, sovrascrive le coordinate (geometry) e la zona (zone_id) con quelle derivate dal forward geocoding del testo indirizzo.

## Perché

L'app consente all'utente di selezionare un punto sulla mappa (che georeferenzia automaticamente le coordinate e pre-compila l'indirizzo), ma poi permette di modificare il testo dell'indirizzo a mano. Se l'utente cambia l'indirizzo testo dopo aver cliccato sulla mappa, le coordinate restano quelle del click originale mentre il testo riflette una posizione diversa. Il risultato è un ticket inviato agli operatori ERSU con zona e comune errati (come nel caso segnalazione #51467: coordinate Pietrasanta, indirizzo Fosdinovo).

## Requisiti

- [ ] Il controllo è abilitato tramite variabile d'ambiente `ADDRESS_DISCREPANCY_CHECK_ENABLED` (default `true`); se `false` il ticket viene accettato senza controllo (kill switch, analogo a `LUNIGIANA_FORWARD_ENABLED`)
- [ ] Il controllo si attiva solo in `v1store` e `v1update`, non in `store()`
- [ ] Il controllo si attiva solo quando `$ticket->address_id` è null (valore sul modello Eloquent idratato dal DB, non presenza nel payload — gestisce correttamente le patch parziali di `v1update`)
- [ ] La query Nominatim usa i campi strutturati: `street={address}+{house_number}&city={city}&format=json&limit=1&countrycodes=it` — nessuna concatenazione con separatori em-dash
- [ ] Se `city` è assente nel payload, il controllo viene saltato (query troppo vaga)
- [ ] Si ricava la zona per le coordinate forward geocodificate (`Zone::findByPoint`)
- [ ] Si ricava la zona per le coordinate ricevute dall'app (`Zone::findByPoint`)
- [ ] Se le due zone differiscono e il forward geocoding ha avuto successo, geometry e zone_id vengono sostituiti con i valori derivati dal testo
- [ ] In caso di fallimento del geocoding (Nominatim irraggiungibile, senza risultati, o `city` assente): fail-open, il ticket viene accettato con le coordinate originali
- [ ] La correzione viene loggata con `Log::warning` includendo coordinate originali, coordinate corrette e zone coinvolte
- [ ] Il codice include un `// TODO:` esplicito che indica che il controllo potrà essere rimosso una volta che l'app avrà il fix lato frontend e tutti gli utenti avranno aggiornato
- [ ] La correzione avviene pre-save (prima del `save()` e prima dell'invio email), seguendo la convenzione di oc:8099

## Rischi

- **Nominatim può restituire risultati ambigui o plausibili ma errati** per indirizzi omonimi (es. "Via Roma" presente in più comuni). Mitigazione: fail-open — se le zone coincidono la correzione non scatta; se scatta con dati sbagliati il `Log::warning` è l'unico segnale.
- **Latenza aggiuntiva sincrona + timeout infinito**: `CurlServiceProvider` ha `CURLOPT_TIMEOUT => 0`. Se Nominatim non risponde, il worker PHP-FPM si blocca. Accettato come compromesso temporaneo — follow-up per rendere il check asincrono una volta che il fix frontend è live e il kill switch può essere disattivato.
- **Nominatim rate limiting (1 req/sec)**: il progetto non usa un'istanza self-hosted né una chiave API. Sotto carico potrebbe essere throttolato (HTTP 429). `curlRequest` non controlla il codice HTTP di risposta, quindi un 429 viene trattato come fail-open silenzioso.
- **False positive su click mappa senza modifica testo**: il controllo gira anche quando coordinate e testo sono consistenti (nessuna modifica utente). Overhead di una chiamata Nominatim inutile — nessun danno funzionale grazie al fail-open.

## Out of scope

- Fix lato app (frontend) — rimandato a un ciclo successivo
- Rendering asincrono della correzione (queue job) — rimandato a follow-up
- Applicazione a `store()` (non-v1) — non affetto: quel metodo costruisce `location_address` da reverse geocoding delle coordinate, senza testo editabile
- Applicazione quando `address_id` è valorizzato — la zona è già corretta per costruzione dall'indirizzo salvato

## Moduli toccati

- `app/Http/Controllers/TicketController.php` — aggiunta logica di discrepancy check + forward geocoding in `v1store` e `v1update`
