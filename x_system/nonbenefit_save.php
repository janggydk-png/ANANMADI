<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode((string) $raw, true);
if (!is_array($data) || !isset($data['sections']) || !is_array($data['sections'])) {
    echo json_encode(['success' => false, 'message' => '데이터 형식이 올바르지 않습니다.']);
    exit;
}

/** 기준일 문구가 실제 날짜인지 확인한다. 입력한 표기는 바꾸지 않는다. 빈 값은 허용한다. */
function nonbenefit_base_date_valid($text)
{
    $patterns = [
        '/^(\d{2}|\d{4})\s*[-.\/]\s*(\d{1,2})(?:\s*[-.\/]\s*(\d{1,2}))?\s*\.?$/u',
        '/^(\d{2}|\d{4})\s*년\s*(\d{1,2})\s*월(?:\s*(\d{1,2})\s*일)?$/u',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $m)) {
            $year = (int) $m[1] + (strlen($m[1]) === 2 ? 2000 : 0);
            $day = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 1;
            return checkdate((int) $m[2], $day, $year);
        }
    }
    return false;
}

$baseDate = trim((string) ($data['base_date'] ?? ''));
if ($baseDate !== '' && !nonbenefit_base_date_valid($baseDate)) {
    echo json_encode(['success' => false, 'message' => '기준일을 날짜 형식으로 입력해 주세요. (예: 2026-09-16, 2026.9.16, 26.9.16, 2026년 9월 16일)']);
    exit;
}
$data['base_date'] = $baseDate;

$path = __DIR__ . '/nonbenefit.json';
$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($json === false || file_put_contents($path, $json, LOCK_EX) === false) {
    echo json_encode(['success' => false, 'message' => '저장에 실패했습니다.']);
    exit;
}

echo json_encode(['success' => true, 'base_date' => $baseDate]);
