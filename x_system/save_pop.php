<?php
session_start();
require_once __DIR__ . '/pop_settings.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    echo "<script>alert('이미지 용량이 너무 큽니다. 더 작은 파일로 올려 주세요.');history.back();</script>";
    exit;
}

$postedId = trim((string) ($_POST['id'] ?? ''));
$index = isset($_POST['index']) && $_POST['index'] !== '' ? (int) $_POST['index'] : null;

function popup_parse_datetime($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    try {
        return new DateTime($value);
    } catch (Exception $e) {
        return null;
    }
}

$startAt = popup_parse_datetime($_POST['start_date'] ?? '');
$endAt = popup_parse_datetime($_POST['end_date'] ?? '');
if ($startAt === null || $endAt === null) {
    echo "<script>alert('시작 날짜와 종료 날짜를 올바르게 입력해 주세요.');history.back();</script>";
    exit;
}
if ($startAt >= $endAt) {
    echo "<script>alert('종료 날짜는 시작 날짜보다 늦어야 합니다. 기간을 다시 확인해 주세요.');history.back();</script>";
    exit;
}

function popup_image_extension($tmpPath, $originalName)
{
    $nameExt = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
    $allowedName = ['jpg' => true, 'jpeg' => true, 'png' => true, 'gif' => true, 'webp' => true];
    if (!isset($allowedName[$nameExt])) {
        return '';
    }
    $info = @getimagesize($tmpPath);
    $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
    $byMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    return $byMime[$mime] ?? '';
}

$existingImage = '';
$itemsForCheck = pop_load_items();
if ($postedId !== '') {
    foreach ($itemsForCheck as $row) {
        if ((string) ($row['uniqueId'] ?? '') === $postedId) {
            $existingImage = (string) ($row['image_url'] ?? '');
            break;
        }
    }
} elseif ($index !== null && isset($itemsForCheck[$index]['image_url'])) {
    $existingImage = (string) $itemsForCheck[$index]['image_url'];
}

$uploadError = $_FILES['popup_image']['error'] ?? UPLOAD_ERR_NO_FILE;
if ($uploadError === UPLOAD_ERR_NO_FILE && $existingImage === '') {
    echo "<script>alert('팝업 이미지를 올려 주세요. 이미지 없는 팝업은 등록할 수 없습니다.');history.back();</script>";
    exit;
}
if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
    echo "<script>alert('이미지 용량이 너무 큽니다. 더 작은 파일로 올려 주세요.');history.back();</script>";
    exit;
}
if ($uploadError !== UPLOAD_ERR_NO_FILE && $uploadError !== UPLOAD_ERR_OK) {
    echo "<script>alert('이미지 업로드에 실패했습니다. 다시 시도해 주세요.');history.back();</script>";
    exit;
}

$imageUrl = '';

if (isset($_FILES['popup_image']['name']) && $_FILES['popup_image']['name'] != '') {
    $file_extension = popup_image_extension($_FILES['popup_image']['tmp_name'], $_FILES['popup_image']['name']);
    if ($file_extension === '') {
        echo "<script>alert('이미지 파일만 업로드할 수 있습니다. (jpg, png, gif, webp)');history.back();</script>";
        exit;
    }
    $target_dir = __DIR__ . '/uploads/img/pop/';
    $relative_file = 'uploads/img/pop/' . time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = __DIR__ . '/' . $relative_file;
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    if (move_uploaded_file($_FILES['popup_image']['tmp_name'], $target_file)) {
        if (function_exists('imagecreatefromstring')) {
            $raw = @file_get_contents($target_file);
            $im = $raw !== false ? @imagecreatefromstring($raw) : false;
            if ($im !== false) {
                $w = imagesx($im);
                $h = imagesy($im);
                $maxW = 900;
                if ($w > $maxW) {
                    $nh = (int) round($h * $maxW / $w);
                    $resized = imagecreatetruecolor($maxW, $nh);
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled($resized, $im, 0, 0, 0, 0, $maxW, $nh, $w, $h);
                    imagedestroy($im);
                    $im = $resized;
                }
                $tmpOpt = $target_file . '.opt';
                $ok = false;
                $ext = strtolower($file_extension);
                if ($ext === 'png') {
                    imagetruecolortopalette($im, true, 255);
                    $ok = @imagepng($im, $tmpOpt, 9);
                } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                    $ok = @imagejpeg($im, $tmpOpt, 80);
                } elseif ($ext === 'webp' && function_exists('imagewebp')) {
                    $ok = @imagewebp($im, $tmpOpt, 80);
                }
                imagedestroy($im);
                if ($ok && filesize($tmpOpt) > 0 && filesize($tmpOpt) < filesize($target_file)) {
                    @rename($tmpOpt, $target_file);
                } else {
                    @unlink($tmpOpt);
                }
            }
        }
        $imageUrl = $relative_file;
    } else {
        echo "<script>alert('이미지 저장에 실패했습니다. (uploads/img/pop 쓰기 권한 확인)');history.back();</script>";
        exit;
    }
}

$filename = __DIR__ . '/pop_json.json';
$existingData = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
if (!is_array($existingData)) {
    $existingData = [];
}

$editIndex = null;
if ($postedId !== '') {
    foreach ($existingData as $i => $row) {
        if ((string) ($row['uniqueId'] ?? '') === $postedId) {
            $editIndex = $i;
            break;
        }
    }
} elseif ($index !== null && isset($existingData[$index])) {
    $editIndex = $index;
}

$isNew = $editIndex === null;
if (!$isNew) {
    $existingData[$editIndex] = [
        'uniqueId' => $existingData[$editIndex]['uniqueId'] ?? uniqid(),
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'title' => $_POST['title'],
        'image_url' => $imageUrl ?: ($existingData[$editIndex]['image_url'] ?? ''),
        'pop_link' => isset($_POST['pop_link_none']) ? 'javascript:void(0)' : $_POST['pop_link'],
    ];
} else {
    $existingData[] = [
        'uniqueId' => uniqid(),
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'title' => $_POST['title'],
        'image_url' => $imageUrl,
        'pop_link' => isset($_POST['pop_link_none']) ? 'javascript:void(0)' : $_POST['pop_link'],
    ];
}

$json = json_encode($existingData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($json === false || file_put_contents($filename, $json, LOCK_EX) === false) {
    echo "<script>alert('저장에 실패했습니다.');history.back();</script>";
    exit;
}

$notice = '팝업이 성공적으로 저장되었습니다.';
if ($isNew && pop_settings_load()['mode'] === 'custom') {
    $notice .= '\\n직접 구성에서는 묶음에 넣어야 홈에 노출됩니다. 지금은 묶음 미배치 상태입니다.';
}

echo "<script>
    alert('" . $notice . "');
    window.location.href = 'pop_list.php';
</script>";
