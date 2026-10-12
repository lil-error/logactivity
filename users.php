<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();
$page_title='Manajemen User'; $active_page='users'; $errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf($_POST['csrf_token']??null); $action=$_POST['action']??'';
 try{
  if($action==='create'){
   $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??''; $role=$_POST['role']??'staff'; $department=trim($_POST['department']??''); $position=trim($_POST['position']??'');
   if($name===''||mb_strlen($name)>100)$errors[]='Nama wajib diisi dan maksimal 100 karakter.';
   if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='Email tidak valid.';
   if(strlen($password)<8)$errors[]='Password minimal 8 karakter.';
   if(!in_array($role,['admin','staff'],true))$errors[]='Role tidak valid.'; if(mb_strlen($department)>120||mb_strlen($position)>120)$errors[]='Unit dan jabatan maksimal 120 karakter.';
   if(!$errors){$stmt=db()->prepare('INSERT INTO users(name,email,password,role,department,position) VALUES(:name,:email,:password,:role,:department,:position)');$stmt->execute([':name'=>$name,':email'=>$email,':password'=>password_hash($password,PASSWORD_DEFAULT),':role'=>$role,':department'=>$department?:null,':position'=>$position?:null]);flash('success','User berhasil dibuat.');redirect('users.php');}
  }
  if($action==='delete'){
   $id=(int)($_POST['id']??0); if($id===(int)user()['id'])throw new RuntimeException('Akun yang sedang dipakai tidak boleh dihapus.');
   $photoStmt=db()->prepare('SELECT p.file_name FROM activity_photos p JOIN activities a ON a.id=p.activity_id WHERE a.user_id=:id');$photoStmt->execute([':id'=>$id]);$files=$photoStmt->fetchAll(PDO::FETCH_COLUMN);
   $stmt=db()->prepare('DELETE FROM users WHERE id=:id');$stmt->execute([':id'=>$id]);foreach($files as $fileName)delete_uploaded_photo($fileName);flash('success','User dan dokumentasi kegiatannya berhasil dihapus.');redirect('users.php');
  }
 }catch(PDOException $e){$errors[]=$e->getCode()==='23000'?'Email tersebut sudah terdaftar.':'Gagal menyimpan user.';}catch(Throwable $e){$errors[]=$e->getMessage();}
}
$users=db()->query('SELECT u.id,u.name,u.email,u.role,u.department,u.position,u.created_at,COUNT(a.id) activity_count FROM users u LEFT JOIN activities a ON a.user_id=u.id GROUP BY u.id ORDER BY u.created_at DESC')->fetchAll();
require __DIR__ . '/partials/header.php';
?>
<div class="section-head page-head"><div><h2>Manajemen User</h2><p>Admin dapat membuat akun baru dan memantau jumlah activity tiap user.</p></div></div>
<?php if($errors): ?><div class="alert alert-error"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="admin-grid"><section class="panel"><div class="panel-head"><div><h3>Tambah User</h3><p>Buat akun secara manual bila diperlukan; anggota juga dapat mendaftar sendiri.</p></div></div><form method="post" class="field-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create"><label class="full">Nama<input required maxlength="100" name="name" placeholder="Nama lengkap"></label><label class="full">Email<input required type="email" name="email" placeholder="nama@instansi.go.id"></label><label>Unit / bagian<input maxlength="120" name="department" placeholder="Contoh: IT"></label><label>Jabatan<input maxlength="120" name="position" placeholder="Contoh: Staff"></label><label>Password<input required minlength="8" type="password" name="password" placeholder="Minimal 8 karakter"></label><label>Role<select name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select></label><div class="full form-submit"><button class="btn btn-primary" type="submit">＋ Buat User</button></div></form></section>
<section class="panel"><div class="panel-head"><div><h3>Daftar User</h3><p><?= count($users) ?> akun terdaftar.</p></div></div><div class="table-wrap"><table><thead><tr><th>Anggota</th><th>Role</th><th>Kegiatan</th><th>Dibuat</th><th></th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><div class="admin-user-cell"><div class="admin-user-avatar"><img src="<?= e(avatar_url((int)$u['id'])) ?>" alt=""></div><div><strong><?= e($u['name']) ?></strong><small><?= e($u['email']) ?></small><small><?= e($u['department']?:'Unit belum diisi') ?><?= $u['position']?' · '.e($u['position']):'' ?></small></div></div></td><td><span class="role-pill <?= e($u['role']) ?>"><?= e(strtoupper($u['role'])) ?></span></td><td><?= (int)$u['activity_count'] ?></td><td><?= e(format_date_id(substr($u['created_at'],0,10))) ?></td><td><?php if((int)$u['id']!==(int)user()['id']): ?><form method="post" onsubmit="return confirm('Hapus user ini? Activity user juga akan terhapus.')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="icon-danger" title="Hapus">×</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
<?php require __DIR__ . '/partials/footer.php'; ?>
