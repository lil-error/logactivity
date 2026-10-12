<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$next=safe_next_url($_GET['next'] ?? ($_SESSION['after_login'] ?? null));
$errors=[]; $name=''; $email=''; $department=''; $position='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf($_POST['csrf_token']??null);
    $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??''));
    $department=trim($_POST['department']??''); $position=trim($_POST['position']??'');
    $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
    if ($name==='' || mb_strlen($name)>100) $errors[]='Nama wajib diisi dan maksimal 100 karakter.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email)>190) $errors[]='Masukkan alamat email yang valid.';
    if (mb_strlen($department)>120) $errors[]='Nama unit/bagian maksimal 120 karakter.';
    if (mb_strlen($position)>120) $errors[]='Jabatan maksimal 120 karakter.';
    if (strlen($password)<8) $errors[]='Password minimal 8 karakter.';
    if ($password!==$confirm) $errors[]='Konfirmasi password tidak sama.';
    if (!$errors) {
        try {
            $stmt=db()->prepare('INSERT INTO users(name,email,password,role,department,position) VALUES(:name,:email,:password,\'staff\',:department,:position)');
            $stmt->execute([':name'=>$name,':email'=>$email,':password'=>password_hash($password,PASSWORD_DEFAULT),':department'=>$department?:null,':position'=>$position?:null]);
            session_regenerate_id(true);
            $_SESSION['user']=['id'=>(int)db()->lastInsertId(),'name'=>$name,'email'=>$email,'role'=>'staff','department'=>$department,'position'=>$position,'avatar'=>'avatar-1'];
            flash('success','Akun berhasil dibuat. Selamat bergabung di ActivityLog Instansi!');
            $destination=safe_next_url($_POST['next'] ?? null) ?? $next ?? safe_next_url($_SESSION['after_login'] ?? null) ?? 'dashboard.php';
            unset($_SESSION['after_login']);
            redirect($destination);
        } catch (PDOException $e) {
            $errors[]=$e->getCode()==='23000'?'Email tersebut sudah terdaftar. Silakan masuk atau gunakan email lain.':'Pendaftaran gagal disimpan. Periksa koneksi database.';
        }
    }
}
$flashes=pull_flashes();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Buat akun ActivityLog Instansi untuk mencatat dan memantau kegiatan."><title>Daftar — <?= e(APP_NAME) ?></title><link rel="stylesheet" href="assets/css/app.css"></head>
<body class="auth-page"><div class="auth-layout">
<section class="auth-promo"><div class="promo-noise"></div><div class="brand brand-light"><div class="brand-mark">AL</div><div><strong>ActivityLog</strong><span>Monitoring kegiatan instansi</span></div></div><div class="promo-content"><span class="eyebrow light">WORKSPACE INSTANSI</span><h1>Setiap kegiatan<br>terdokumentasi.</h1><p>Buat akun sendiri, catat kegiatan, unggah bukti foto, dan lihat aktivitas bersama dalam satu tempat.</p><div class="promo-points"><div><b>01</b><span>Registrasi mandiri</span></div><div><b>02</b><span>Galeri dokumentasi</span></div><div><b>03</b><span>Pemantauan bersama</span></div></div></div><div class="promo-footer">PHP · MYSQL · XAMPP</div></section>
<main class="auth-main"><div class="auth-card"><div class="auth-heading"><span class="eyebrow">REGISTRASI ANGGOTA</span><h2>Buat akun baru</h2><p>Akun dibuat sendiri, tidak perlu dibuat oleh administrator.</p></div>
<?php foreach($flashes as $flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endforeach; ?>
<?php if($errors): ?><div class="alert alert-error"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="auth-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="next" value="<?= e($next ?? '') ?>">
<label>Nama lengkap<input name="name" required maxlength="100" autocomplete="name" value="<?= e($name) ?>" placeholder="Nama lengkap"></label>
<label>Email pribadi<input type="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($email) ?>" placeholder="nama@email.com"></label>
<label>Unit / bagian <span class="optional-label">Opsional</span><input name="department" maxlength="120" value="<?= e($department) ?>" placeholder="Contoh: IT / Administrasi"></label>
<label>Jabatan <span class="optional-label">Opsional</span><input name="position" maxlength="120" value="<?= e($position) ?>" placeholder="Contoh: Staff / Peserta Magang"></label>
<label>Password<div class="password-field"><input id="password" type="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Minimal 8 karakter"><button type="button" class="password-toggle" data-target="password">Lihat</button></div></label>
<label>Konfirmasi password<input type="password" name="confirm_password" minlength="8" required autocomplete="new-password" placeholder="Ketik ulang password"></label>
<button class="btn btn-primary btn-block" type="submit">Buat akun <span>→</span></button></form>
<p class="auth-switch">Sudah punya akun? <a href="login.php<?= $next ? '?next='.rawurlencode($next) : '' ?>">Masuk ke halaman login</a></p><div class="auth-note"><span>ℹ️</span><p>Setelah mendaftar, kamu bisa langsung membuka dokumentasi foto kegiatan. Informasi umum kegiatan bisa dilihat tanpa login.</p></div>
</div></main></div><script src="assets/js/app.js"></script></body></html>
