<?php
namespace Tests\Feature\V2;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;
class VerificationControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;

    private $verifiedUser;
    private $company;   
    private $unverifiedUser;
    const API_PREFIX = 'api/v2/';
    const responseMessages = [
        'invalidSignature' => 'Invalid signature.',
        'userNotFound' => 'User not found.',
        'emailAlreadyVerified' => 'Email already verified.',
        'emailNotRegistered' => 'you are not registered',
        'emailVerificationLinkSent' => 'Email verification link sent on your email id',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->company = $this->createCompany();
        $this->verifiedUser = $this->createUser(company: $this->company, extraAttributes: ['email' => 'test@test.com']);
        $this->unverifiedUser = $this->createUser(company: $this->company, extraAttributes: ['email' => 'test2@test.com', 'email_verified_at' => null]);
    }

    /** @test */
    public function VerifyTestWithUserNotVerified()
    {
        $this->assertFromViewIfUserIsValidated($this->unverifiedUser, false);
        $this->assertTrue($this->unverifiedUser->fresh()->hasVerifiedEmail());
    }

    /** @test */
    public function VerifyTestWithUserAlreadyVerified()
    {
        $this->assertFromViewIfUserIsValidated($this->verifiedUser, true);
    }

    /** @test */
    public function VerifyTestWithInvalidSignature()
    {
        $this->assertErrorResponse(
            $this->get($this->getSignatureForUser($this->unverifiedUser, false)),
            self::responseMessages['invalidSignature'],
            400
        );
    }

    /** @test */
    public function VerifyTestWithUserNotExisting()
    {
        $this->unverifiedUser->id = 0;
        $this->assertErrorResponse(
            $this->get($this->getSignatureForUser($this->unverifiedUser)),
            self::responseMessages['userNotFound'],
            400
        );
    }

    /** @test */
    public function ResendTest()
    {
        Notification::fake();
        Sanctum::actingAs($this->unverifiedUser);
        $this->assertSuccessResponse(
            $this->get(self::API_PREFIX . 'email/resend'),
            self::responseMessages['emailVerificationLinkSent'],
            200
        );
        Notification::assertSentTo($this->unverifiedUser, VerifyEmail::class);
    }

    /** @test */
    public function ResendTestWithUserAlreadyVerified()
    {
        Sanctum::actingAs($this->verifiedUser);
        $this->assertSuccessResponse(
            $this->get(self::API_PREFIX . 'email/resend'),
            self::responseMessages['emailAlreadyVerified'],
            200
        );
    }

    /** @test */
    public function ResendTestWithUserNotRegistered()
    {
        $this->assertErrorResponse(
            $this->get(self::API_PREFIX . 'email/resend'),
            self::responseMessages['emailNotRegistered'],
            400,
            true
        );
    }

    /** @test */
    public function ResendTestWithNonExistantUserLoggedIn()
    {
        $this->unverifiedUser->id = 0;
        Sanctum::actingAs($this->unverifiedUser);
        $this->assertErrorResponse(
            $this->get(self::API_PREFIX . 'email/resend'),
            self::responseMessages['userNotFound'],
            400,
            true
        );
    }

    private function assertFromViewIfUserIsValidated($user, $alreadyValidated){
        $this->get($this->getSignatureForUser($user))
            ->assertStatus(200)
            ->assertViewIs('auth.verifyemail')
            ->assertViewHas('already_validated', $alreadyValidated);
    }

    private function getSignatureForUser($user, $valid = true)
    {
        $expirationTime = $valid ? 
            Carbon::now()->addMinutes(10) : 
            Carbon::now()->subMinutes(10);

        return URL::temporarySignedRoute(
            "verificationV2.verify",
            $expirationTime,
            ['id' => $user->id]
        );
    }

}
