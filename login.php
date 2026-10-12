<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$email = '';
$next = safe_next_url($_GET['next'] ?? ($_SESSION['after_login'] ?? null));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Masukkan email yang valid.';
    }
    if ($password === '') {
        $errors[] = 'Password wajib diisi.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, name, email, password, role, department, position, avatar FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $account = $stmt->fetch();

        if ($account && password_verify($password, $account['password'])) {
            session_regenerate_id(true);
            unset($account['password']);
            $_SESSION['user'] = $account;
            flash('success', 'Selamat datang kembali, ' . $account['name'] . '!');
            $destination = safe_next_url($_POST['next'] ?? null) ?? $next ?? safe_next_url($_SESSION['after_login'] ?? null) ?? 'dashboard.php';
            unset($_SESSION['after_login']);
            redirect($destination);
        }

        $errors[] = 'Email atau password salah.';
    }
}

$flashes = pull_flashes();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ActivityLog - sistem log aktivitas harian dengan foto.">
    <title>Login — ActivityLog</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<div class="auth-layout">
    <section class="auth-promo">
        <div class="promo-noise"></div>
        <div class="brand brand-light"><div class="brand-mark">AL</div><div><strong>ActivityLog</strong><span>Monitoring kegiatan</span></div></div>
        <div class="promo-content">
            <span class="eyebrow light">WORKSPACE INSTANSI</span>
            <h1>Pantau kegiatan.<br>Dokumentasikan hasil.</h1>
            <p>Tempat terpusat untuk mencatat kegiatan harian, menyimpan foto dokumentasi, dan memantau aktivitas anggota instansi.</p>
            <div class="promo-points"><div><b>01</b><span>Foto tersimpan di server</span></div><div><b>02</b><span>Riwayat aktivitas terstruktur</span></div><div><b>03</b><span>Dashboard progres</span></div></div>
        </div>
        <div class="promo-footer">PHP · MySQL · XAMPP</div>
    </section>

    <main class="auth-main">
        <div class="auth-card">
            <div class="auth-heading"><span class="eyebrow">SELAMAT DATANG</span><h2>Masuk ke ActivityLog</h2><p>Masuk untuk mencatat dan memantau kegiatan instansi.</p></div>
            <?php foreach ($flashes as $flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endforeach; ?>
            <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="next" value="<?= e($next ?? '') ?>">
                <label>Email<input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required placeholder="nama@perusahaan.com"></label>
                <label>Password<div class="password-field"><input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Masukkan password"><button type="button" class="password-toggle" data-target="password">Lihat</button></div></label>
                <button class="btn btn-primary btn-block" type="submit">Masuk <span>→</span></button>
            </form>
            <p class="auth-switch">Belum punya akun? <a href="register.php<?= $next ? '?next='.rawurlencode($next) : '' ?>">Daftar sekarang</a></p>
            <div class="auth-note"><span>🔒</span><p>Daftar atau login untuk melihat foto dokumentasi. Daftar kegiatan dan informasinya tetap dapat dilihat tanpa akun.</p></div>
        </div>
    </main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
