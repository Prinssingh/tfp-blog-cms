<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;

class ImageService
{
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/avif',
    ];

    private const THUMBNAIL_WIDTH  = 400;
    private const THUMBNAIL_HEIGHT = 300;
    private const WEBP_QUALITY     = 82;

    public function validate(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new AppException('File upload failed. Error code: ' . $file['error'], 422);
        }

        $maxSize = (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 20971520);
        if ($file['size'] > $maxSize) {
            throw new AppException('File size exceeds the maximum allowed size.', 422);
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new AppException('File type not allowed. Accepted: jpg, png, webp, avif.', 422);
        }
    }

    public function store(array $file, int $websiteId): array
    {
        $this->validate($file);

        $uploadDir = $this->makeUploadDir($websiteId);
        $mime      = mime_content_type($file['tmp_name']);
        $baseName  = pathinfo($file['name'], PATHINFO_FILENAME);
        $baseName  = $this->sanitizeFileName($baseName);
        $uniqueName = $baseName . '_' . uniqid();

        $ext          = $this->extensionFromMime($mime);
        $originalName = $uniqueName . '.' . $ext;
        $originalPath = $uploadDir . '/' . $originalName;

        if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
            throw new AppException('Failed to save uploaded file.', 500);
        }

        [$width, $height] = getimagesize($originalPath);

        $webpName = $uniqueName . '.webp';
        $webpPath = $uploadDir . '/' . $webpName;
        $this->convertToWebp($originalPath, $webpPath, $mime);

        $thumbName = $uniqueName . '_thumb.webp';
        $thumbPath = $uploadDir . '/' . $thumbName;
        $this->createThumbnail($originalPath, $thumbPath, $mime);

        $relativePath = $this->relativePath($originalPath);

        return [
            'file_name'  => $originalName,
            'file_path'  => $relativePath,
            'webp_path'  => $this->relativePath($webpPath),
            'thumb_path' => $this->relativePath($thumbPath),
            'mime_type'  => $mime,
            'size'       => filesize($originalPath),
            'width'      => $width,
            'height'     => $height,
        ];
    }

    public function delete(string $filePath): void
    {
        $base      = BASE_PATH . '/uploads/';
        $full      = BASE_PATH . '/' . ltrim($filePath, '/');
        $webpFull  = preg_replace('/\.[^.]+$/', '.webp', $full);
        $thumbFull = preg_replace('/\.[^.]+$/', '_thumb.webp', $full);

        foreach ([$full, $webpFull, $thumbFull] as $path) {
            if (file_exists($path) && str_starts_with(realpath($path), realpath($base))) {
                unlink($path);
            }
        }
    }

    private function makeUploadDir(int $websiteId): string
    {
        $dir = BASE_PATH . '/uploads/website-' . $websiteId . '/' . date('Y/m');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function convertToWebp(string $source, string $dest, string $mime): void
    {
        $image = $this->createImageResource($source, $mime);
        if ($image === null) {
            return;
        }
        imagewebp($image, $dest, self::WEBP_QUALITY);
        imagedestroy($image);
    }

    private function createThumbnail(string $source, string $dest, string $mime): void
    {
        $image = $this->createImageResource($source, $mime);
        if ($image === null) {
            return;
        }

        $origW = imagesx($image);
        $origH = imagesy($image);

        $ratio   = min(self::THUMBNAIL_WIDTH / $origW, self::THUMBNAIL_HEIGHT / $origH);
        $newW    = (int) ($origW * $ratio);
        $newH    = (int) ($origH * $ratio);
        $thumb   = imagecreatetruecolor($newW, $newH);

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagewebp($thumb, $dest, self::WEBP_QUALITY);

        imagedestroy($image);
        imagedestroy($thumb);
    }

    private function createImageResource(string $path, string $mime): ?\GdImage
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png'               => imagecreatefrompng($path),
            'image/webp'              => imagecreatefromwebp($path),
            default                   => null,
        };
    }

    private function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png'               => 'png',
            'image/webp'              => 'webp',
            'image/avif'              => 'avif',
            default                   => 'jpg',
        };
    }

    private function sanitizeFileName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\-_]/', '-', $name);
        return trim($name, '-');
    }

    private function relativePath(string $absolutePath): string
    {
        return str_replace(BASE_PATH, '', $absolutePath);
    }
}
