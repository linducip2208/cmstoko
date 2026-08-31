<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_subscribe_stores_token_and_source(): void
    {
        $this->post('/newsletter', ['email' => 'subscriber@example.com'])
            ->assertRedirect();

        $subscriber = NewsletterSubscriber::first();

        $this->assertNotNull($subscriber->token);
        $this->assertSame('subscriber@example.com', $subscriber->email);
        $this->assertNull($subscriber->unsubscribed_at);
    }

    public function test_unsubscribe_with_token_marks_and_does_not_resubscribe_others(): void
    {
        $subscriber = NewsletterSubscriber::create(['email' => 'bye@example.com']);

        $this->assertNull($subscriber->fresh()->unsubscribed_at);

        $this->get('/newsletter/berhenti/'.$subscriber->token)->assertRedirect();

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);

        // Unknown tokens 404 — cannot unsubscribe other people by guessing.
        $this->get('/newsletter/berhenti/not-a-real-token')->assertNotFound();
    }

    public function test_resubscribe_after_unsubscribe_clears_flag(): void
    {
        $this->post('/newsletter', ['email' => 'cycle@example.com']);
        $subscriber = NewsletterSubscriber::where('email', 'cycle@example.com')->first();
        $subscriber->update(['unsubscribed_at' => now()]);

        $this->post('/newsletter', ['email' => 'cycle@example.com']);

        $this->assertNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_newsletter_admin_listing_accessible_to_marketing_only(): void
    {
        $marketing = User::factory()->create([
            'role_id' => Role::where('slug', Role::MARKETING)->value('id'),
        ]);

        $finance = User::factory()->create([
            'role_id' => Role::where('slug', Role::FINANCE)->value('id'),
        ]);

        $this->actingAs($marketing)->get('/admin/newsletter-subscribers')->assertOk();
        $this->actingAs($finance)->get('/admin/newsletter-subscribers')->assertForbidden();
    }
}
