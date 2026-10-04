<?php

namespace Tests\Feature;

use App\Support\Photos;
use Illuminate\Support\Facades\File;
use Illuminate\View\ComponentAttributeBag;
use Tests\TestCase;

class ImagePipelineTest extends TestCase
{
    public function test_images_command_builds_responsive_derivatives_and_the_component_renders_them(): void
    {
        $src = storage_path('framework/testing/photos-src');
        $dest = storage_path('framework/testing/photos-dest');
        File::deleteDirectory($src);
        File::deleteDirectory($dest);
        File::ensureDirectoryExists($src);
        $img = imagecreatetruecolor(1000, 563);
        imagefill($img, 0, 0, imagecolorallocate($img, 11, 61, 92));
        imagejpeg($img, "$src/library.jpg", 85);
        File::put("$src/manifest.json", json_encode(['photos' => [
            ['slug' => 'library', 'alt' => 'Students reading in a university library', 'credit' => 'Example Photographer / Unsplash', 'source_url' => 'https://unsplash.com/photos/example', 'licence' => 'Unsplash License', 'focal' => '50% 40%', 'page' => '/ (hero)', 'intended_use' => 'hero, right column'],
            ['slug' => 'no-licence', 'alt' => 'x', 'source_url' => 'https://unsplash.com/photos/y'],
        ]]));

        $this->artisan('smukn:images', ['--source' => str_replace(base_path().'/', '', $src), '--dest' => str_replace(base_path().'/', '', $dest)])
            ->expectsOutputToContain('built library: 480, 960 px')->expectsOutputToContain('skipping entry without licence')->assertSuccessful();

        foreach (['library-480.webp', 'library-480.jpg', 'library-960.webp', 'library-960.jpg', 'manifest.json'] as $f) {
            $this->assertFileExists("$dest/$f");
        }
        $this->assertFileDoesNotExist("$dest/library-1440.jpg", 'never upscales beyond the original');
        $manifest = json_decode(File::get("$dest/manifest.json"), true);
        $this->assertSame([480, 960], $manifest['photos']['library']['sizes']);
        $this->assertSame('hero, right column', $manifest['photos']['library']['intended_use'], 'the image record keeps its intended use');
        $this->assertSame('/ (hero)', $manifest['photos']['library']['page']);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $manifest['photos']['library']['placeholder']);
        [$w] = getimagesize("$dest/library-480.jpg");
        $this->assertSame(480, $w);

        // component reads the public manifest; point it at the generated one for the test
        File::ensureDirectoryExists(public_path('images/photos'));
        $live = public_path('images/photos/manifest.json');
        $backup = is_file($live) ? File::get($live) : null;
        File::copy("$dest/manifest.json", $live);
        Photos::reset();
        try {
            $html = view('components.photo', ['slug' => 'library', 'attributes' => new ComponentAttributeBag, 'sizes' => '100vw', 'priority' => true, 'ratio' => '16/9', 'caption' => true])->render();
            $this->assertStringContainsString('<picture>', $html);
            $this->assertStringContainsString('type="image/webp"', $html);
            $this->assertStringContainsString('/images/photos/library-960.webp 960w', $html);
            $this->assertStringContainsString('alt="Students reading in a university library"', $html);
            $this->assertStringContainsString('fetchpriority="high"', $html);
            $this->assertStringContainsString('Photo: Example Photographer / Unsplash', $html);
            $empty = view('components.photo', ['slug' => 'missing', 'attributes' => new ComponentAttributeBag, 'sizes' => '100vw', 'priority' => false, 'ratio' => null, 'caption' => true])->render();
            $this->assertStringNotContainsString('<img', $empty, 'a missing photo renders nothing');
        } finally {
            if ($backup === null) {
                File::delete($live);
            } else {
                File::put($live, $backup);
            }
            Photos::reset();
            File::deleteDirectory($src);
            File::deleteDirectory($dest);
        }
    }
}
