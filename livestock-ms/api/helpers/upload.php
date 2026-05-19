<?php
function saveFarmerPngUpload(array $file, string $prefix, int $userId): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('File upload failed');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'image/png') {
        throw new InvalidArgumentException('Only PNG images are allowed');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'png') {
        throw new InvalidArgumentException('Only PNG images are allowed');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('File must be 5MB or smaller');
    }

    $dir = dirname(__DIR__, 2) . '/uploads/documents';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = $prefix . '_' . $userId . '_' . bin2hex(random_bytes(4)) . '.png';
    $dest     = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save uploaded file');
    }
    return 'documents/' . $filename;
}
