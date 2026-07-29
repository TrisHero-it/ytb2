<?php

namespace Tests\Feature\Support;

use App\Support\BillUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BillUploadServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_stores_a_valid_uploaded_file_and_returns_json_array(): void
    {
        $service = new BillUploadService();
        $file = UploadedFile::fake()->image('bill.jpg')->size(100);

        $result = $service->store([$file], null, null);

        $decoded = json_decode($result, true);
        $this->assertCount(1, $decoded);
        Storage::disk('public')->assertExists('bills/' . $decoded[0]);
    }

    public function test_stores_file_using_detected_mime_type_not_client_extension(): void
    {
        $service = new BillUploadService();
        // Laravel's fake UploadedFile derives getMimeType() from the given
        // filename's extension unless overridden, so we explicitly force the
        // reported (content-detected) MIME type to image/jpeg to simulate a
        // real upload: valid image bytes that pass MIME validation, but with
        // an attacker-controlled filename claiming to be a PHP script. This
        // mirrors what BillUploadService::store() sees in production, where
        // getMimeType() reads the real file content, not the client filename.
        $file = UploadedFile::fake()->image('evil.php')->size(100)->mimeType('image/jpeg');

        $result = $service->store([$file], null, null);

        $decoded = json_decode($result, true);
        $this->assertCount(1, $decoded);
        $storedName = $decoded[0];
        Storage::disk('public')->assertExists('bills/' . $storedName);
        $this->assertStringEndsNotWith('.php', $storedName);
        $this->assertMatchesRegularExpression('/\.(jpe?g|png|gif|webp)$/', $storedName);
    }

    public function test_appends_to_existing_json_array(): void
    {
        $service = new BillUploadService();
        $file = UploadedFile::fake()->image('bill2.jpg')->size(100);
        $existing = json_encode(['old_file.jpg']);

        $result = $service->store([$file], null, $existing);

        $decoded = json_decode($result, true);
        $this->assertCount(2, $decoded);
        $this->assertContains('old_file.jpg', $decoded);
    }

    public function test_rejects_oversized_file(): void
    {
        $service = new BillUploadService();
        $file = UploadedFile::fake()->image('big.jpg')->size(10241);

        $result = $service->store([$file], null, null);

        $this->assertNull($result);
    }

    public function test_rejects_disallowed_mime_type(): void
    {
        $service = new BillUploadService();
        $file = UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload');

        $result = $service->store([$file], null, null);

        $this->assertNull($result);
    }

    public function test_stores_pasted_base64_image(): void
    {
        $service = new BillUploadService();
        $pixelPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $dataUrl = 'data:image/png;base64,' . base64_encode($pixelPng);

        $result = $service->store([], $dataUrl, null);

        $decoded = json_decode($result, true);
        $this->assertCount(1, $decoded);
        Storage::disk('public')->assertExists('bills/' . $decoded[0]);
    }

    public function test_returns_null_when_nothing_provided(): void
    {
        $service = new BillUploadService();

        $result = $service->store([], null, null);

        $this->assertNull($result);
    }
}
