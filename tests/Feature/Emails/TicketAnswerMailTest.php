<?php

namespace Tests\Feature\Emails;

use App\Mail\TicketAnswer;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Nova\Actions\TicketAnswerViaMail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\ActionFields;
use Tests\TestCase;

class TicketAnswerMailTest extends TestCase
{
    use DatabaseTransactions;

    private function createTicketContext(array $ticketOverrides = []): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['app_company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'geometry'   => null,
            ...$ticketOverrides,
        ]);

        return compact('company', 'user', 'ticket');
    }

    /** @test */
    public function answerEmailRendersPlainTextInBody(): void
    {
        ['ticket' => $ticket] = $this->createTicketContext();

        $html = (new TicketAnswer($ticket, 'Risposta semplice'))->render();

        $this->assertStringContainsString('Risposta semplice', $html);
    }

    /** @test */
    public function answerEmailRendersHtmlUnescaped(): void
    {
        ['ticket' => $ticket] = $this->createTicketContext();

        $html = (new TicketAnswer($ticket, '<p><strong>Testo grassetto</strong></p>'))->render();

        $this->assertStringContainsString('<strong>Testo grassetto</strong>', $html);
        $this->assertStringNotContainsString('&lt;strong&gt;', $html);
    }

    /** @test */
    public function answerEmailSubstitutesInlineImageSrcWithCid(): void
    {
        ['ticket' => $ticket] = $this->createTicketContext();

        Storage::disk('public')->put('trix-test-image.jpg', 'fake-image-content');

        try {
            $html = (new TicketAnswer($ticket, '<img src="/storage/trix-test-image.jpg">'))
                ->render();

            $this->assertStringContainsString('src="cid:img-1@portapporta"', $html);
            $this->assertStringNotContainsString('src="/storage/trix-test-image.jpg"', $html);
        } finally {
            Storage::disk('public')->delete('trix-test-image.jpg');
        }
    }

    /** @test */
    public function answerEmailLeavesImgSrcUnchangedWhenFileNotFound(): void
    {
        ['ticket' => $ticket] = $this->createTicketContext();

        $html = (new TicketAnswer($ticket, '<img src="/storage/nonexistent-file.jpg">'))
            ->render();

        $this->assertStringContainsString('src="/storage/nonexistent-file.jpg"', $html);
        $this->assertStringNotContainsString('cid:', $html);
    }

    /** @test */
    public function answerEmailRemovesNonImageTrixAttachmentFigureFromBody(): void
    {
        ['ticket' => $ticket] = $this->createTicketContext();

        Storage::disk('public')->put('trix-test-document.pdf', '%PDF-fake');

        $attachment = json_encode([
            'contentType' => 'application/pdf',
            'url'         => '/storage/trix-test-document.pdf',
            'filename'    => 'trix-test-document.pdf',
        ]);
        $trixHtml = "<figure data-trix-attachment='{$attachment}'></figure><p>Testo rimasto</p>";

        try {
            $html = (new TicketAnswer($ticket, $trixHtml))->render();

            $this->assertStringNotContainsString('data-trix-attachment', $html);
            $this->assertStringNotContainsString('trix-test-document.pdf', $html);
            $this->assertStringContainsString('Testo rimasto', $html);
        } finally {
            Storage::disk('public')->delete('trix-test-document.pdf');
        }
    }

    /** @test */
    public function answerEmailKeepsImageFigureInBodyAndConvertsImgToCid(): void
    {
        ['ticket' => $ticket] = $this->createTicketContext();

        Storage::disk('public')->put('trix-test-img-figure.jpg', 'fake-img');

        $attachment = json_encode([
            'contentType' => 'image/jpeg',
            'url'         => '/storage/trix-test-img-figure.jpg',
            'filename'    => 'trix-test-img-figure.jpg',
        ]);
        $trixHtml = "<figure data-trix-attachment='{$attachment}'><img src=\"/storage/trix-test-img-figure.jpg\"></figure>";

        try {
            $html = (new TicketAnswer($ticket, $trixHtml))->render();

            // Image figures stay in the body
            $this->assertStringContainsString('data-trix-attachment', $html);
            // img src is replaced with CID
            $this->assertStringContainsString('src="cid:img-1@portapporta"', $html);
        } finally {
            Storage::disk('public')->delete('trix-test-img-figure.jpg');
        }
    }

    /** @test */
    public function answerEmailHandlesEmptyAnswerWithoutError(): void
    {
        ['ticket' => $ticket] = $this->createTicketContext();

        $html = (new TicketAnswer($ticket, ''))->render();

        $this->assertStringContainsString('Risposta', $html);
    }

    /** @test */
    public function actionSendsEmailToUserAndCcsOperator(): void
    {
        Mail::fake();

        ['company' => $company, 'user' => $user, 'ticket' => $ticket] = $this->createTicketContext();
        $admin = User::factory()->create(['app_company_id' => $company->id]);
        Auth::login($admin);

        $action = new TicketAnswerViaMail();
        $fields = new ActionFields(collect(['answer' => '<p>Risposta</p>']), collect());
        $action->handle($fields, collect([$ticket]));

        Mail::assertSent(TicketAnswer::class, function ($mail) use ($user, $admin) {
            return $mail->hasTo($user->email) && $mail->hasCc($admin->email);
        });
    }

    /** @test */
    public function actionMarksTicketAsReadAfterSend(): void
    {
        Mail::fake();

        ['company' => $company, 'ticket' => $ticket] = $this->createTicketContext();
        Auth::login(User::factory()->create(['app_company_id' => $company->id]));

        $this->assertFalse((bool) $ticket->is_read);

        $action = new TicketAnswerViaMail();
        $fields = new ActionFields(collect(['answer' => '<p>Risposta</p>']), collect());
        $action->handle($fields, collect([$ticket]));

        $this->assertTrue((bool) $ticket->fresh()->is_read);
    }

    /** @test */
    public function actionReturnsDangerOnMailException(): void
    {
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP error'));

        ['company' => $company, 'ticket' => $ticket] = $this->createTicketContext();
        Auth::login(User::factory()->create(['app_company_id' => $company->id]));

        $action = new TicketAnswerViaMail();
        $fields = new ActionFields(collect(['answer' => '<p>Risposta</p>']), collect());
        $result = $action->handle($fields, collect([$ticket]));

        $this->assertFalse((bool) $ticket->fresh()->is_read);
    }
}
