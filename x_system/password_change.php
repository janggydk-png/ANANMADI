<?php
session_start();
require_once __DIR__ . '/clinic_name.php';
$clinicName = admin_clinic_name();
$adminMenu = 'password';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$credentialsFile = __DIR__ . '/password.json';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string) ($_POST['current_password'] ?? '');
    $next = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $data = json_decode((string) @file_get_contents($credentialsFile), true);
    if (!is_array($data)) {
        $data = ['id' => 'admin', 'password' => ''];
    }
    $storedPassword = (string) ($data['password'] ?? '');

    if ($current === '' || $next === '' || $confirm === '') {
        $message = '모든 항목을 입력해 주세요.';
        $messageType = 'error';
    } elseif (!hash_equals($storedPassword, $current)) {
        $message = '현재 비밀번호가 올바르지 않습니다.';
        $messageType = 'error';
    } elseif (strlen($next) < 6) {
        $message = '새 비밀번호는 6자 이상이어야 합니다.';
        $messageType = 'error';
    } elseif ($next !== $confirm) {
        $message = '새 비밀번호 확인이 일치하지 않습니다.';
        $messageType = 'error';
    } elseif ($next === $current) {
        $message = '현재 비밀번호와 다른 비밀번호를 입력해 주세요.';
        $messageType = 'error';
    } else {
        $data['password'] = $next;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false || @file_put_contents($credentialsFile, $json . "\n") === false) {
            $message = '비밀번호 저장에 실패했습니다. 파일 권한을 확인해 주세요.';
            $messageType = 'error';
        } else {
            $message = '비밀번호가 변경되었습니다.';
            $messageType = 'ok';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ADMIN - 비밀번호 변경</title>
    <link rel="stylesheet" type="text/css" href="main.css?v=260922p">
</head>
<body>
    <div class="main_bg">
        <div class="main_warp">
            <?php include __DIR__ . '/admin_sidebar.php'; ?>
            <div class="right_warp">
                <div class="right_warp1">
                    <div class="right_tit">비밀번호 변경</div>
                    <div class="right_cont">
                        <div class="right_cont1">
                            <form class="formwarp pw_change_form" method="post" action="password_change.php" autocomplete="off">
                                <?php if ($message !== ''): ?>
                                    <p class="pw_change_msg is-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <label for="current_password">현재 비밀번호</label>
                                <input type="password" id="current_password" name="current_password" required>

                                <label for="new_password">새 비밀번호</label>
                                <input type="password" id="new_password" name="new_password" minlength="6" required>

                                <label for="confirm_password">새 비밀번호 확인</label>
                                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>

                                <p class="pw_change_hint">비밀번호는 6자 이상으로 설정해 주세요.</p>
                                <button type="submit" class="pw_change_submit">변경하기</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
