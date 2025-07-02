<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (!$email) {
        die("❌ 이메일을 입력해주세요.");
    }

    // DB 연결
    $conn = new mysqli("localhost", "webuser", "webpass", "user_db");
    if ($conn->connect_error) {
        die("❌ DB 연결 실패: " . $conn->connect_error);
    }

    // 사용자 비밀번호 조회
    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute(); 
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        echo "<script>alert('비밀번호는 {$user['password']} 입니다.'); history.back();</script>";
    } else {
        echo "<script>alert('❌ 존재하지 않는 이메일입니다.'); history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
