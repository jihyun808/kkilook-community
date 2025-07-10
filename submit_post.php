<!-- submit_post.php -->

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
error_log("🚀 submit_post.php 실행됨");
session_start();



if (isset($_FILES['file'])) {
    error_log("파일 업로드 상태: " . $_FILES['file']['error']);
    error_log("파일 이름: " . $_FILES['file']['name']);
    error_log("임시 경로: " . $_FILES['file']['tmp_name']);
    error_log("파일 크기: " . $_FILES['file']['size']);

    if (!empty($_FILES['file']['tmp_name'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
        finfo_close($finfo);
        error_log("MIME 타입: " . $mime);
    } else {
        error_log("⚠️ tmp_name 값이 비어있습니다. 파일이 제대로 업로드되지 않았을 가능성이 높습니다.");
    }
}



if (!isset($_SESSION['user_id'])) {
  die("로그인이 필요합니다.");
}

$category = $_POST['category'] ?? '';
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
if ($content === '' && empty($_FILES['file']['name'])) {
  die("<script>alert('본문이나 첨부파일 중 하나는 입력해야 합니다.'); history.back();</script>");
}
$user_id = $_SESSION['user_id'];

$upload_dir = __DIR__ . '/../kkilookCM_F/';

if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0755, true);
}

$original_name = null;
$filename = null;

if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
  $original_name = $_FILES['file']['name'];

  $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
  $dangerous_exts = ['php', 'exe', 'js', 'sh', 'bat', 'cgi', 'pl'];
  
  if (in_array($ext, $dangerous_exts)) {
    die("<script>alert('🚫 이 형식의 파일은 업로드할 수 없습니다.'); history.back();</script>");
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
  finfo_close($finfo);

  $allowed_mime_types = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
    'application/pdf',
    'application/zip',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/octet-stream',
    'application/x-hwp'
  ];

  if (!in_array($mime, $allowed_mime_types)) {
    die("<script>alert('❌ 지원하지 않는 파일 형식입니다. (" . htmlspecialchars($mime) . ")'); history.back();</script>");
  }

  $newname = uniqid("file_") . "." . $ext;
  $filepath = $upload_dir . $newname;

  if (!move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
    die("파일 업로드에 실패했습니다.");
  }

  $filename = $newname; 
}

require_once '/var/www/dbinfo.php';
if ($conn->connect_error) {
  die("DB 연결 실패: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$stmt = $conn->prepare("INSERT INTO posts (title, content, category, user_id, file_name, original_name, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssiss", $title, $content, $category, $user_id, $filename, $original_name);
$stmt->execute();

$stmt->close();
$conn->close();

// 페이지 이동
header("Location: category_" . ($category === "자유게시판" ? "free" : "2ch") . ".php");
exit;
?>
