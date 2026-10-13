<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifikasiEmail;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertOk();
    }

    public function test_users_can_register_and_receive_verification_email()
    {
        Mail::fake();

        Group::factory()->create(['group_id' => 3]);

        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'address'               => 'Denpasar, Bali, Indonesia',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'privacy_policy'        => 'on',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user', [
            'email'    => 'test@example.com',
            'username' => 'testuser',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Denpasar, Bali, Indonesia', $user->address);
        $this->assertNotSame('activated', $user->activation);

        Mail::assertSent(VerifikasiEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_registration_requires_privacy_policy_acceptance()
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('privacy_policy');
    }

    public function test_registration_requires_address()
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'privacy_policy'        => 'on',
        ]);

        $response->assertSessionHasErrors('address');
    }

    public function test_email_verification_token_can_be_used()
    {
        $user = User::factory()->create([
            'activation' => 'verification-token',
        ]);

        $response = $this->get(route('verify.email', ['token' => 'verification-token']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $this->assertSame('activated', $user->fresh()->activation);
    }

    public function test_email_verification_fails_for_invalid_token()
    {
        $response = $this->get(route('verify.email', ['token' => 'invalid-token']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
    }
}
