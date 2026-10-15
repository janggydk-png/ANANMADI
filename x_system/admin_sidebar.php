<?php
if (!isset($clinicName)) {
    require_once __DIR__ . '/clinic_name.php';
    $clinicName = admin_clinic_name();
}
if (!isset($adminMenu)) {
    $adminMenu = '';
}
?>
<div class="left_warp">
    <div class="left_logo"></div>
    <?php if ($clinicName !== ''): ?>
        <div class="left_clinic_name"><?= htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <div class="left_tit_text">ADMIN</div>
    <div class="left_menu_warp">
        <a href="pop_list.php"><div class="btn<?= $adminMenu === 'popup' ? ' on' : '' ?>">팝업 목록</div></a>
        <a href="pop_display.php"><div class="btn<?= $adminMenu === 'popup_display' ? ' on' : '' ?>">팝업 표시 형태</div></a>
        <a href="nonbenefit_list.php"><div class="btn<?= $adminMenu === 'nonbenefit' ? ' on' : '' ?>">비급여 항목표</div></a>
    </div>
    <div class="left_bottom">
        <a href="logout.php" class="left_logout_link">로그아웃</a>
        <a href="password_change.php" class="left_pw_link<?= $adminMenu === 'password' ? ' on' : '' ?>">비밀번호 변경</a>
        <div class="left_credit">H-solution 제작</div>
    </div>
</div>
