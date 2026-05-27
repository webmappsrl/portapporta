> Ticket: oc:7616

# Smistamento automatico segnalazioni Lunigiana

## Cosa cambia
Ogni email inviata a ERSU (`urp@ersu.it`) per ticket appartenenti a zone Lunigiana viene automaticamente duplicata verso `urp@lunigianaambiente.it` al momento dell'invio, senza intervento manuale.

## Perché
Gli operatori URP ERSU inoltravano manualmente le segnalazioni Lunigiana ogni mattina. Il processo era inefficiente e soggetto a dimenticanze. L'automazione elimina il passaggio manuale e garantisce l'inoltro in tempo reale.

## Requisiti
- [ ] Le segnalazioni vengono identificate tramite `zone_id` direttamente sul modello `Ticket` (dipende da migrazione `oc_7612`)
- [ ] L'inoltro avviene nello stesso momento in cui viene inviata l'email a `company.ticket_email`
- [ ] Sono coperti tutti gli eventi email attuali: creazione ticket (`store`, `v1store`) e cancellazione (`v1update` con status `Deleted`)
- [ ] Se il ticket ERSU ha `zone_id = null`, viene loggato un warning e non viene effettuato nessun inoltro
- [ ] Se l'invio a Lunigiana fallisce, viene loggato un warning e il flusso principale non viene interrotto
- [ ] La feature è disabilitabile via env senza deploy (`LUNIGIANA_FORWARD_ENABLED=false`)
- [ ] La lista delle zone è configurabile in `config/lunigiana.php` senza modificare il codice

## Rischi
- **`zone_id` null silenzioso:** ticket inviati da client che non mandano `zone_id` non vengono inoltrati. Mitigato con `Log::warning` esplicito per i ticket ERSU con `zone_id = null`.
- **Lista zone provvisoria:** la lista `zone_id` Lunigiana è da confermare definitivamente con ERSU prima del deploy in produzione.
- **Dipendenza da `oc_7612`:** la colonna `zone_id` su `tickets` deve essere in produzione prima del deploy di questa feature. Se mancante, il forwarding è silenziosamente disabilitato (null → nessun inoltro).

## Out of scope
- Retry automatico in caso di fallimento invio email Lunigiana
- Generalizzazione a altre company (feature hard-coded per ERSU)
- Interfaccia admin per gestire la lista zone da Nova
- Copertura ticket storici (nessuna ri-spedizione email pregresse)

## Moduli toccati
- `config/lunigiana.php` — nuovo file di configurazione
- `app/Models/Ticket.php` — aggiunta metodo `isLunigianaZone(): bool`
- `app/Http/Controllers/TicketController.php` — aggiunta blocco forwarding in `store()`, `v1store()`, `v1update()`
