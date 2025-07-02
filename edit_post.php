<!-- edit_post.php -->

<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
  die("로그인이 필요합니다.");
}

$conn = new mysqli("localhost", "webuser", "webpass", "user_db");
$conn->set_charset("utf8");

$post_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// 게시글 정보 조회
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) die("해당 게시글이 존재하지 않습니다.");
if ($post['user_id'] != $user_id) die("수정 권한이 없습니다.");
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>끼룩끼룩 - 게시글 수정</title>
  <link rel="stylesheet" href="write.css" />
  <link href="https://fonts.googleapis.com/css2?family=Gowun+Dodum&display=swap" rel="stylesheet" />
</head>
<body>
  <h2>📑 게시글 수정</h2>
  <form action="update_post.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $post_id ?>">
    
    <label>카테고리</label>
    <select name="category">
      <option value="자유게시판" <?= $post['category'] === '자유게시판' ? 'selected' : '' ?>>자유게시판</option>
      <option value="괴담" <?= $post['category'] === '괴담' ? 'selected' : '' ?>>괴담</option>
    </select>

    <label>제목</label>
    <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>

    <label>내용</label>
    <textarea name="content" required><?= htmlspecialchars($post['content']) ?></textarea>

    <label>기존 첨부파일: <?= $post['original_name'] ?: '없음' ?></label><br>
    <label>새 파일 첨부</label>
    <input type="file" name="file">

    <div class="form-buttons">
      <button type="submit">수정 완료</button>
      <button type="button" onclick="location.href='view_post.php?id=<?= $post_id ?>'">취소</button>
    </div>
  </form>
</body>
</html>
