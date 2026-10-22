---
paths:
  - "tests/**"
---

# Trappole: test

- `APP_KEY` deve essere identica in `.env` e `.env.testing`: chiavi diverse causano `InvalidSignatureException` durante `RefreshDatabase` (serializable-closure firma con la chiave) (oc:7991)
- Il throttle `api` è disattivo in ambiente `testing`: un test di rate limiting senza `withMiddleware` esplicito passa sempre (oc:7991)
- In `tests/Feature/V2/CalendarControllerTest.php`, `verifyCalendarItemSchedule` confronta il ritiro senza `->etc()`: ogni campo aggiunto a un ritiro va aggiunto anche lì, o i test esistenti falliscono con «Unexpected properties» (oc:8655)
- Le asserzioni su etichette Nova tradotte usano `__('Name')` / `__('Phone')`: il locale del progetto è `it` (oc:8058)
