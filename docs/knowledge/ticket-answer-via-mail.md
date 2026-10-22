# TicketAnswerViaMail — Rich Text, Immagini Inline, Allegati

## Contesto

L'azione Nova `TicketAnswerViaMail` permetteva agli operatori di rispondere ai ticket via email con una semplice Textarea (testo piatto). È stata trasformata in uno strumento di comunicazione completo:

- **Editor WYSIWYG** (Trix) con formattazione testo
- **Immagini inline** nel corpo dell'email (CID embedded, compatibili con tutti i client email)
- **Allegati arbitrari** (PDF, Word, qualsiasi formato) come attachment email veri

---

## File modificati

| File | Tipo di modifica |
|------|-----------------|
| `app/Nova/Actions/TicketAnswerViaMail.php` | `Textarea` → `Trix`, bug fix `save()` |
| `app/Nova/Ticket.php` | Campo Trix nascosto per l'upload, CSS toolbar |
| `app/Mail/TicketAnswer.php` | Riscritto con `build()`, CID embedding, allegati |
| `resources/views/emails/tickets/answer.blade.php` | `{{ $answer }}` → `{!! $answer !!}` |
| `app/Providers/NovaServiceProvider.php` | Registrazione CSS custom |
| `public/css/nova-custom.css` | Toolbar Trix su due righe |
| `docker/configs/phpfpm/php.ini` | Aggiunto `upload_max_filesize = 1024M`, `post_max_size = 1024M` |

---

## Come funziona

### Upload file in Trix (Nova Action)

Nova's `FieldAttachmentController` gestisce gli upload Trix cercando il campo con `withFiles = true` tra i campi del **Resource**, non dell'Action. Per questo motivo in `app/Nova/Ticket.php` è stato aggiunto un campo Trix nascosto con un callback `attach()` custom:

```php
Trix::make('Answer', 'answer')
    ->withFiles('public')
    ->attach(function (Request $request) { ... })
    ->hideFromIndex()->hideFromDetail()
    ->hideWhenCreating()->hideWhenUpdating()
    ->fillUsing(fn () => null);
```

Il `fillUsing(fn () => null)` impedisce qualsiasi modifica al modello Ticket. Il campo è puramente un hook per il controller di upload.

### Mailable: CID embedding e allegati

Il Mailable usa il metodo `build()` (vecchio stile, pienamente supportato in Laravel 9) per avere accesso a `withSymfonyMessage()`.

**Flusso:**

1. `processHtml($trixHtml)` — un singolo passaggio DOM che:
   - Trova le `<figure data-trix-attachment>` con file non-immagine → li converte in email attachment e li rimuove dal body
   - Trova i tag `<img>` → sostituisce `src` con `cid:img-N@portapporta` e raccoglie i path locali

2. L'HTML processato (con CID) viene passato alla view come `$answer`

3. `withSymfonyMessage()` aggiunge i `DataPart` inline con i CID corrispondenti

> **Nota timing critica**: `withSymfonyMessage` viene eseguito **prima** che `addContent()` renderizzi la view. Quindi il callback non può leggere/modificare l'HTML body (è ancora vuoto). I CID vanno iniettati nell'HTML **prima** di passarlo alla view, non nel callback.

### Conversione URL → path locale

`srcToStoragePath()` converte gli src delle immagini Trix in path filesystem:

```
/storage/foo/bar.jpg        → storage_path('app/public/foo/bar.jpg')
https://app.example.com/... → storage_path('app/public/...')
```

Dipende da `APP_URL` nel `.env` per la versione assoluta.

### Bug fix

Corretto un bug preesistente: `$ticket->is_read = true` veniva impostato ma `$ticket->save()` non veniva mai chiamato.

---

## Produzione — checklist

### Comandi da eseguire sul server dopo il deploy

```bash
# 1. Symlink storage (se non già presente)
php artisan storage:link

# 2. Verifica migration Nova (deve essere già eseguita)
php artisan migrate --status | grep field_attachments
```

### PHP limits

I limiti `upload_max_filesize = 1024M` e `post_max_size = 1024M` sono stati aggiunti a `docker/configs/phpfpm/php.ini`.

| Setup produzione | Azione necessaria |
|-----------------|-------------------|
| **Nginx + PHP-FPM** | Nessuna — `www.conf` ha già `php_admin_value[upload_max_filesize] = 1024M` |
| **`artisan serve`** | Rebuild immagine Docker (il `php.ini` aggiornato viene copiato nel build) |
| **Server non Docker** | Aggiungere i valori nel `php.ini` del server o in un file `.user.ini` |

### Variabili d'ambiente

`APP_URL` deve essere l'URL pubblico esatto del server (es. `https://app.portapporta.it`).  
Viene usato in `TicketAnswer::srcToStoragePath()` per trovare le immagini Trix sul filesystem. Se è sbagliato, le immagini nell'email arrivano come link esterni invece che inline — l'email funziona comunque, ma l'immagine potrebbe non essere visibile senza "carica immagini".

### Storage

La directory `storage/app/public/` deve essere scrivibile dal processo web. Verificare i permessi se l'upload fallisce in produzione:

```bash
chmod -R 775 storage/app/public
chown -R www-data:www-data storage/app/public
```

---

## Retention file temporanei

I file caricati tramite Trix (immagini e allegati) vengono salvati in `storage/app/public/` e tracciati nella tabella `nova_pending_field_attachments`.

Il modello `PendingAttachment` di Nova ha il trait `Prunable` configurato per eliminare i record più vecchi di 1 giorno (`created_at <= now()->subDays(1)`) **e i file fisici corrispondenti** tramite il metodo `pruning()`.

Poiché il campo Trix usa `fillUsing(fn () => null)` (il draft non viene mai confermato), i file rimangono sempre in stato pending — quindi verranno eliminati dal pruning dopo 1 giorno, che è sufficiente dato che l'email viene inviata immediatamente.

Il comando `model:prune` è stato aggiunto allo scheduler in `app/Console/Kernel.php`:

```php
$schedule->command('model:prune')->daily();
```

---

## Limitazioni note

- **Numero allegati**: illimitato — qualsiasi file trascinato/allegato in Trix viene convertito in attachment email.
- **File non trovati**: se un file viene eliminato dallo storage prima dell'invio, viene saltato silenziosamente (il controllo `file_exists()` nel Mailable evita errori).
- **Client email e immagini CID**: testato con Mailpit. Gmail, Outlook e Apple Mail supportano CID embedded. Alcuni client enterprise potrebbero mostrare le immagini come attachment separati.
