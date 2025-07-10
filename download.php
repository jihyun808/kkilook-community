<?php
// 실제 저장된 파일명
$filename = basename($_GET['file'] ?? '');
// 사용자가 볼 원래 파일명
$original_name = basename($_GET['name'] ?? $filename); 

$filepath = '/var/www/kkilookCM_F/' . $filename;

// 파일 존재 확인
if (!file_exists($filepath)) {
    http_response_code(404);
    exit('파일을 찾을 수 없습니다.');
}

// 파일 다운로드 처리
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $original_name . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
?>
