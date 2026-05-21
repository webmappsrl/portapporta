<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class TicketAnswer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected $ticket,
        protected string $answer
    ) {}

    public function build(): self
    {
        $fromAddress = config('mail.from.address');
        $fromName = 'PortAPPorta-' . $this->ticket->company->name;
        $subject = 'Ticket ' . $this->ticket->ticket_type . ' numero: ' . $this->ticket->id . ' - PortAPPorta';

        // I CID vengono sostituiti dentro il Trix HTML *prima* di passarlo alla view.
        // withSymfonyMessage viene eseguito prima di addContent(), quindi non può modificare
        // l'HTML body — deve solo aggiungere i DataPart.
        ['html' => $processedHtml, 'files' => $fileAttachments, 'images' => $inlineImages] = $this->processHtml($this->answer);

        $mail = $this->from($fromAddress, $fromName)
            ->subject($subject)
            ->view('emails.tickets.answer')
            ->with([
                'ticket' => $this->ticket,
                'answer' => $processedHtml,
            ]);

        foreach ($fileAttachments as $file) {
            $mail->attach($file['path'], [
                'as'   => $file['name'],
                'mime' => $file['mime'],
            ]);
        }

        if (!empty($inlineImages)) {
            $this->withSymfonyMessage(function (Email $symfonyMsg) use ($inlineImages) {
                foreach ($inlineImages as $image) {
                    $part = DataPart::fromPath($image['path'], basename($image['path']), $image['mime']);
                    $part->asInline()->setContentId($image['cid']);
                    $symfonyMsg->attachPart($part);
                }
            });
        }

        return $mail;
    }

    private function processHtml(string $html): array
    {
        if (empty($html)) {
            return ['html' => $html, 'files' => [], 'images' => []];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML('<?xml encoding="utf-8"?><html><body>' . $html . '</body></html>');

        $files = [];
        $inlineImages = [];
        $toRemove = [];
        $imgCounter = 0;

        // File non-immagine allegati in Trix → email attachments, rimossi dal body
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//figure[@data-trix-attachment]') as $figure) {
            $attachment = json_decode($figure->getAttribute('data-trix-attachment'), true);
            if (!$attachment) {
                continue;
            }

            $contentType = $attachment['contentType'] ?? '';
            if (str_starts_with($contentType, 'image/')) {
                continue;
            }

            $localPath = $this->srcToStoragePath($attachment['url'] ?? '');
            if ($localPath && file_exists($localPath)) {
                $files[] = [
                    'path' => $localPath,
                    'name' => $attachment['filename'] ?? 'attachment',
                    'mime' => $contentType ?: 'application/octet-stream',
                ];
            }

            $toRemove[] = $figure;
        }

        foreach ($toRemove as $node) {
            $node->parentNode->removeChild($node);
        }

        // Sostituisce gli src delle immagini con CID direttamente nel DOM
        foreach ($dom->getElementsByTagName('img') as $img) {
            $src = $img->getAttribute('src');
            $localPath = $this->srcToStoragePath($src);

            if ($localPath && file_exists($localPath)) {
                $cid = 'img-' . (++$imgCounter) . '@portapporta';
                $inlineImages[] = [
                    'path' => $localPath,
                    'mime' => mime_content_type($localPath) ?: 'image/jpeg',
                    'cid'  => $cid,
                ];
                $img->setAttribute('src', 'cid:' . $cid);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $processedHtml = '';
        foreach ($body->childNodes as $child) {
            $processedHtml .= $dom->saveHTML($child);
        }

        return ['html' => $processedHtml, 'files' => $files, 'images' => $inlineImages];
    }

    private function srcToStoragePath(string $src): ?string
    {
        if (str_starts_with($src, '/storage/')) {
            return storage_path('app/public/' . substr($src, 9));
        }

        $appUrl = rtrim(config('app.url'), '/');
        if (str_starts_with($src, $appUrl . '/storage/')) {
            return storage_path('app/public/' . substr($src, strlen($appUrl) + 9));
        }

        return null;
    }
}
