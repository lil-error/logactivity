<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$id=(int)($_GET['id']??0);
$stmt=db()->prepare('SELECT file_name FROM activity_photos WHERE id=:id LIMIT 1');
$stmt->execute([':id'=>$id]);
$fileName=$stmt->fetchColumn();
if(!$fileName){http_response_code(404);exit('Foto tidak ditemukan.');}
$fullPath=UPLOAD_DIR.DIRECTORY_SEPARATOR.basename((string)$fileName);
if(!is_file($fullPath)){http_response_code(404);exit('File foto tidak tersedia di server.');}
$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($fullPath);
if(!in_array($mime,['image/jpeg','image/png','image/webp','image/gif'],true)){http_response_code(415);exit('Format foto tidak didukung.');}
header('Content-Type: '.$mime);header('Content-Length: '.filesize($fullPath));header('Content-Disposition: inline; filename="'.basename((string)$fileName).'"');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, max-age=3600');readfile($fullPath);exit;
