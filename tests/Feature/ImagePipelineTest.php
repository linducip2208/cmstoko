<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Services\ImagePipeline;
use App\Services\MediaService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagePipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        Storage::fake('public');
    }

    protected function storeImage(int $width = 1600, int $height = 900): Media
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('hero photo.png', $width, $height);

        return app(MediaService::class)->store($file, $user->id);
    }

    public function test_variants_generated_on_demand_and_cached(): void
    {
        $media = $this->storeImage(1600, 900);

        $url320 = app(ImagePipeline::class)->variant($media, 320);

        $this->assertStringContainsString('-320w.webp', $url320);

        // Physically on disk under the same directory.
        $variantPath = 'media/'.now()->format('Y/m').'/'.pathinfo($media->file_name, PATHINFO_FILENAME).'-320w.webp';
        Storage::disk('public')->assertExists($variantPath);

        // Second call serves the cached variant (same URL).
        $this->assertSame($url320, app(ImagePipeline::class)->variant($media, 320));
    }

    public function test_srcset_includes_widths_and_original(): void
    {
        $media = $this->storeImage(1600, 900);

        $srcset = app(ImagePipeline::class)->srcset($media);

        $this->assertStringContainsString('320w', $srcset);
        $this->assertStringContainsString('1280w', $srcset);
        $this->assertStringContainsString($media->url(), $srcset); // original as largest
    }

    public function test_no_upscaling_beyond_source_width(): void
    {
        $media = $this->storeImage(400, 300); // small source

        $url320 = app(ImagePipeline::class)->variant($media, 320);
        $url1280 = app(ImagePipeline::class)->variant($media, 1280);

        // 320 exists (downscale), 1280 must fall back to the ORIGINAL.
        $this->assertStringContainsString('-320w', $url320);
        $this->assertSame($media->url(), $url1280);
    }

    public function test_svg_served_as_is(): void
    {
        $user = User::factory()->create();

        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';
        $media = app(MediaService::class)->store(
            UploadedFile::fake()->createWithContent('icon.svg', $svg),
            $user->id,
        );

        $url = app(ImagePipeline::class)->variant($media, 320);

        $this->assertSame($media->url(), $url);
    }

    public function test_corrupt_source_falls_back_to_original(): void
    {
        $user = User::factory()->create();

        // Valid PNG header claim, garbage content.
        $media = Media::create([
            'file_name' => 'broken-'.uniqid().'.png',
            'original_name' => 'broken.png',
            'path' => 'media/broken/broken.png',
            'disk' => 'public',
            'mime' => 'image/png',
            'extension' => 'png',
            'size' => 10,
            'uploaded_by' => $user->id,
        ]);

        Storage::disk('public')->put($media->path, 'not-an-image');

        $url = app(ImagePipeline::class)->variant($media, 320);

        $this->assertSame($media->url(), $url);
    }

    public function test_delete_removes_original_and_variants(): void
    {
        $user = User::factory()->create();
        $media = $this->storeImage(1600, 900);
        $originalPath = $media->path;

        app(ImagePipeline::class)->variant($media, 320);

        $variantPath = 'media/'.now()->format('Y/m').'/'.pathinfo($media->file_name, PATHINFO_FILENAME).'-320w.webp';
        Storage::disk('public')->assertExists($variantPath);

        app(MediaService::class)->delete($media);

        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertMissing($variantPath);
    }

    public function test_img_component_renders_srcset_and_lazy(): void
    {
        $media = $this->storeImage(1600, 900);

        $html = $this->blade(
            '<x-img :media="$media" alt="Foto" sizes="(max-width: 768px) 100vw, 50vw" />',
            ['media' => $media],
        );

        $this->assertStringContainsString('srcset=', $html);
        $this->assertStringContainsString('sizes="(max-width: 768px) 100vw, 50vw"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
        $this->assertStringContainsString('width="1600"', $html);
        $this->assertStringContainsString('alt="Foto"', $html);
    }

    public function test_img_component_eager_for_hero(): void
    {
        $media = $this->storeImage();

        $html = $this->blade(
            '<x-img :media="$media" alt="Hero" eager preload />',
            ['media' => $media],
        );

        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('decoding="sync"', $html);
        $this->assertStringContainsString('rel="preload"', $html);
    }
}
