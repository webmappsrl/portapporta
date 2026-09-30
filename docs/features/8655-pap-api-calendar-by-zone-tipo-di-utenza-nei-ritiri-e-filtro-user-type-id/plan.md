> Ticket: oc:8655

# Piano — [pap] api calendar by zone: tipo di utenza nei ritiri e filtro user_type_id

**Obiettivo:** ogni ritiro di `GET /api/v2/c/{id}/calendar/z/{zone_id}` riporta `user_type_id`; il parametro opzionale `?user_type_id=` restituisce solo i ritiri di quell'utenza.

**Repo:** solo `portapporta` (feature custom, nessun submodule).

**Riferimenti:** [overview.md](overview.md) (approvata da Alessandro Peci il 29/09/2026), descrizione del ticket oc:8655 §6–§10. I numeri di riga si riferiscono a `22b847c`.

**Regole di esecuzione**

- Nessun `git add` / `git commit` / `git push` durante l'esecuzione: i commit sotto sono istruzioni testuali, da eseguire solo dopo il review-gate e l'approvazione esplicita della dev.
- Commit con scope `oc:8655` (`feat(oc:8655): ...`, `test(oc:8655): ...`, `docs(oc:8655): ...`).
- Tutti i comandi artisan girano nel container: `docker exec php_portapporta php artisan ...`.
- I test girano su `pap_test` (vedi `CLAUDE.md`, "Setup ambiente di test").

---

## Task 0 — Branch e ambiente

- [ ] Verificare di essere su `feature/oc-8655-calendar-zona-user-type` (già creato, contiene i commit dell'overview, PR #60 aperta verso `develop`):
  ```bash
  git branch --show-current
  git log --oneline -1 origin/develop   # deve essere 22b847c o successivo
  ```
- [ ] Se `origin/develop` è avanzato oltre `22b847c`, chiedere alla dev se riallineare il branch prima di iniziare (nessun rebase automatico).
- [ ] Pulire la cache di config e verificare che la suite di partenza sia verde:
  ```bash
  docker exec php_portapporta php artisan config:clear
  docker exec php_portapporta php artisan test --filter=CalendarControllerTest
  ```
  Atteso: tutti verdi. Se qualcosa è già rosso prima delle modifiche, fermarsi e segnalarlo.

## Task 1 — Test nuovi (prima del codice, devono fallire)

File: `tests/Feature/V2/CalendarControllerTest.php`, sezione `// Tests for V1IndexByZone Function` (riga 228), in coda ai test esistenti della sezione, prima di `// Helper Methods for V1IndexByZone Tests`.

- [ ] Aggiungere un helper privato che crea la seconda utenza con un calendario identico a quello di `setUp()` (stessa zona, stessa company, stesse date, un ritiro `weekly` domani):
  ```php
  private function createSecondUserTypeCalendar(): UserType
  {
      $secondUserType = UserType::factory()->create();
      $calendar = Calendar::factory()->create([
          'zone_id' => $this->zone->id,
          'company_id' => $this->company->id,
          'user_type_id' => $secondUserType->id,
          'start_date' => Carbon::today()->subDays(5),
          'stop_date' => Carbon::today()->addDays(30),
      ]);
      $item = CalendarItem::factory()->create([
          'calendar_id' => $calendar->id,
          'day_of_week' => Carbon::tomorrow()->dayOfWeek,
          'start_time' => '14:00',
          'stop_time' => '18:00',
          'frequency' => 'weekly',
      ]);
      $item->trashTypes()->attach($this->trashType->id);

      return $secondUserType;
  }
  ```
- [ ] `testV1IndexByZoneItemsHaveUserTypeId`: chiamata da domani a domani + 6, senza `user_type_id`. Nel giorno di domani 2 ritiri, uno con `user_type_id` = `$this->userType->id` e uno con l'id della seconda utenza.
  ```php
  /** @test */
  public function testV1IndexByZoneItemsHaveUserTypeId()
  {
      $secondUserType = $this->createSecondUserTypeCalendar();
      $start = Carbon::tomorrow()->format('Y-m-d');
      $stop = Carbon::tomorrow()->addDays(6)->format('Y-m-d');

      $response = $this->get(self::API_PREFIX . "{$this->company->id}/calendar/z/{$this->zone->id}?start_date={$start}&stop_date={$stop}");

      $response->assertStatus(200);
      $items = $response->json("data.calendar.{$start}");
      $this->assertCount(2, $items);
      $this->assertEqualsCanonicalizing(
          [$this->userType->id, $secondUserType->id],
          array_column($items, 'user_type_id')
      );
  }
  ```
- [ ] `testV1IndexByZoneFilterByUserType`: stessi dati, con `&user_type_id=<id seconda utenza>`. Nel giorno di domani 1 solo ritiro, con `user_type_id` = id della seconda utenza.
  ```php
  /** @test */
  public function testV1IndexByZoneFilterByUserType()
  {
      $secondUserType = $this->createSecondUserTypeCalendar();
      $start = Carbon::tomorrow()->format('Y-m-d');
      $stop = Carbon::tomorrow()->addDays(6)->format('Y-m-d');

      $response = $this->get(self::API_PREFIX . "{$this->company->id}/calendar/z/{$this->zone->id}?start_date={$start}&stop_date={$stop}&user_type_id={$secondUserType->id}");

      $response->assertStatus(200);
      $items = $response->json("data.calendar.{$start}");
      $this->assertCount(1, $items);
      $this->assertSame($secondUserType->id, $items[0]['user_type_id']);
  }
  ```
- [ ] `testV1IndexByZoneFilterByUnknownUserTypeReturnsError`: `?user_type_id=999999` → HTTP 400, `success` = false, messaggio `No calendars found for the specified zone.`
  ```php
  /** @test */
  public function testV1IndexByZoneFilterByUnknownUserTypeReturnsError()
  {
      $response = $this->get(self::API_PREFIX . "{$this->company->id}/calendar/z/{$this->zone->id}?user_type_id=999999");

      $this->assertErrorResponse(
          $response,
          self::responseMessages['noCalendarsForZone'],
          400
      );
  }
  ```
- [ ] Eseguire e verificare che falliscano per il motivo giusto (campo assente / filtro ignorato):
  ```bash
  docker exec php_portapporta php artisan test --filter=V1IndexByZone
  ```
  Atteso: `ItemsHaveUserTypeId` e `FilterByUserType` rossi, `FilterByUnknownUserType` rosso (oggi il filtro è ignorato → 200).

> Nota: i valori reali (`assertSame` su int) presuppongono che `user_type_id` arrivi come intero. Se arriva come stringa, applicare il cast `(int)` nella riga del Task 2 (vedi Rischi dell'overview) invece di allentare l'asserzione.

## Task 2 — Codice nel controller (solo 2 righe)

File: `app/Http/Controllers/CalendarController.php`.

- [ ] **2.1** In `createCalendar()` (riga 339), subito dopo la riga 367 `$p['stop_time'] = str_replace('0:00', '0', $item->stop_time);` aggiungere:
  ```php
  $p['user_type_id'] = $calendar->user_type_id;
  ```
  ⚠️ Non toccare la riga uguale alla riga 82, dentro `index()`.
- [ ] **2.2** In `V1IndexByZone`, nella query dei calendari, subito dopo la riga 207 `->where('zone_id', $zone_id)` aggiungere:
  ```php
  ->when($request->filled('user_type_id'), fn ($q) => $q->where('user_type_id', (int) $request->user_type_id))
  ```
  Il resto della query (righe 205–210, compreso `whereDate('start_date', ...)`) resta identico.
- [ ] Verificare con `git diff app/` che le righe aggiunte siano esattamente 2.

## Task 3 — Adeguare il test esistente (approvato da Alessandro Peci, domanda aperta 1)

File: `tests/Feature/V2/CalendarControllerTest.php`, `verifyCalendarItemSchedule` (righe 455–460).

- [ ] Aggiungere il controllo sul nuovo campo:
  ```php
  return $json->where('start_time', $this->calendarItem->start_time)
              ->where('stop_time', $this->calendarItem->stop_time)
              ->where('frequency', $this->calendarItem->frequency)
              ->where('user_type_id', $this->userType->id);
  ```
- [ ] Nessun `->etc()` aggiunto: la verifica del singolo ritiro resta rigorosa.

## Task 4 — Esecuzione test

- [ ] ```bash
  docker exec php_portapporta php artisan config:clear
  docker exec php_portapporta php artisan test --filter=CalendarControllerTest
  docker exec php_portapporta php artisan test
  ```
- [ ] Atteso: i 3 test nuovi verdi; `testV1Index`, `testV1IndexByZoneSuccessWithoutDates`, `testV1IndexByZoneSuccessWithDates` verdi grazie al Task 3; intera suite verde.
- [ ] Verifica manuale opzionale sul DB di sviluppo (zona 84 esiste in `pap`):
  ```bash
  curl -s "http://localhost:<DOCKER_SERVE_PORT>/api/v2/c/1/calendar/z/84?start_date=2025-07-14&stop_date=2025-07-15&user_type_id=6" | jq '.data.calendar'
  ```

## Task 5 — Documentazione

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-5-documentazione)

- [ ] `notes.md` (stessa cartella): deviazioni e decisioni della sessione (modifica al test approvata, domande 2–4 tolte e perché, stima lasciata a 4h, commit dell'overview spostato da `develop` al branch, migrazioni locali eseguite).
- [ ] `CLAUDE.md`, tabella "Feature disponibili": una riga per oc:8655 con moduli `app/Http/Controllers/CalendarController.php`, `tests/Feature/V2/CalendarControllerTest.php` e link a `docs/features/8655-.../` (il repo usa ancora la forma vecchia: nessun blocco in "Decisioni architetturali").

## Task 6 — Review-gate, commit e PR (solo dopo approvazione della dev)

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-6-review-gate-commit-e-pr)

- [ ] Riepilogo del diff da subagente isolato, poi approvazione esplicita della dev.
- [ ] Commit suggeriti (da eseguire solo dopo approvazione):
  ```
  test(oc:8655): test user_type_id e filtro in calendar by zone
  feat(oc:8655): add user_type_id to calendar by zone
  docs(oc:8655): notes e riga CLAUDE.md
  ```
- [ ] Push sullo stesso branch: la PR #60 verso `develop` si aggiorna. Aggiornarne titolo e descrizione (link al ticket oc:8655) con conferma della dev.
