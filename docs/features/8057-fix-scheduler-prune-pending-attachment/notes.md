> Ticket: oc:8057

# Notes — [ersu] Fix scheduler model:prune — PendingAttachment Nova/Trix

## Deviazioni dal piano
Nessuna.

## Bug trovati
Nessuno aggiuntivo rispetto a quanto descritto nel ticket.

## Decisioni
- Verificato il sorgente `vendor/laravel/nova/src/Fields/Attachments/PendingAttachment.php`: il modello usa il trait `Prunable` (non `MassPrunable`), che trigghera `pruning()` per ogni record prima della DELETE. Il metodo `pruning()` chiama `Storage::disk($this->disk)->delete($this->attachment)` — i file fisici vengono eliminati correttamente insieme ai record DB.
- Finestra di pruning: `created_at <= now()->subDays(1)`. Comportamento atteso e coerente con il flusso operativo (upload e invio immediato).
- Il finding della challenge su `MassPrunable` è stato refutato dalla lettura del sorgente vendor.

## Follow-up
- Test automatico che verifichi l'esecuzione del prune (non incluso in questo ciclo per scope minimo)
- Checklist pre-deploy produzione: run manuale in staging + backup spot del filesystem prima del primo run (eliminerà i file accumulati fino al deploy)
- Restano aperti i finding 2-6 del ticket (validazione upload, test MIME, multi-ticket, `max_file_uploads`, `APP_URL`) — esclusi dallo scope di questo ciclo
