<!-- write.php -->
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>끼룩끼룩 - 글쓰기</title>
  <link rel="stylesheet" href="write.css" />
  <link href="https://fonts.googleapis.com/css2?family=Gowun+Dodum&display=swap" rel="stylesheet" />
</head>
<body>
  <h1>✍ 글쓰기</h1>

  <form action="submit_post.php" method="post" enctype="multipart/form-data">
    <label for="category">카테고리</label>
    <select name="category" id="category" required>
      <option value="자유게시판">자유게시판</option>
      <option value="괴담">괴담</option>
    </select>

    <label for="title">제목</label>
    <input type="text" id="title" name="title" placeholder="제목을 입력하세요" required />

    <label for="content">내용</label>
    <textarea id="content" name="content" placeholder="내용을 입력하세요" required></textarea>

    <!-- 파일 업로드 부분 -->
    <label for="file">파일 첨부</label>
    <input type="file" id="file" name="file" accept="image/*,video/*,.pdf,.docx,.pptx,.xlsx,.hwp" />
    <div id="preview"></div>


    <div class="form-buttons">
      <button type="submit">작성 완료</button>
      <button type="reset">초기화</button>
    </div>

  </form>
  <a href="home.php" class="back-link">← 돌아가기</a>

  <script>
  const fileInput = document.getElementById('file');
  const preview = document.getElementById('preview');

  fileInput.addEventListener('change', function () {
    const file = this.files[0];
    preview.innerHTML = '';

    if (!file) return;

    const filename = document.createElement('p');
    filename.textContent = "첨부파일: " + file.name;
    preview.appendChild(filename);

    const fileType = file.type;

    const reader = new FileReader();
    reader.onload = function (e) {
      const url = e.target.result;

      if (fileType.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = url;
        img.style.maxWidth = '100%';
        preview.appendChild(img);
      } else if (fileType.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = url;
        video.controls = true;
        video.style.maxWidth = '100%';
        preview.appendChild(video);
      } else {
        const link = document.createElement('a');
        link.href = url;
        link.textContent = "📎 첨부파일 다운로드";
        link.download = file.name;
        preview.appendChild(link);
      }
    };

    reader.readAsDataURL(file);
  });
</script>

</body>
</html>
