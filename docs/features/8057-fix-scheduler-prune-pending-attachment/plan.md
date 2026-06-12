> Ticket: oc:8057

# Plan — [ersu] Fix scheduler model:prune — PendingAttachment Nova/Trix

## Repo coinvolti
- **portapporta** (repo principale) — Custom, nessun submodule

## Step 1 — Fix scheduler

**File:** `app/Console/Kernel.php`

Sostituire la riga:
```php
$schedule->command('model:prune')->daily();
```
con:
```php
$schedule->command('model:prune', [
    '--model' => [\Laravel\Nova\Fields\Attachments\PendingAttachment::class],
])->daily();
```

**Nota:** `PendingAttachment` usa il trait `Prunable` (non `MassPrunable`) con:
- `prunable()` → scope `created_at <= now()->subDays(1)`
- `pruning()` → `Storage::disk($this->disk)->delete($this->attachment)` (elimina il file fisico prima del DELETE DB)

## Step 2 — Documentazione

- Creare `docs/features/8057-fix-scheduler-prune-pending-attachment/notes.md`
- Aggiornare `CLAUDE.md` (sezione "Feature disponibili" e "Decisioni architetturali")

## Commit

```
fix(oc:8057): add --model PendingAttachment to model:prune scheduler
```

## Checklist pre-deploy produzione
- [ ] Eseguire `php artisan model:prune --model="Laravel\Nova\Fields\Attachments\PendingAttachment"` manualmente in staging e verificare che i record `nova_pending_field_attachments` vecchi vengano eliminati
- [ ] Verificare che i file fisici corrispondenti siano rimossi da `storage/app/public/`
- [ ] Fare un backup spot del filesystem prima del primo run in produzione (il primo run eliminerà i file accumulati fino ad oggi)
