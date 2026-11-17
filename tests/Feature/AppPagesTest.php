<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(): User
    {
        return User::where('email', env('SEED_EMAIL', 'me@budgetdesk.local'))->firstOrFail();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_registration_route_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Budget Desk');
    }

    public function test_seeded_user_can_log_in_and_log_out(): void
    {
        $response = $this->post('/login', [
            'email' => $this->user()->email,
            'password' => env('SEED_PASSWORD', 'password'),
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($this->user());

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_password_is_rejected(): void
    {
        $this->post('/login', [
            'email' => $this->user()->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_every_authenticated_page_renders(): void
    {
        $this->actingAs($this->user());

        foreach (['/', '/expenses', '/paydays', '/bills', '/budgets', '/savings', '/accounts', '/reports', '/settings'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_reports_csv_export_streams(): void
    {
        $this->actingAs($this->user());

        $this->get('/reports/export?month='.now()->format('Y-m'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_full_json_export_streams(): void
    {
        $this->actingAs($this->user());

        $this->get('/settings/export')
            ->assertOk()
            ->assertHeader('content-type', 'application/json');
    }
}
