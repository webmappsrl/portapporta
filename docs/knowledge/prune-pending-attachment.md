# Pulizia dei PendingAttachment di Nova/Trix

## Come funziona oggi

- Il Kernel schedula `model:prune` **con `--model` esplicito** su `Laravel\Nova\Fields\Attachments\PendingAttachment` (`app/Console/Kernel.php`): senza il flag, i file temporanei caricati nell'editor Trix non vengono mai eliminati.
- `PendingAttachment` usa il trait `Prunable` (non `MassPrunable`), verificato nel vendor: il prune è per-record e il model elimina anche il file fisico dallo storage.
- Finestra di pruning: `created_at <= now()->subDays(1)` — hard-coded nel model vendor (`prunable()`).
- Lo scheduler che esegue il comando gira in produzione solo dal 22/10/2026 (vedi [8843-runtime-php-docker-prod-dev.md](8843-runtime-php-docker-prod-dev.md)): prima non era configurato e il prune non è mai partito.

## Perché così

- `--model` esplicito (oc:8057): `model:prune` senza argomenti scandisce solo `app/Models`, e `PendingAttachment` vive nel vendor — non verrebbe mai trovato.
- **Prima del primo run in produzione va fatto un backup spot del filesystem**: il primo prune elimina tutti i file accumulati (oc:8057). Il primo run reale è avvenuto con lo snapshot Hetzner del 22/10/2026 già preso (oc:8843).

## Come ci siamo arrivati

- Cantiere: oc:8057 (fix scheduler prune). L'attivazione effettiva dello scheduler in produzione è di oc:8843.
