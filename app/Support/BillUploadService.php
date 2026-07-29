<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BillUploadService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const ALLOWED_PASTED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    private const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;

    /**
     * @param  UploadedFile[]  $files
     */
    public function store(array $files, ?string $pastedImageDataUrl, ?string $existingJson): ?string
    {
        $stored = $this->decodeExisting($existingJson);

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
                continue;
            }
            if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
                continue;
            }

            $extension = self::MIME_TO_EXTENSION[$file->getMimeType()];
            $name = uniqid() . '_' . time() . '.' . $extension;
            $file->storeAs('bills', $name, 'public');
            $stored[] = $name;
        }

        if ($pastedImageDataUrl) {
            $name = $this->storePastedImage($pastedImageDataUrl);
            if ($name !== null) {
                $stored[] = $name;
            }
        }

        return $stored === [] ? null : json_encode($stored, JSON_UNESCAPED_UNICODE);
    }

    private function decodeExisting(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [$json];
    }

    private function storePastedImage(string $dataUrl): ?string
    {
        if (! preg_match('/^data:(image\/(?:jpeg|png|gif|webp));base64,(.+)$/', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || strlen($binary) > self::MAX_FILE_SIZE_BYTES) {
            return null;
        }

        $extension = self::ALLOWED_PASTED_IMAGE_TYPES[$matches[1]] ?? null;
        if ($extension === null) {
            return null;
        }

        $name = uniqid() . '_' . time() . '.' . $extension;
        Storage::disk('public')->put('bills/' . $name, $binary);

        return $name;
    }
}
