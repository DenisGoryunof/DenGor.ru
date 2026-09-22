<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
header('Content-Type: application/json');

if (empty($_FILES['file'])) { echo json_encode(['error' => 'no file']); exit; }
$path = upload_image($_FILES['file']);
echo json_encode($path ? ['path' => $path, 'url' => img_url($path)] : ['error' => 'upload failed']);