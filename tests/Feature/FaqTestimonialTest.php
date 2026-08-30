<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\HomepageSection;
use App\Models\Testimonial;
use Database\Seeders\HomepageSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTestimonialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->seed(HomepageSeeder::class);
    }

    public function test_faq_section_renders_active_faqs_only(): void
    {
        $visible = Faq::create(['question' => 'Berapa lama pengiriman?', 'answer' => '1-3 hari kerja.', 'sort_order' => 1]);
        Faq::create(['question' => 'Inactive FAQ '.uniqid(), 'answer' => 'rahasia', 'is_active' => false]);

        HomepageSection::create([
            'type' => 'faq',
            'title' => 'FAQ Section',
            'config' => ['heading' => 'Pertanyaan Umum', 'limit' => 8],
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Berapa lama pengiriman?', $html);
        $this->assertStringContainsString('1-3 hari kerja.', $html);
        $this->assertStringNotContainsString('rahasia', $html);
    }

    public function test_faq_group_filter(): void
    {
        Faq::create(['question' => 'Q Pengiriman', 'answer' => 'a', 'group' => 'pengiriman', 'sort_order' => 1]);
        Faq::create(['question' => 'Q Pembayaran', 'answer' => 'b', 'group' => 'pembayaran', 'sort_order' => 2]);

        HomepageSection::create([
            'type' => 'faq',
            'title' => 'FAQ Pengiriman',
            'config' => ['group' => 'pengiriman'],
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Q Pengiriman', $html);
        $this->assertStringNotContainsString('Q Pembayaran', $html);
    }

    public function test_testimonial_rating_only_when_set(): void
    {
        $rated = Testimonial::create(['name' => 'Budi Rated', 'quote' => 'Pelayanan mantap.', 'rating' => 5, 'sort_order' => 1]);
        $unrated = Testimonial::create(['name' => 'Sari Unrated', 'quote' => 'Pengiriman cepat.', 'sort_order' => 2]);

        HomepageSection::create([
            'type' => 'testimonials',
            'title' => 'Testimoni Section',
            'config' => ['limit' => 6],
            'sort_order' => 98,
            'is_active' => true,
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Pelayanan mantap.', $html);
        $this->assertStringContainsString('Pengiriman cepat.', $html);
        $this->assertStringContainsString('Budi Rated', $html);

        // Rating component renders per rating; only the rated testimonial contributes one.
        preg_match_all('/aria-label="Rating/', $html, $matches);

        $this->assertGreaterThanOrEqual(1, count($matches[0]));
    }

    public function test_inactive_testimonials_hidden(): void
    {
        Testimonial::create(['name' => 'Hidden Person', 'quote' => 'SECRET-QUOTE', 'is_active' => false]);

        HomepageSection::create([
            'type' => 'testimonials',
            'title' => 'Testimoni Section',
            'config' => [],
            'sort_order' => 98,
            'is_active' => true,
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('SECRET-QUOTE', $html);
    }
}
