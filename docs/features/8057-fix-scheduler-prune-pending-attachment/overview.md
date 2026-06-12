> Ticket: oc:8057

# [ersu] Fix scheduler model:prune — PendingAttachment Nova/Trix

## Cosa cambia
Lo scheduler notturno `model:prune` viene configurato per eliminare anche le righe in `nova_pending_field_attachments` e i file fisici associati caricati nell'editor Trix, che attualmente non vengono prunati.

## Perché
Il comando `model:prune` gira ogni notte alle 00:00 ma senza il flag `--model` su `PendingAttachment` di Nova. Nel tempo i file caricati nell'editor (immagini, PDF) si accumulano in `storage/app/public/` e le relative righe restano in `nova_pending_field_attachments`, riducendo lo spazio disco disponibile fino a causare fallimenti negli upload.

## Requisiti
- [ ] Aggiungere `--model` con `PendingAttachment::class` al comando `model:prune` nello scheduler
- [ ] Il prune deve avvenire ogni notte (comportamento già presente, da mantenere)

## Rischi
- **Basso** — `PendingAttachment` è un modello Nova con logica `MassPrunable` già implementata dalla libreria; il flag `--model` la attiva senza modificare il comportamento delle altre prunable model già gestite dal comando senza flag.
- **Nessun impatto sui file già inviati** — il prune elimina solo i record in stato "pending" (file caricati ma non ancora associati a un messaggio inviato); gli attachment già allegati alle risposte non sono toccati.

## Out of scope
- Validazione tipo/dimensione file nell'upload Trix
- Assert MIME negli unit test esistenti
- Gestione invio parziale su selezione multi-ticket
- Configurazione `max_file_uploads` PHP
- Dipendenza `APP_URL` per immagini inline

## Moduli toccati
- `app/Console/Kernel.php` — aggiunta opzione `--model` al comando `model:prune`
