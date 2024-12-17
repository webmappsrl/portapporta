<?php
namespace Tests\Feature\V2;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;
use App\Models\Company;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
class VerificationControllerTest extends TestCase
{
    use DatabaseTransactions;
    private $verifiedUser;
    private $company;   
    private $unverifiedUser;
    const API_PREFIX = 'api/v2/';
    public function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->verifiedUser = User::factory()->create(['app_company_id' => $this->company->id, 'email' => 'test@test.com']);
        $this->unverifiedUser = User::factory()->unverified()->create(['app_company_id' => $this->company->id, 'email' => 'test2@test.com']);
    }

    /** @test */
    public function VerifyTestWithUserNotVerified()
    {
        $this->get($this->getSignatureForUser($this->unverifiedUser))
            ->assertStatus(200)
            ->assertViewIs('auth.verifyemail')
            ->assertViewHas('already_validated', false);

        $this->assertTrue($this->unverifiedUser->fresh()->hasVerifiedEmail());
    }

    /** @test */
    public function VerifyTestWithUserAlreadyVerified()
    {
        $this->get($this->getSignatureForUser($this->verifiedUser))
            ->assertStatus(200)
            ->assertViewIs('auth.verifyemail')
            ->assertViewHas('already_validated', true);
    }

    /** @test */
    public function VerifyTestWithInvalidSignature()
    {
        $this->get($this->getSignatureForUser($this->unverifiedUser, false))
            ->assertStatus(400)
            ->assertJson(['message' => 'Invalid signature.']);
    }

    /** @test */
    public function VerifyTestWithUserNotExisting()
    {
        $this->unverifiedUser->id = 0;
        $this->get($this->getSignatureForUser($this->unverifiedUser))
            ->assertStatus(400)
            ->assertJson(['message' => 'User not found.']);
    }


    /** @test */

    public function ResendTest()
    {
        Notification::fake();
        Sanctum::actingAs($this->unverifiedUser);
        $this->get(self::API_PREFIX . 'email/resend')
            ->assertStatus(200)
            ->assertJson(['message' => 'Email verification link sent on your email id']);

        Notification::assertSentTo($this->unverifiedUser, VerifyEmail::class);
    }

    /** @test */
    public function ResendTestWithUserAlreadyVerified()
    {
        Sanctum::actingAs($this->verifiedUser);
        $this->get(self::API_PREFIX . 'email/resend')
            ->assertStatus(200)
            ->assertJson(['message' => 'Email already verified.']);
    }

    /** @test */
    public function ResendTestWithUserNotRegistered()
    {
        $this->get(self::API_PREFIX . 'email/resend')
            ->assertStatus(400)
            ->assertJson(['data' => 'you are not registered']);
    }

    /** @test */
    public function ResendTestWithNonExistantUserLoggedIn()
    {
        $this->unverifiedUser->id = 0;
        Sanctum::actingAs($this->unverifiedUser);
        $this->get(self::API_PREFIX . 'email/resend')
            ->assertStatus(400)
            ->assertJson(['data' => 'User not found.']);
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
