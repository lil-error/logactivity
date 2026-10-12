<?php
require_once __DIR__ . '/includes/functions.php';require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('activities.php');verify_csrf($_POST['csrf_token']??null);
$id=(int)($_POST['id']??0);$activity=get_activity($id);
if(!$activity){flash('error','Kegiatan tidak ditemukan atau kamu tidak memiliki izin untuk menghapusnya.');redirect('activities.php');}
$photos=get_activity_photos($id);$stmt=db()->prepare('DELETE FROM activities WHERE id=:id');$stmt->execute([':id'=>$id]);foreach($photos as $photo)delete_uploaded_photo($photo['file_name']);flash('success','Kegiatan dan seluruh foto dokumentasi berhasil dihapus.');redirect('activities.php');
