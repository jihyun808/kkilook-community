<!-- category_2ch.php -->
<?php
ob_start();
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '/var/www/dbinfo.php';
if ($conn->connect_error) die("DB 연결 실패: " . $conn->connect_error);
$logged_in = isset($_SESSION['user_id']);
$logged_in_username = '';
if ($logged_in) {
  $uid = (int)$_SESSION['user_id'];
  $res = $conn->query("SELECT username FROM users WHERE id = $uid");
  if ($res && $userRow = $res->fetch_assoc()) {
    $logged_in_username = $userRow['username'];
  }
}

// 🟡 페이지 처리
$page = max(1, intval($_GET['page'] ?? 1)); // 1페이지부터 시작
$limit = 15;
$offset = ($page - 1) * $limit;

// 🔍 검색 필터
$query = $_GET['query'] ?? '';
$type = $_GET['type'] ?? 'title';
$sort = $_GET['sort'] ?? 'latest';

$allowed_categories = ['괴담', '자유게시판'];
$category = $_GET['category'] ?? '괴담';
if (!in_array($category, $allowed_categories)) {
    $category = '괴담';
}
$whereClauses = ["p.category = '" . $conn->real_escape_string($category) . "'"];

if ($query !== '') {
  if ($type === 'user') {
    $whereClauses[] = "u.username LIKE '%" . $conn->real_escape_string($query) . "%'";
  } else {
    $whereClauses[] = "p.title LIKE '%" . $conn->real_escape_string($query) . "%'";
  }
}
$where = 'WHERE ' . implode(' AND ', $whereClauses);

$order = 'ORDER BY p.created_at DESC';
if ($sort === 'oldest') {
  $order = 'ORDER BY p.created_at ASC';
}

// 전체 게시글 수 계산 (페이징용)
$count_sql = "
  SELECT COUNT(*) AS total 
  FROM posts p 
  JOIN users u ON p.user_id = u.id 
  $where
";
$count_result = $conn->query($count_sql);
$total_posts = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $limit);

// 게시글 목록 쿼리
$sql = "
  SELECT p.id, p.title, p.created_at, p.category, u.username 
  FROM posts p 
  JOIN users u ON p.user_id = u.id 
  $where
  $order
  LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>끼룩끼룩 - 괴담</title>
  <link rel="stylesheet" href="home.css" />
  <link href="https://fonts.googleapis.com/css2?family=Gowun+Dodum&display=swap" rel="stylesheet" />
</head>

<body>
  <header>
    <div class="Top">
      <h1><a href="home.php">🐦‍⬛</a> 괴담</h1>
      <div class="myPage">
        <?php if ($logged_in): ?>
          <span><?php echo htmlspecialchars($logged_in_username); ?>님</span>
          <a href="logout.php">로그아웃</a>
        <?php else: ?>
          <a href="signup.html">회원가입</a>
          <a href="login.html">로그인</a>
        <?php endif; ?>
      </div>
    </div>
    <nav>
      <div class="category">
        <a href="category_free.php?category=자유게시판">자유게시판</a>
        <a href="category_2ch.php?category=괴담">괴담</a>

      </div>
      <div class="exTop">
        <!-- nav 검색 부분 -->
        <form method="GET" class="search-form">
          <div class="search-input-group">
            <input type="text" name="query" placeholder="검색어를 입력하세요" value="<?= htmlspecialchars($_GET['query'] ?? '') ?>" />
            <select name="type">
              <option value="title" <?= ($_GET['type'] ?? '') === 'title' ? 'selected' : '' ?>>제목</option>
              <option value="user" <?= ($_GET['type'] ?? '') === 'user' ? 'selected' : '' ?>>유저명</option>
            </select>
            <button type="submit">검색</button>
          </div>
          <div class="search-sort" style="margin-top: 5px;">
            <select name="sort" onchange="this.form.submit()">
              <option value="latest" <?= ($_GET['sort'] ?? '') === 'latest' ? 'selected' : '' ?>>최신순</option>
              <option value="oldest" <?= ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>오래된순</option>
            </select>
          </div>
        </form>
      </div>
    </nav>
  </header>

  <main id="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <p style="text-align: center; margin: 20px 0;">총 <?= $total_posts ?>개의 글이 있습니다.</p>
      <?php if ($logged_in): ?>
        <a href="write.php" class="writeBtn">✍ 글쓰기</a>
      <?php else: ?>
        <a href="login.html" class="writeBtn">✍ 글쓰기</a>
      <?php endif; ?>
    </div>
    <div id="wrapper">
      <div class="exPage">
      <?php
      if ($result->num_rows === 0): ?>
        <p>작성된 글이 없습니다.</p>
        <?php else:
        while ($row = $result->fetch_assoc()): ?>
        <div class="post-preview">
          <a href="view_post.php?id=<?php echo $row['id']; ?>">
            <?php echo htmlspecialchars($row['title']); ?>
          </a><br>
          <small>작성자: <?php echo htmlspecialchars($row['username']); ?> | 작성 시간: <?php echo $row['created_at']; ?> | <?php echo htmlspecialchars($row['category']); ?></small>
        </div>
        <?php endwhile; endif; ?>
      </div>
      <?php if ($total_pages > 1): ?>
        <div class="page" style="text-align:center; margin-top: 30px;">
          <span>- </span>
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
               style="margin: 0 5px; color: black; <?= $i == $page ? 'font-weight:bold; text-decoration:underline;' : '' ?>">
               <?= $i ?>
            </a>
          <?php endfor; ?>
          <span> -</span>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <footer>
    <p>© 끼룩끼룩 | 고객센터: 1234-5678</p>
  </footer>
</body>
</html>
