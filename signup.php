<?php
// ----------------------------- signup.php (회 원 가 입  처 리 ) 
// -----------------------------
session_start(); ini_set('display_errors', 1); error_reporting(E_ALL); 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = $_POST['nickname'] ?? ''; $email = $_POST['email'] ?? 
    ''; $password = $_POST['password'] ?? ''; if (!$nickname || !$email 
    || !$password) {
        die("모든 필드를 입력해주세요.");
    }
    require_once '/var/www/dbinfo.php'; if 
    ($conn->connect_error) die("DB 연결 실패: " . $conn->connect_error); 
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?"); 
    $stmt->bind_param("s", $email); $stmt->execute(); 
    $stmt->store_result(); if ($stmt->num_rows > 0) {
        die("이미 가입된 이메일입니다.");
    }
    $stmt->close(); $hashed_pw = password_hash($password, 
    PASSWORD_DEFAULT); $stmt = $conn->prepare("INSERT INTO users 
    (username, email, password) VALUES (?, ?, ?)"); 
    $stmt->bind_param("sss", $nickname, $email, $hashed_pw); if 
    ($stmt->execute()) {
        header("Location: login.html"); exit;
    } else {
        die("회원 가입 실패: " . $stmt->error);
    }
}
?>
