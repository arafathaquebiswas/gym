<?php

/**
 * Validated file upload handling for admin-managed content (trainer photos,
 * product images, etc). Never trusts the client-supplied MIME type or
 * filename — sniffs the real MIME with finfo and writes under a random name.
 */
final class Upload
{
    private static ?string $lastError = null;

    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param array $file One entry from $_FILES (e.g. $_FILES['photo'])
     * @return string|null Web-relative path like "uploads/trainers/xxxx.jpg", or null on failure/no file.
     */
    public static function handle(array $file, string $subfolder, int $maxBytes = MAX_UPLOAD_SIZE): ?string
    {
        self::$lastError = null;

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            self::$lastError = 'Upload failed. Please try again.';
            return null;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            self::$lastError = 'Invalid upload.';
            return null;
        }
        if ($file['size'] > $maxBytes) {
            self::$lastError = 'File is too large (max ' . round($maxBytes / 1024 / 1024, 1) . 'MB).';
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);

        if (!isset(self::MIME_EXTENSIONS[$mime]) || !in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
            self::$lastError = 'Only JPG, PNG, or WEBP images are allowed.';
            return null;
        }

        $extension = self::MIME_EXTENSIONS[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetDir = UPLOAD_PATH . '/' . trim($subfolder, '/');

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            self::$lastError = 'Could not create upload directory.';
            return null;
        }

        $targetPath = $targetDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            self::$lastError = 'Could not save the uploaded file.';
            return null;
        }

        return 'uploads/' . trim($subfolder, '/') . '/' . $filename;
    }

    /**
     * Deletes a previously uploaded file given its stored "uploads/..." path.
     * Refuses to touch anything outside the uploads directory.
     */
    public static function delete(?string $webPath): void
    {
        if (!$webPath || !str_starts_with($webPath, 'uploads/')) {
            return;
        }
        $fullPath = BASE_PATH . '/' . $webPath;
        $realUploadPath = realpath(UPLOAD_PATH);
        $realFilePath = realpath($fullPath);

        if ($realFilePath && $realUploadPath && str_starts_with($realFilePath, $realUploadPath) && is_file($realFilePath)) {
            unlink($realFilePath);
        }
    }
}
