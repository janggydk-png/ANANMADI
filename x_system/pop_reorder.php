<?php
session_start();
require_once __DIR__ . '/pop_settings.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

if (pop_settings_load()['mode'] === 'custom') {
    echo json_encode(['success' => false, 'message' => '직접 구성에서는 이 목록 순서를 바꾸지 않습니다.']);
    exit;
}

$filename = __DIR__ . '/pop_json.json';
if (!file_exists($filename)) {
    echo json_encode(['success' => false, 'message' => '데이터 파일이 없습니다.']);
    exit;
}

$data = json_decode((string) file_get_contents($filename), true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => '데이터 형식이 올바르지 않습니다.']);
    exit;
}

$count = count($data);
$orderRaw = $_POST['order'] ?? '';
$order = is_array($orderRaw) ? $orderRaw : json_decode($orderRaw, true);

if (!is_array($order) || count($order) !== $count) {
    echo json_encode(['success' => false, 'message' => '순서 데이터가 올바르지 않습니다.']);
    exit;
}

$order = array_map('intval', $order);
if (count(array_unique($order)) !== $count) {
    echo json_encode(['success' => false, 'message' => '중복된 순서가 있습니다.']);
    exit;
}

foreach ($order as $idx) {
    if ($idx < 0 || $idx >= $count) {
        echo json_encode(['success' => false, 'message' => '잘못된 인덱스입니다.']);
        exit;
    }
}

$reordered = [];
foreach ($order as $idx) {
    $reordered[] = $data[$idx];
}

file_put_contents($filename, json_encode($reordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(['success' => true, 'message' => '순서가 변경되었습니다.']);
