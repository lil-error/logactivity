<?php
require_once __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$activity = get_activity($id, false);
if (!$activity) { flash('error', 'Kegiatan tidak ditemukan.'); redirect('activities.php'); }
$photos = get_activity_photos($id);
$canManage = can_manage_activity($activity);
$page_title = 'Detail Kegiatan';
$active_page = 'activities';
require __DIR__ . '/partials/header.php';
?>
<div class="section-head page-head detail-page-heading">
  <div>
    <a class="back-link" href="activities.php">← Kembali ke aktivitas</a>
    <h2><?= e($activity['title']) ?></h2>
    <p>Dokumentasi kegiatan dan catatan anggota instansi.</p>
  </div>
  <div class="section-actions">
    <?php if ($canManage): ?><a href="activity_form.php?id=<?= (int)$id ?>" class="btn btn-primary">Edit kegiatan</a><?php endif; ?>
    <?php if (is_logged_in()): ?><a href="gallery.php?activity_id=<?= (int)$id ?>" class="btn btn-secondary">Lihat galeri</a><?php else: ?><a href="login.php?next=<?= rawurlencode('gallery.php?activity_id='.(int)$id) ?>" class="btn btn-secondary">Lihat galeri</a><?php endif; ?>
  </div>
</div>

<section class="panel detail-combined" aria-label="Dokumentasi dan informasi kegiatan">
  <div class="detail-combined-media">
    <div class="detail-combined-section-head">
      <div><h3>Foto dokumentasi</h3><p><?= count($photos) ?> foto tersimpan pada server.</p></div>
      <?php if (is_logged_in() && $canManage): ?><a class="text-link" href="activity_form.php?id=<?= (int)$id ?>">Kelola foto ↗</a><?php endif; ?>
    </div>
    <?php if (!$photos): ?>
      <div class="detail-media-empty"><span class="detail-empty-icon">▧</span><strong>Belum ada foto dokumentasi</strong><p>Foto akan muncul di bagian ini setelah kegiatan ditambahkan dokumentasinya.</p><?php if ($canManage): ?><a class="btn btn-primary" href="activity_form.php?id=<?= (int)$id ?>">Tambah foto</a><?php endif; ?></div>
    <?php elseif (!is_logged_in()): ?>
      <div class="photo-access-card detail-photo-access"><div class="photo-access-icon">⌑</div><h4>Dokumentasi khusus anggota</h4><p>Kegiatan ini memiliki <?= count($photos) ?> foto. Silakan login atau buat akun untuk membuka foto dokumentasi.</p><div class="public-hero-actions"><a class="btn btn-primary" href="login.php?next=<?= rawurlencode('activity_view.php?id='.(int)$id) ?>">Login untuk melihat foto</a><a class="btn btn-secondary" href="register.php?next=<?= rawurlencode('activity_view.php?id='.(int)$id) ?>">Buat akun</a></div></div>
    <?php else: ?>
      <div class="detail-photo-grid">
      <?php foreach ($photos as $index => $photo): $src = photo_url((int)$photo['id']); ?>
        <a href="<?= e($src) ?>" data-lightbox-src="<?= e($src) ?>" data-lightbox-alt="Foto <?= e($activity['title']) ?> — dokumentasi <?= $index + 1 ?>" class="detail-photo" title="Lihat foto ukuran besar"><img loading="lazy" src="<?= e($src) ?>" alt="Foto dokumentasi kegiatan"><span>Perbesar ⤢</span></a>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="detail-combined-info">
    <div class="detail-meta"><span class="tag"><?= e($activity['category']) ?></span><span>📅 <?= e(format_date_id($activity['activity_date'])) ?></span><span>◷ <?= e($activity['activity_time'] ? format_time($activity['activity_time']) : 'Tanpa waktu') ?></span><?php if ($activity['location']): ?><span>⌖ <?= e($activity['location']) ?></span><?php endif; ?></div>
    <div class="detail-info-block"><span class="detail-kicker">RINGKASAN KEGIATAN</span><h3>Catatan kegiatan</h3><div class="detail-description"><?= nl2br(e($activity['description'] ?: 'Belum ada catatan tambahan untuk kegiatan ini.')) ?></div></div>
    <div class="detail-author detail-author-unified">
      <div class="profile-avatar small-avatar profile-avatar-photo"><img src="<?= e(avatar_url((int)$activity['user_id'])) ?>" alt="Foto profil <?= e($activity['user_name']) ?>"></div>
      <div class="detail-author-copy"><span class="detail-kicker">DIUNGGAH OLEH</span><strong><a href="user_profile.php?id=<?= (int)$activity['user_id'] ?>"><?= e($activity['user_name']) ?></a></strong><span><?= e($activity['user_position'] ?: 'Anggota') ?><?= $activity['user_department'] ? ' · '.e($activity['user_department']) : '' ?></span></div>
      <a class="detail-author-profile" href="user_profile.php?id=<?= (int)$activity['user_id'] ?>" aria-label="Lihat profil anggota">Profil ↗</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
