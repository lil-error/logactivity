<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$id=(int)($_GET['id']??0); $editing=$id>0; $activity=$editing?get_activity($id):null;
if($editing && !$activity){ flash('error','Activity tidak ditemukan atau kamu tidak memiliki izin untuk mengeditnya.'); redirect('activities.php'); }
$page_title=$editing?'Edit Activity':'Tambah Activity'; $active_page='activities';
$old=$activity?:['title'=>'','activity_date'=>date('Y-m-d'),'activity_time'=>date('H:i'),'category'=>'Development','location'=>'','description'=>''];
$errors=[]; $existingPhotos=$editing?get_activity_photos($id):[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $title=trim($_POST['title']??''); $activityDate=trim($_POST['activity_date']??''); $activityTime=trim($_POST['activity_time']??'');
    $category=trim($_POST['category']??'Lainnya'); $location=trim($_POST['location']??''); $description=trim($_POST['description']??'');
    $old=array_merge($old,['title'=>$title,'activity_date'=>$activityDate,'activity_time'=>$activityTime,'category'=>$category,'location'=>$location,'description'=>$description]);
    if($title===''||mb_strlen($title)>150)$errors[]='Judul kegiatan wajib diisi dan maksimal 150 karakter.';
    if(!valid_date($activityDate))$errors[]='Tanggal kegiatan tidak valid.';
    if($activityTime!==''&&!preg_match('/^\d{2}:\d{2}$/',$activityTime))$errors[]='Waktu kegiatan tidak valid.';
    if($category===''||mb_strlen($category)>50)$errors[]='Kategori wajib diisi.';
    if(mb_strlen($location)>150)$errors[]='Lokasi maksimal 150 karakter.';
    if(mb_strlen($description)>5000)$errors[]='Catatan maksimal 5000 karakter.';
    $removeIds=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['remove_photos']??[])),fn($v)=>$v>0)));
    if(!$errors){
        $newFiles=[]; $deleteAfterCommit=[]; $inTransaction=false;
        try{
            $newFiles=save_uploaded_photos($_FILES['photos']??null);
            $currentPhotos=$editing?get_activity_photos($id):[];
            if(count($newFiles)>MAX_PHOTOS_PER_REQUEST)$errors[]='Jumlah foto terlalu banyak.';
            if(count($newFiles)+count($currentPhotos)-count(array_intersect(array_column($currentPhotos,'id'),$removeIds))>60)$errors[]='Maksimal 60 foto per kegiatan.';
            if($errors){ foreach($newFiles as $f)delete_uploaded_photo($f); }
            else {
                db()->beginTransaction(); $inTransaction=true;
                if($editing){
                    $stmt=db()->prepare('UPDATE activities SET title=:title,activity_date=:activity_date,activity_time=:activity_time,category=:category,location=:location,description=:description WHERE id=:id');
                    $stmt->execute([':title'=>$title,':activity_date'=>$activityDate,':activity_time'=>$activityTime?:null,':category'=>$category,':location'=>$location?:null,':description'=>$description?:null,':id'=>$id]);
                    $activityId=$id;
                }else{
                    $stmt=db()->prepare('INSERT INTO activities(user_id,title,activity_date,activity_time,category,location,description) VALUES(:user_id,:title,:activity_date,:activity_time,:category,:location,:description)');
                    $stmt->execute([':user_id'=>(int)user()['id'],':title'=>$title,':activity_date'=>$activityDate,':activity_time'=>$activityTime?:null,':category'=>$category,':location'=>$location?:null,':description'=>$description?:null]);
                    $activityId=(int)db()->lastInsertId();
                }
                if($newFiles){$photoStmt=db()->prepare('INSERT INTO activity_photos(activity_id,file_name) VALUES(:activity_id,:file_name)');foreach($newFiles as $fileName)$photoStmt->execute([':activity_id'=>$activityId,':file_name'=>$fileName]);}
                if($removeIds){
                    $find=db()->prepare('SELECT id,file_name FROM activity_photos WHERE activity_id=:activity_id AND id=:photo_id');
                    $remove=db()->prepare('DELETE FROM activity_photos WHERE activity_id=:activity_id AND id=:photo_id');
                    foreach($removeIds as $photoId){$find->execute([':activity_id'=>$activityId,':photo_id'=>$photoId]);$found=$find->fetch();if($found){$deleteAfterCommit[]=$found['file_name'];$remove->execute([':activity_id'=>$activityId,':photo_id'=>$photoId]);}}
                }
                db()->commit(); $inTransaction=false;
                foreach($deleteAfterCommit as $f)delete_uploaded_photo($f);
                flash('success',$editing?'Kegiatan dan dokumentasinya berhasil diperbarui.':'Kegiatan berhasil disimpan.');
                redirect('activity_view.php?id='.$activityId);
            }
        }catch(Throwable $e){if($inTransaction&&db()->inTransaction())db()->rollBack();foreach($newFiles as $f)delete_uploaded_photo($f);$errors[]=$e instanceof RuntimeException?$e->getMessage():'Terjadi kesalahan saat menyimpan kegiatan. Pastikan database sudah menggunakan skema terbaru.';}
    }
    $existingPhotos=$editing?get_activity_photos($id):[];
}
require __DIR__ . '/partials/header.php';
?>
<div class="form-page"><form method="post" enctype="multipart/form-data" class="large-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-page-head"><div><a href="<?= $editing?'activity_view.php?id='.(int)$id:'activities.php' ?>" class="back-link">← Kembali</a><h2><?= $editing?'Edit kegiatan':'Catat kegiatan baru' ?></h2><p><?= $editing?'Perbarui informasi dan dokumentasi kegiatan.':'Bagikan kegiatan dan bukti dokumentasinya ke workspace instansi.' ?></p></div><div class="form-page-actions"><a href="<?= $editing?'activity_view.php?id='.(int)$id:'activities.php' ?>" class="btn btn-ghost">Batal</a><button class="btn btn-primary" type="submit"><?= $editing?'Simpan perubahan':'Simpan kegiatan' ?></button></div></div>
<?php if($errors): ?><div class="alert alert-error"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="form-layout"><section class="panel"><div class="panel-head"><div><h3>Detail Kegiatan</h3><p>Informasi pokok yang akan dilihat anggota instansi.</p></div></div><div class="field-grid">
<label class="full">Judul kegiatan <span>*</span><input name="title" maxlength="150" required value="<?= e($old['title']) ?>" placeholder="Contoh: Pendampingan input data"></label>
<label>Tanggal <span>*</span><input type="date" name="activity_date" required value="<?= e($old['activity_date']) ?>"></label><label>Waktu<input type="time" name="activity_time" value="<?= e($old['activity_time']) ?>"></label>
<label class="full">Kategori<select name="category"><?php foreach(['Administrasi','Development','Meeting','Design','Research','Testing','Dokumentasi','Training','Pelayanan','Lapangan','Lainnya'] as $cat): ?><option value="<?= e($cat) ?>" <?= ($old['category']??'')===$cat?'selected':'' ?>><?= e($cat) ?></option><?php endforeach; ?></select></label>
<label class="full">Lokasi kegiatan<input name="location" maxlength="150" value="<?= e($old['location']) ?>" placeholder="Contoh: Kantor utama / ruang rapat / lapangan"></label>
<label class="full">Deskripsi / hasil kegiatan<textarea name="description" maxlength="5000" rows="8" placeholder="Tuliskan kegiatan yang dilakukan, hasil, catatan, atau tindak lanjut."><?= e($old['description']) ?></textarea><small class="field-help">Maksimal 5000 karakter.</small></label>
</div></section>
<section class="panel"><div class="panel-head"><div><h3>Dokumentasi Foto</h3><p>Bisa memilih beberapa foto sekaligus. Maksimal 5 MB per foto.</p></div></div>
<label class="upload-box" for="photos"><input id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple><div class="upload-symbol">↑</div><strong>Pilih beberapa foto sekaligus</strong><span>JPG, PNG, WEBP, GIF · Maks. 6 foto per upload</span></label>
<div id="photoPreview" class="multi-photo-preview"><span class="preview-empty">Preview foto yang dipilih akan muncul di sini.</span></div>
<?php if($existingPhotos): ?><div class="existing-photo-head"><h4>Foto yang sudah tersimpan (<?= count($existingPhotos) ?>)</h4><small>Centang foto jika ingin menghapusnya saat disimpan.</small></div><div class="existing-photo-grid"><?php foreach($existingPhotos as $photo): ?><label class="existing-photo"><img src="<?= e(photo_url((int)$photo['id'])) ?>" alt="Dokumentasi kegiatan"><span><input type="checkbox" name="remove_photos[]" value="<?= (int)$photo['id'] ?>"> Hapus foto</span><button type="button" class="existing-photo-view" data-lightbox-src="<?= e(photo_url((int)$photo['id'])) ?>" data-lightbox-alt="Dokumentasi kegiatan">Lihat ukuran penuh ⤢</button></label><?php endforeach; ?></div><?php endif; ?>
</section></div></form></div>
<?php require __DIR__ . '/partials/footer.php'; ?>
