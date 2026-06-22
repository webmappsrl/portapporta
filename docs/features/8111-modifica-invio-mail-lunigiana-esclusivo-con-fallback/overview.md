> Ticket: oc:8111

# Modifica invio mail Lunigiana: esclusivo con fallback su mail company

## Cosa cambia

Per i ticket nelle zone Lunigiana di ERSU, le notifiche email vengono ora inviate **esclusivamente** ai destinatari Lunigiana configurati, senza più duplicare la notifica alla mail di default della company. Se anche un solo destinatario Lunigiana fallisce, l'invio cade in fallback sulla mail company. Tutta la logica di routing è centralizzata in un nuovo metodo privato `sendTicketNotification()`. Stessa logica per `TicketCreated` e `TicketDeleted`.

## Perché

Attualmente la notifica viene inviata sia alla company sia a Lunigiana, causando duplicazione delle email per i ticket Lunigiana. Il cliente vuole un comportamento esclusivo (solo Lunigiana), mantenendo la company come rete di sicurezza in caso di errore.

## Requisiti

- [ ] Introdurre il metodo privato `sendTicketNotification(Ticket, Company, Mailable)` che centralizza tutta la logica di routing email, sostituendo il doppio blocco attuale (company + `forwardToLunigiana`)
- [ ] Per ticket in zona Lunigiana con forwarding abilitato: inviare la mail **solo** ai destinatari Lunigiana, non alla company
- [ ] Se anche un solo destinatario Lunigiana solleva un'eccezione durante l'invio: loggare a livello `error` e inviare la mail a tutti i destinatari company come fallback
- [ ] Quando `LUNIGIANA_FORWARD_ENABLED=false`: inviare la mail alla company (graceful degradation); loggare a `Log::info` (non `warning`) perché è una configurazione intenzionale
- [ ] Quando il ticket è ERSU ma `zone_id` non è derivabile: inviare la mail alla company come fallback sicuro
- [ ] I 3 call site (`store`, `v1store`, `v1update`) chiamano solo `sendTicketNotification` — nessuna logica di routing fuori dal metodo
- [ ] Il Mailable viene istanziato dal call site e passato già costruito; aggiungere un commento nel metodo che avverte di non riusare la stessa istanza tra invii multipli
- [ ] La logica si applica sia a `TicketCreated` (`store`, `v1store`) sia a `TicketDeleted` (`v1update`)
- [ ] Aggiungere test PHPUnit per: (1) zona Lunigiana → solo mail Lunigiana, (2) fallback su company quando Lunigiana fallisce, (3) forwarding disabilitato → mail company — sia per `store()` che per `v1store()`

## Rischi

- **SMTP sincrono**: `Mail::send()` è blocking — se lo SMTP Lunigiana è lento, i worker PHP-FPM possono esaurirsi sotto carico. Lasciato come follow-up documentato (richiederebbe refactor verso queue)
- **Mailable mutabile**: la stessa istanza passata a più `send` consecutivi può ereditare stato interno dal send precedente. Mitigato documentando con commento che il Mailable non va riusato
- **Fallback parziale nel fallback**: se la company ha più destinatari e uno fallisce nel fallback, la notifica è parzialmente consegnata — accettabile, comportamento pre-esistente
- **Rollback**: il rollback del codice richiede di ripristinare il blocco `if ($company->ticket_email)` + `forwardToLunigiana` nei call site — procedura documentata in `notes.md`

## Out of scope

- Invio asincrono via queue (follow-up documentato)
- Modifica alla logica di determinazione zona Lunigiana (`isLunigianaZone()`)
- Modifica alla logica di derivazione `zone_id`
- Generalizzazione della logica a company diverse da ERSU
- Notifiche push o canali diversi dall'email

## Moduli toccati

- `app/Http/Controllers/TicketController.php` — nuovo metodo `sendTicketNotification()`; rimozione del doppio blocco email da `store()`, `v1store()`, `v1update()`; eventuale rimozione/deprecazione di `forwardToLunigiana()`
- `tests/Feature/V2/TicketControllerTest.php` — nuovi test per i 3 scenari Lunigiana su `store()` e `v1store()`
