<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/pop_settings.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode((string) $raw, true);
$settings = pop_settings_prune_expired(pop_settings_normalize($input));

if (!pop_settings_save($settings)) {
    echo json_encode(['success' => false, 'message' => '저장에 실패했습니다.']);
    exit;
}

echo json_encode([
    'success' => true,
    'settings' => $settings,
]);
