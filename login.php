<?php
// ----------------------------- login.php (로 그 인  처 리 ) 
// -----------------------------
session_start(); ini_set('display_errors', 1); error_reporting(E_ALL); 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? ''; $password = $_POST['password'] ?? 
    ''; require_once '/var/www/dbinfo.php'; if 
    ($conn->connect_error) die("DB 연결 실패: " . $conn->connect_error); 
    $stmt = $conn->prepare("SELECT id, username, password FROM users 
    WHERE email = ?"); $stmt->bind_param("s", $email); $stmt->execute(); 
    $result = $stmt->get_result(); if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); if (password_verify($password, 
        $user['password'])) {
            $_SESSION['user_id'] = $user['id']; $_SESSION['username'] = 
            $user['username']; header("Location: home.php"); exit;
        } else {
            die("비밀번호가 올바르지 않습니다.");
        }
    } else {
        die("존재하지 않는 이메일입니다.");
    }
}
?>
