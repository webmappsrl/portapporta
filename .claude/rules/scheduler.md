---
paths:
  - "app/Console/**"
---

# Trappole: scheduler

- `model:prune` richiede `--model` esplicito su `Laravel\Nova\Fields\Attachments\PendingAttachment`: senza il flag il model (che vive nel vendor) non viene mai trovato e i file Trix si accumulano (oc:8057)
- Lo scheduler in produzione gira via `/etc/cron.d/oc8843-pap-scheduler` con `docker exec` sul container: un job aggiunto al Kernel non parte finché quel cron è attivo e il container su (oc:8843)
