<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$page_title = 'Profil Saya';
$active_page = 'profile';
$errors = [];
$userId = (int)user()['id'];
$stmt = db()->prepare('SELECT id,name,email,department,position,role,avatar,created_at FROM users WHERE id=:id LIMIT 1');
$stmt->execute([':id'=>$userId]);
$account = $stmt->fetch();
if (!$account) { flash('error','Akun tidak ditemukan.'); redirect('logout.php'); }
$profile = ['name'=>$account['name'], 'email'=>$account['email'], 'department'=>$account['department'] ?? '', 'position'=>$account['position'] ?? ''];
$currentAvatar = $account['avatar'] ?: 'avatar-1';
$selectedAvatar = is_preset_avatar($currentAvatar) ? $currentAvatar : 'keep';
$joined = $account['created_at'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $profile['name'] = trim($_POST['name'] ?? '');
    $profile['email'] = strtolower(trim($_POST['email'] ?? ''));
    $profile['department'] = trim($_POST['department'] ?? '');
    $profile['position'] = trim($_POST['position'] ?? '');
    $selectedAvatar = (string)($_POST['avatar_choice'] ?? 'keep');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $avatarFile = $_FILES['avatar_photo'] ?? ['error'=>UPLOAD_ERR_NO_FILE];
    $hasAvatarUpload = (int)($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($profile['name'] === '' || mb_strlen($profile['name']) > 100) $errors[] = 'Nama wajib diisi dan maksimal 100 karakter.';
    if (!filter_var($profile['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($profile['email']) > 190) $errors[] = 'Email tidak valid.';
    if (mb_strlen($profile['department']) > 120 || mb_strlen($profile['position']) > 120) $errors[] = 'Unit dan jabatan maksimal 120 karakter.';
    if ($newPassword !== '' && strlen($newPassword) < 8) $errors[] = 'Password baru minimal 8 karakter.';
    if ($newPassword !== $confirmPassword) $errors[] = 'Konfirmasi password baru tidak sama.';
    if ($newPassword !== '' && $currentPassword === '') $errors[] = 'Masukkan password saat ini untuk mengganti password.';
    if ($newPassword !== '' && $currentPassword !== '') {
        $check = db()->prepare('SELECT password FROM users WHERE id=:id');
        $check->execute([':id'=>$userId]);
        if (!password_verify($currentPassword, (string)$check->fetchColumn())) $errors[] = 'Password saat ini tidak benar.';
    }
    if (!$hasAvatarUpload && $selectedAvatar !== 'keep' && !is_preset_avatar($selectedAvatar)) $errors[] = 'Pilihan avatar tidak valid.';
    if ($hasAvatarUpload) {
        if (($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) $errors[] = 'Upload foto profil gagal. Silakan coba lagi.';
        elseif ((int)($avatarFile['size'] ?? 0) > MAX_AVATAR_SIZE) $errors[] = 'Ukuran foto profil maksimal 3 MB.';
        elseif (!is_uploaded_file($avatarFile['tmp_name'] ?? '') || !isset($avatarFile['tmp_name']) || @getimagesize($avatarFile['tmp_name']) === false) $errors[] = 'File foto profil tidak valid.';
        else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (!in_array($finfo->file($avatarFile['tmp_name']), ['image/jpeg','image/png','image/webp','image/gif'], true)) $errors[] = 'Foto profil harus JPG, PNG, WEBP, atau GIF.';
        }
    }

    if (!$errors) {
        $newAvatar = $currentAvatar;
        $savedAvatar = null;
        try {
            if ($hasAvatarUpload) {
                $savedAvatar = save_uploaded_avatar($avatarFile);
                $newAvatar = $savedAvatar;
            } elseif (is_preset_avatar($selectedAvatar)) {
                $newAvatar = $selectedAvatar;
            }
            $sql = 'UPDATE users SET name=:name,email=:email,department=:department,position=:position,avatar=:avatar';
            $params = [':name'=>$profile['name'],':email'=>$profile['email'],':department'=>$profile['department'] ?: null,':position'=>$profile['position'] ?: null,':avatar'=>$newAvatar,':id'=>$userId];
            if ($newPassword !== '') { $sql .= ',password=:password'; $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT); }
            $sql .= ' WHERE id=:id';
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $_SESSION['user']['name'] = $profile['name'];
            $_SESSION['user']['email'] = $profile['email'];
            $_SESSION['user']['department'] = $profile['department'];
            $_SESSION['user']['position'] = $profile['position'];
            $_SESSION['user']['avatar'] = $newAvatar;
            if ($newAvatar !== $currentAvatar) delete_uploaded_avatar($currentAvatar);
            flash('success', 'Profil berhasil diperbarui.');
            redirect('profile.php');
        } catch (PDOException $e) {
            if ($savedAvatar) delete_uploaded_avatar($savedAvatar);
            $errors[] = $e->getCode() === '23000' ? 'Email tersebut sudah digunakan akun lain.' : 'Profil gagal disimpan. Silakan coba lagi.';
        } catch (RuntimeException $e) {
            if ($savedAvatar) delete_uploaded_avatar($savedAvatar);
            $errors[] = $e->getMessage();
        }
    }
}
require __DIR__ . '/partials/header.php';
?>
<div class="section-head page-head"><div><h2>Profil Saya</h2><p>Perbarui foto, identitas, dan informasi pekerjaanmu.</p></div></div>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="profile-layout"><section class="panel profile-summary"><div class="profile-avatar profile-avatar-photo"><img src="<?= e(avatar_url($userId)) ?>" alt="Foto profil <?= e($profile['name']) ?>"></div><h2><?= e($profile['name']) ?></h2><p><?= e($profile['position'] ?: 'Anggota instansi') ?></p><span class="role-pill <?= e($account['role']) ?>"><?= e(strtoupper($account['role'])) ?></span><div class="profile-summary-list"><div><span>Email pribadi</span><strong><?= e($profile['email']) ?></strong></div><div><span>Unit / Bagian</span><strong><?= e($profile['department'] ?: 'Belum diisi') ?></strong></div><div><span>Bergabung</span><strong><?= e(format_date_id($joined ? substr((string)$joined,0,10) : null)) ?></strong></div></div></section>
<section class="panel"><div class="panel-head"><div><h3>Informasi akun</h3><p>Foto dan informasi ini tampil pada profil serta detail kegiatan.</p></div></div><form method="post" enctype="multipart/form-data" class="field-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="full avatar-settings"><div class="avatar-settings-head"><div><h3>Foto profil</h3><p>Pilih avatar bawaan atau unggah foto milikmu sendiri.</p></div><span class="avatar-upload-limit">Maks. 3 MB</span></div>
<div class="avatar-picker" id="avatarPicker">
<?php if (!is_preset_avatar($currentAvatar)): ?><label class="avatar-option"><input type="radio" name="avatar_choice" value="keep" <?= $selectedAvatar==='keep'?'checked':'' ?>><span class="avatar-option-visual"><img src="<?= e(avatar_url($userId)) ?>" alt="Foto sekarang"></span><span class="avatar-option-name">Foto sekarang</span></label><?php endif; ?>
<?php foreach (preset_avatar_names() as $preset): ?><label class="avatar-option"><input type="radio" name="avatar_choice" value="<?= e($preset) ?>" <?= $selectedAvatar===$preset?'checked':'' ?>><span class="avatar-option-visual"><img src="<?= e(app_path('assets/avatars/'.preset_avatar_asset($preset).'.svg?v=20261012fruit2')) ?>" alt="Buah <?= e(preset_avatar_label($preset)) ?>"></span><span class="avatar-option-name"><?= e(preset_avatar_label($preset)) ?></span></label><?php endforeach; ?>
</div>
<label class="avatar-upload-control" for="avatarPhoto"><span class="avatar-upload-icon">↑</span><span class="avatar-upload-copy"><strong>Unggah foto sendiri</strong><small>JPG, PNG, WEBP, atau GIF. Foto upload akan menggantikan pilihan avatar.</small><input id="avatarPhoto" name="avatar_photo" type="file" accept="image/jpeg,image/png,image/webp,image/gif"></span></label><div id="avatarUploadPreview" class="avatar-upload-preview" hidden></div>
</div>
<label class="full">Nama lengkap<input name="name" required maxlength="100" value="<?= e($profile['name']) ?>"></label><label class="full">Email pribadi<input type="email" name="email" required maxlength="190" value="<?= e($profile['email']) ?>"></label><label>Unit / bagian<input name="department" maxlength="120" value="<?= e($profile['department']) ?>" placeholder="Contoh: IT"></label><label>Jabatan<input name="position" maxlength="120" value="<?= e($profile['position']) ?>" placeholder="Contoh: Staff"></label><div class="full"><hr class="profile-divider"><h3>Ganti password</h3><p class="field-help">Kosongkan bagian password jika tidak ingin menggantinya.</p></div><label class="full">Password saat ini<input type="password" name="current_password" autocomplete="current-password"></label><label>Password baru<input type="password" name="new_password" minlength="8" autocomplete="new-password"></label><label>Konfirmasi password baru<input type="password" name="confirm_password" minlength="8" autocomplete="new-password"></label><div class="full form-submit"><button class="btn btn-primary" type="submit">Simpan profil</button></div></form></section></div>
<?php require __DIR__ . '/partials/footer.php'; ?>
