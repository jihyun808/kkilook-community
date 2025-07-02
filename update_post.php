<!-- update_post.php -->
<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) die("로그인이 필요합니다.");

$id = (int)$_POST['id'];
$category = $_POST['category'] ?? '';
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$user_id = $_SESSION['user_id'];
$upload_dir = "uploads/";
$filename = null;
$original_name = null;

$conn = new mysqli("localhost", "webuser", "webpass", "user_db");
$conn->set_charset("utf8");

// 권한 확인
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post || $post['user_id'] != $user_id) die("수정 권한 없음");

// 파일 처리
if (isset($_FILES['file']) && $_FILES['file']['error'] === 0 && !empty($_FILES['file']['tmp_name'])) {
    // 기존 파일 삭제
    if (!empty($post['file_name']) && file_exists($upload_dir . $post['file_name'])) {
        unlink($upload_dir . $post['file_name']);
    }

    // 새 파일 저장
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $newname = uniqid("file_") . "." . $ext;
    move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $newname);

    $filename = $newname;
    $original_name = $_FILES['file']['name'];
} else {
    // 파일 변경 없으면 기존 데이터 유지
    $filename = $post['file_name'];
    $original_name = $post['original_name'];
}

// DB 업데이트
$stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, category = ?, file_name = ?, original_name = ? WHERE id = ?");
$stmt->bind_param("sssssi", $title, $content, $category, $filename, $original_name, $id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: view_post.php?id=$id");
exit;
?>
