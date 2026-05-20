<?php
/**
 * API: upload_request_letter
 * Handles secure file upload for request letters (PDF, DOC, DOCX).
 *
 * Security features:
 *  1. Session + role check before processing
 *  2. File extension whitelist
 *  3. MIME type verification via finfo (not browser-reported type)
 *  4. File size limit (5 MB)
 *  5. SHA-256 hash of file content — stored for integrity verification
 *  6. Randomized filename to prevent path traversal
 *  7. .htaccess blocks PHP execution inside uploads folder
 *
 * Method: POST (multipart/form-data)
 * Auth:   Requires login (community_organizer)
 * Body:   file: <uploaded file>
 * Returns: {
 *   success: true,
 *   path: string,
 *   filename: string,
 *   hash: string,       ← SHA-256 of file content
 *   hash_algo: 'sha256',
 *   size: int,
 *   mime: string
 * }
 */

require_once __DIR__ . '/bootstrap.php';

apiRequireRole('community_organizer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed. Use POST.', 405);
}

if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    apiError('No file uploaded.');
}

$file    = $_FILES['file'];
$maxSize = 5 * 1024 * 1024; // 5 MB

// ── 1. Upload error check ─────────────────────────────────
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
    ];
    apiError($uploadErrors[$file['error']] ?? 'Unknown upload error.');
}

// ── 2. File size check ────────────────────────────────────
if ($file['size'] > $maxSize) {
    apiError('File size must not exceed 5 MB. Uploaded: ' . round($file['size'] / 1048576, 2) . ' MB');
}

// ── 3. Extension whitelist ────────────────────────────────
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf', 'doc', 'docx'];

if (!in_array($ext, $allowed)) {
    apiError('Invalid file type. Only PDF, DOC, and DOCX are allowed.');
}

// ── 4. MIME type verification via finfo ───────────────────
$validMimes = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$realMime = $finfo->file($file['tmp_name']);

if ($realMime !== $validMimes[$ext]) {
    apiError('File content does not match its extension. Upload rejected.');
}

// ── 5. Generate SHA-256 hash of file content ──────────────
$fileContent = file_get_contents($file['tmp_name']);
$fileHash    = hash('sha256', $fileContent);

// ── 6. Generate safe randomized filename ─────────────────
$userId   = $_SESSION['user']['id'];
$safeName = time() . '_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$uploadDir = __DIR__ . '/../uploads/request_letters/';
$destPath  = $uploadDir . $safeName;
$publicPath = 'uploads/request_letters/' . $safeName;

// ── 7. Move file to uploads folder ───────────────────────
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    apiError('Failed to save file. Check folder permissions.');
}

// ── 8. Log the upload ─────────────────────────────────────
$secLog = new SecurityLog($conn);
$secLog->logActivity(
    $userId,
    'file_upload',
    'organizer',
    "Uploaded request letter: $safeName | SHA-256: $fileHash | Size: {$file['size']} bytes"
);

// ── 9. Return success with hash ───────────────────────────
apiSuccess([
    'path'       => $publicPath,
    'filename'   => $safeName,
    'original'   => htmlspecialchars($file['name']),
    'hash'       => $fileHash,
    'hash_algo'  => 'sha256',
    'size'       => $file['size'],
    'mime'       => $realMime,
]);
?>
