<?php
require_once __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$avatar = 'avatar-1';
if ($id > 0) {
    try {
        $stmt = db()->prepare('SELECT avatar FROM users WHERE id=:id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $stored = $stmt->fetchColumn();
        if (is_string($stored) && $stored !== '') $avatar = $stored;
    } catch (Throwable $e) {
        $avatar = 'avatar-1';
    }
}
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, max-age=0, must-revalidate');
if (is_preset_avatar($avatar)) {
    $path = __DIR__ . '/assets/avatars/' . preset_avatar_asset($avatar) . '.svg';
    header('Content-Type: image/svg+xml; charset=utf-8');
    if (!is_file($path)) { http_response_code(404); exit; }
    readfile($path); exit;
}
if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|gif)$/i', $avatar)) $avatar = 'avatar-1';
if ($avatar === 'avatar-1') {
    $path = __DIR__ . '/assets/avatars/fruit-apple.svg';
    header('Content-Type: image/svg+xml; charset=utf-8');
    readfile($path); exit;
}
$path = AVATAR_DIR . DIRECTORY_SEPARATOR . basename($avatar);
if (!is_file($path)) {
    header('Content-Type: image/svg+xml; charset=utf-8');
    readfile(__DIR__ . '/assets/avatars/fruit-apple.svg'); exit;
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path);
$allowed = ['image/jpeg','image/png','image/webp','image/gif'];
if (!in_array($mime, $allowed, true) || @getimagesize($path) === false) {
    header('Content-Type: image/svg+xml; charset=utf-8');
    readfile(__DIR__ . '/assets/avatars/fruit-apple.svg'); exit;
}
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="profile-image"');
readfile($path); exit;
