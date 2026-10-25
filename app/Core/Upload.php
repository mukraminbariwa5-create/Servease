<?php
declare(strict_types=1);

/**
 * Safe file uploads: checks the real MIME type (not the extension the browser sends),
 * enforces a size limit and stores files under a random name.
 */
final class Upload
{
    public const IMAGES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public const DOCS   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];

    /**
     * @return array{path:?string, error:?string}
     *   path is relative to the destination root (uploads/ or storage/private/)
     */
    public static function store(?array $file, string $folder, array $allowed = self::IMAGES, ?int $maxMb = null, bool $private = false): array
    {
        if (Demo::uploadsBlocked()) {
            return ['path' => null, 'error' => 'Uploads are turned off in the public demo.'];
        }
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => 'Please choose a file.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'The upload failed. The file may be too large.'];
        }
        $maxMb ??= (int) config('uploads.max_image_mb', 5);
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return ['path' => null, 'error' => "The file must be smaller than {$maxMb} MB."];
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset($allowed[$mime])) {
            $types = strtoupper(implode(', ', array_unique(array_values($allowed))));
            return ['path' => null, 'error' => "Unsupported file type. Allowed: $types."];
        }
        if (str_starts_with($mime, 'image/') && @getimagesize($file['tmp_name']) === false) {
            return ['path' => null, 'error' => 'That image appears to be damaged.'];
        }

        $root = $private ? ROOT . '/storage/private' : ROOT . '/uploads';
        $dir = $root . '/' . trim($folder, '/');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        $moved = is_uploaded_file($file['tmp_name'])
            ? move_uploaded_file($file['tmp_name'], "$dir/$name")
            : rename($file['tmp_name'], "$dir/$name");
        if (!$moved) return ['path' => null, 'error' => 'Could not save the file.'];

        return ['path' => trim($folder, '/') . '/' . $name, 'error' => null];
    }

    public static function delete(?string $path, bool $private = false): void
    {
        if (!$path || str_contains($path, '..')) return;
        $full = ($private ? ROOT . '/storage/private/' : ROOT . '/uploads/') . $path;
        if (is_file($full)) @unlink($full);
    }
}
