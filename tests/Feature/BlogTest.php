<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function makePost(array $attributes = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'title' => 'Artikel '.uniqid(),
            'content' => '<p>Isi artikel <strong>aman</strong>.</p>',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ], $attributes));
    }

    public function test_published_post_visible_with_article_schema(): void
    {
        $post = $this->makePost();

        $response = $this->get('/blog/'.$post->slug);

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString($post->title, $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
    }

    public function test_draft_and_scheduled_posts_hidden_from_public(): void
    {
        $draft = $this->makePost(['title' => 'Draft Post '.uniqid(), 'status' => BlogPost::STATUS_DRAFT]);
        $scheduled = $this->makePost(['title' => 'Scheduled Post '.uniqid(), 'status' => BlogPost::STATUS_SCHEDULED, 'published_at' => now()->addDay()]);

        $this->get('/blog')->assertOk()->assertDontSee($draft->title)->assertDontSee($scheduled->title);
        $this->get('/blog/'.$draft->slug)->assertNotFound();
        $this->get('/blog/'.$scheduled->slug)->assertNotFound();
    }

    public function test_blog_index_filters_by_category_and_tag(): void
    {
        $category = BlogCategory::create(['name' => 'Panduan']);
        $tag = BlogTag::create(['name' => 'Diskon']);

        $inCategory = $this->makePost(['title' => 'Post Kategori '.uniqid(), 'blog_category_id' => $category->id]);
        $other = $this->makePost(['title' => 'Post Lain '.uniqid()]);

        $other->tags()->attach($tag);

        $this->get('/blog?kategori='.$category->slug)->assertOk()->assertSee($inCategory->title)->assertDontSee($other->title);
        $this->get('/blog?tag='.$tag->slug)->assertOk()->assertSee($other->title)->assertDontSee($inCategory->title);
    }

    public function test_content_script_tags_are_sanitized(): void
    {
        $post = $this->makePost([
            'title' => 'XSS Test '.uniqid(),
            'content' => '<p>Paragraf aman</p><script>alert(1)</script><img src="x" onerror="alert(2)">',
        ]);

        $html = $this->get('/blog/'.$post->slug)->getContent();

        $this->assertStringContainsString('Paragraf aman', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_related_posts_share_category(): void
    {
        $category = BlogCategory::create(['name' => 'Kategori Relasi']);
        $a = $this->makePost(['blog_category_id' => $category->id]);
        $b = $this->makePost(['blog_category_id' => $category->id]);
        $this->makePost(['title' => 'Di Kategori Lain '.uniqid()]);

        $related = $a->relatedPosts();

        $this->assertCount(1, $related);
        $this->assertTrue($related->first()->is($b));
    }

    public function test_published_posts_appear_in_sitemap(): void
    {
        $post = $this->makePost();

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString($post->slug, $xml);
    }

    public function test_blog_posts_homepage_section_renders_published_only(): void
    {
        $visible = $this->makePost(['title' => 'Artikel Homepage '.uniqid()]);
        $this->makePost(['title' => 'Artikel Draft '.uniqid(), 'status' => BlogPost::STATUS_DRAFT]);

        \App\Models\HomepageSection::create([
            'type' => 'blog_posts',
            'title' => 'Blog Section',
            'config' => ['heading' => 'Cerita Terbaru', 'limit' => 3],
            'sort_order' => 97,
            'is_active' => true,
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Cerita Terbaru', $html);
        $this->assertStringContainsString($visible->title, $html);
        $this->assertNotFalse(strpos($html, 'Semua Artikel'));
    }
}

namespace Tests\Feature;

// BlogPosts homepage section test appended in separate class file below.
