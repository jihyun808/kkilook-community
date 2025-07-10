<!-- view_post.php -->
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once '/var/www/dbinfo.php';
if ($conn->connect_error) die("DB 연결 실패: " . $conn->connect_error);
$conn->set_charset("utf8");

// 로그인 여부
$logged_in = isset($_SESSION['user_id']);
$my_id = $_SESSION['user_id'] ?? null;

// 게시글 ID 확인
$post_id = $_GET['id'] ?? null;
if (!$post_id) die("게시글 ID가 없습니다.");

// 👉 게시글 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
  $delete_post_id = $_POST['delete_post_id'];

  // 작성자 본인 확인
  $stmt = $conn->prepare("SELECT file_name, user_id FROM posts WHERE id = ?");
  $stmt->bind_param("i", $delete_post_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $post_data = $result->fetch_assoc();
  $stmt->close();

  if (!$post_data) die("게시글이 존재하지 않습니다.");
  if ($post_data['user_id'] != $my_id) die("삭제 권한이 없습니다.");

  // 첨부파일 삭제
  $filename = basename($post_data['file_name']);
  if (!empty($filename) && file_exists("kkilookCM_F/" . $filename)) {
      unlink("kkilookCM_F/" . $filename);
}

  // 댓글 삭제
  $stmt = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
  $stmt->bind_param("i", $delete_post_id);
  $stmt->execute();
  $stmt->close();

  // 게시글 삭제
  $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
  $stmt->bind_param("i", $delete_post_id);
  $stmt->execute();
  $stmt->close();

  $conn->close();

  // 목록으로 이동
  header("Location: home.php");
  exit;
}

// 게시글 정보
$stmt = $conn->prepare("SELECT p.*, category, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) die("게시글을 찾을 수 없습니다.");

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <link href="https://fonts.googleapis.com/css2?family=Gowun+Dodum&display=swap" rel="stylesheet" />
  <title><?php echo htmlspecialchars($post['title']); ?> - <?php echo htmlspecialchars($post['category']); ?></title>
  <style>
    body { font-family: 'Gowun Dodum', sans-serif; padding: 20px; max-width: 800px; margin: auto; }
    h1 { font-size: 24px; }
    .meta { color: #666; font-size: 14px; margin-bottom: 10px; }
    .content { margin-bottom: 30px; white-space: pre-line; }
    .file { margin-top: 10px; }
    .controls { margin-bottom: 20px; }
    .controls form { display: inline-block; margin-right: 10px; }
    .controls a { display: inline-block; text-decoration: none; color: #007bff; margin-right: 10px; margin-top: 5px; }
    .comment-box, .comment-edit-form { margin-top: 20px; }
    .comment { border-top: 1px solid #ccc; padding: 10px 0; }
    .comment small { color: #666; }
    .comment-actions { display: inline; float: right; }
    textarea { width: 100%; padding: 8px; box-sizing: border-box; resize: vertical; }
    a.back-link { display: inline-block; margin-top: 20px; color: #007bff; text-decoration: none; }
    .cmtBtn { padding: 6px 12px; background: #007bff; border: none; color: white; border-radius: 4px; cursor: pointer; }
    .cmtBtn:hover { background: #0056b3; }
    .controls button { color: #007bff; border: none; cursor: pointer; background: none; font-size: 16px; font-family: 'Gowun Dodum', sans-serif;}
    .post-media { max-width: 300px; max-height: 300px; width: auto; height: auto; display: block; margin-top: 10px; }

  </style>
</head>
<body>
  <h1><?php echo htmlspecialchars($post['title']); ?></h1>
  <div class="meta">작성자: <?php echo htmlspecialchars($post['username']); ?> | 작성 시간: <?php echo $post['created_at']; ?> | <?php echo htmlspecialchars($post['category']); ?></div>
  <div class="content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>

        <?php if (!empty($post['file_name'])): ?>
  <div class="file">
    <?php
      $ext = strtolower(pathinfo($post['file_name'], PATHINFO_EXTENSION));
      $file_url = 'download.php?file=' . urlencode($post['file_name']) . '&name=' . urlencode($post['original_name']);

      $img_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      $video_exts = ['mp4', 'webm', 'ogg', 'mov'];
      $mime_map = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
      ];
    ?>

    <?php if (in_array($ext, $img_exts)): ?>
      <img src="<?= $file_url ?>" alt="첨부 이미지" class="post-media">
    <?php elseif (in_array($ext, $video_exts)): ?>
      <video controls autoplay muted preload="auto" class="post-media">
        <source src="<?= $file_url ?>" type="video/mp4">
        이 브라우저는 영상을 지원하지 않습니다.
      </video>


    <?php endif; ?>
     <p>📎 첨부파일: <a href="<?= $file_url ?>"><?= htmlspecialchars($post['original_name']) ?></a></p>
  </div>
<?php endif; ?>

  <?php if ($logged_in && $my_id == $post['user_id']): ?>
  <div class="controls">
    <a href="edit_post.php?id=<?php echo $post_id; ?>">✏️ 수정</a>
    <form method="post" onsubmit="return confirm('정말 삭제하시겠습니까?');" style="display:inline-block;">
      <input type="hidden" name="delete_post_id" value="<?php echo $post_id; ?>">
      <button type="submit">🗑 삭제</button>
    </form>
  </div>
<?php endif; ?>

  <hr>
  <br>
  <h4>💬 댓글</h4>

  <?php
  // 댓글 등록
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $comment = trim($_POST['comment_content']);
    if ($logged_in && $comment !== '') {
      $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iis", $post_id, $my_id, $comment);
      $stmt->execute();
      $stmt->close();
      header("Location: view_post.php?id=$post_id");
      exit;
    }
  }

  // 댓글 수정
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment_id'])) {
    $comment_id = $_POST['edit_comment_id'];
    $edited = trim($_POST['edited_content']);
    if ($edited !== '') {
    $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $edited, $comment_id, $my_id);
    $stmt->execute();
    $stmt->close();
    }
    header("Location: view_post.php?id=$post_id");
    exit;
  }

  // 댓글 삭제
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $comment_id = $_POST['delete_comment_id'];
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $my_id);
    $stmt->execute();
    $stmt->close();
    header("Location: view_post.php?id=$post_id");
    exit;
  }

  // 댓글 목록
  $stmt = $conn->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE post_id = ? ORDER BY c.created_at ASC");
  $stmt->bind_param("i", $post_id);
  $stmt->execute();
  $comments = $stmt->get_result();
  ?>

  <?php while ($row = $comments->fetch_assoc()): ?>
  <div class="comment" id="comment-<?php echo $row['id']; ?>">
    <div style="display: block;">
    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
    <small><?php echo $row['created_at']; ?></small>
    </div>
    <div class="comment-content" id="content-<?php echo $row['id']; ?>" style="display: inline;">
      <?php echo nl2br(htmlspecialchars($row['content'])); ?>
    </div>

    <?php if ($logged_in && $my_id == $row['user_id']): ?>
      <div class="comment-actions">
        <button type="button" onclick="toggleEdit(<?php echo $row['id']; ?>)" class="cmtBtn">수정</button>
        <form method="post" onsubmit="return confirm('댓글을 삭제할까요?');" style="display:inline-block;">
          <input type="hidden" name="delete_comment_id" value="<?php echo $row['id']; ?>">
          <button type="submit" class="cmtBtn">삭제</button>
        </form>
      </div>

      <form method="post" id="edit-form-<?php echo $row['id']; ?>" style="display:none; margin-top: 5px;">
        <input type="hidden" name="edit_comment_id" value="<?php echo $row['id']; ?>">
        <textarea name="edited_content" rows="2"><?php echo htmlspecialchars($row['content']); ?></textarea><br>
        <button type="submit" class="cmtBtn">수정</button>
        <button type="button" class="cmtBtn" onclick="closeEdit(<?php echo $row['id']; ?>)">취소</button>
      </form>
    <?php endif; ?>
  </div>
<?php endwhile; ?>

<script>
  function closeEdit(commentId) {
    const form = document.getElementById('edit-form-' + commentId);
    if (form) {
      // 입력 내용 초기화
      const textarea = form.querySelector('textarea');
      if (textarea) {
        // 원래 댓글 내용을 comment-content에서 가져와서 복원
        const original = document.getElementById('content-' + commentId);
        if (original) {
          textarea.value = original.textContent.trim();
        }
      }
      form.style.display = 'none';
    }
  }

  function toggleEdit(commentId) {
    document.querySelectorAll('[id^="edit-form-"]').forEach(form => {
    form.style.display = 'none';
    });
    
    const form = document.getElementById('edit-form-' + commentId);
    form.querySelector('textarea').focus();
    form.style.display = 'block';
  }
</script>


  <?php if ($logged_in): ?>
    <div class="comment-box">
      <form method="post">
        <textarea name="comment_content" placeholder="댓글을 입력하세요" required></textarea><br>
        <button type="submit" class="cmtBtn">댓글 작성</button>
      </form>
    </div>
  <?php else: ?>
    <p style="font-weight: bold;">댓글을 작성하려면 <a href="login.html" style="text-decoration: underline; color: black;">로그인</a>이 필요합니다.</p>
  <?php endif; ?>

  <a href="home.php" class="back-link">← 목록으로</a>
</body>
</html>
