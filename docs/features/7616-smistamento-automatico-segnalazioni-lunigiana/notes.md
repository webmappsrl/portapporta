> Ticket: oc:7616

# Notes — Smistamento automatico segnalazioni Lunigiana

## Deviazioni dal piano
- Aggiunto metodo privato `forwardToLunigiana()` in `TicketController` (non previsto nelle note di sviluppo originali) per evitare duplicazione del blocco logico nei 3 punti di invio.
- Il metodo `destroy()` del controller è vuoto: la cancellazione avviene via `v1update()` con status `Deleted`, non tramite `destroy()` come indicato nelle note di sviluppo originali.
- La colonna `zone_id` su `tickets` è gestita direttamente sul modello (non via `address->zone_id` come ipotizzato inizialmente), grazie alla migrazione `oc_7612` già presente.

## Bug trovati
- Nessuno.

## Decisioni
- `isLunigianaZone()` sul modello `Ticket` non include il check `config('lunigiana.enabled')`: quel controllo è responsabilità del controller, il metodo risponde solo alla domanda "è zona Lunigiana?".
- Il `Log::warning` per `zone_id` null è scoped a `company_id === config('lunigiana.company_id')` per evitare rumore di log da company non ERSU.
- Multi-destinatario su `lunigiana.email` supportato con `explode(',', ...)`, coerente con il pattern già usato per `company->ticket_email`.

## Follow-up
- Confermare lista definitiva `zone_id` Lunigiana con ERSU e aggiornare `config/lunigiana.php` prima del deploy in produzione.
- Verificare che la migrazione `oc_7612` sia in produzione prima del deploy di questa feature.
