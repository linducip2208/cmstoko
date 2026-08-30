<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use App\Services\MediaService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        Storage::fake('public');
        Storage::fake('temp');
    }

    protected function service(): MediaService
    {
        return app(MediaService::class);
    }

    public function test_store_persists_safe_image_metadata(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('My Photo Üñí.png', 300, 200);

        $media = $this->service()->store($file, $user->id);

        $this->assertStringStartsWith('my-photo-un', $media->file_name);
        $this->assertStringEndsWith('.png', $media->file_name);
        $this->assertSame('image/png', $media->mime);
        $this->assertSame(300, $media->width);
        $this->assertSame(200, $media->height);
        $this->assertSame($user->id, $media->uploaded_by);
        $this->assertTrue(Storage::disk('public')->exists($media->path));
        // Random suffix prevents path guessing/overwrites.
        $this->assertStringContainsString('-', $media->file_name);
    }

    public function test_executable_disguised_as_image_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $file = UploadedFile::fake()->create('payload.php', 10);

        $this->service()->store($file, 1);
    }

    public function test_non_image_mime_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'just text, not an image');

        $this->service()->store($file, 1);
    }

    public function test_oversized_image_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $file = UploadedFile::fake()->create('big.png', Media::MAX_SIZE + 100, 'image/png');

        $this->service()->store($file, 1);
    }

    public function test_svg_scripts_are_sanitized(): void
    {
        $user = User::factory()->create();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect onmouseover="alert(2)" width="10" height="10"/></svg>';

        $file = UploadedFile::fake()->createWithContent('evil.svg', $svg);

        $media = $this->service()->store($file, $user->id);

        $stored = Storage::disk('public')->get($media->path);

        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onmouseover', $stored);
        $this->assertStringContainsString('<rect', $stored);
    }

    public function test_file_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $media = $this->service()->store(UploadedFile::fake()->image('used.png'), $user->id);

        \App\Models\Product::create([
            'category_id' => \App\Models\Category::create(['name' => 'Cat '.uniqid()])->id,
            'name' => 'P',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
            'images' => [$media->path],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service()->delete($media);
    }

    public function test_unused_file_deletes_disk_and_row(): void
    {
        $user = User::factory()->create();
        $media = $this->service()->store(UploadedFile::fake()->image('unused.jpg'), $user->id);
        $path = $media->path;

        $this->service()->delete($media);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_media_admin_page_access(): void
    {
        $admin = User::factory()->create(['role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id')]);
        $customer = User::factory()->create(['role_id' => Role::where('slug', Role::CUSTOMER)->value('id')]);

        $this->actingAs($admin)->get('/admin/media')->assertOk();
        $this->actingAs($customer)->get('/admin/media')->assertForbidden();
    }
}
