> Ticket: oc:8099

# Notes — [ersu] Fallback zone_id in ticket store per app non aggiornate

## Deviazioni dal piano

- La derivazione è stata applicata anche a `v1update()`, non prevista nelle note del ticket originale ma approvata esplicitamente durante la Fase 2 del workflow.

## Bug trovati

- SRID mismatch pre/post-save: `ST_GeomFromText` senza SRID genera geometria con SRID=0, mentre le zone ERSU sono SRID=4326. Risolto con `ST_SetSRID(?::geometry, 4326)` in `Zone::findByPoint`. Scoperto durante la Challenge (Fase 3).

## Decisioni

- Derivazione pre-save (non post-save come suggerito dal ticket): evita un secondo write al DB mantenendo lo stesso risultato. La geometry e l'address_id sono già impostati sull'oggetto prima di `save()`.
- `Zone::findByPoint` su model Zone (non metodo privato nel controller): scelta di riusabilità e testabilità, primo uso di raw SQL nel layer model — convenzione da riportare in CLAUDE.md.
- `ORDER BY ST_Area(geometry::geometry) ASC`: 27 coppie di zone ERSU si sovrappongono in produzione (verificato), quindi l'ordinamento per area è necessario per determinismo.
- Scope esteso a `v1update()` per coprire ticket pre-fix con `zone_id = null` che vengono aggiornati dopo il deploy.

## Follow-up

- `Zone::findByPoint` fa 2 query (SELECT id + self::find) — il chiamante usa solo `->id`. Candidato a refactoring in `?int` in un ciclo successivo.
- `config/lunigiana.php` contiene ancora il TODO `// TODO: confermare lista definitiva zone_id con ERSU` — richiedere conferma ufficiale da ERSU e tracciare in un ticket dedicato.
