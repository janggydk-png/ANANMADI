<?php
session_start(); // 세션 시작
require_once __DIR__ . '/clinic_name.php';
require_once __DIR__ . '/pop_image.php';
$clinicName = admin_clinic_name();
$adminMenu = 'popup';

// 세션에 로그인 여부 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    // 로그인되어 있지 않으면 index.php로 리다이렉트
    header('Location: index.php');
    exit;
}




// 인덱스로부터 데이터 로드
$index = isset($_GET['index']) ? $_GET['index'] : null;
$popupItem = null;
if ($index !== null) {
    $data = json_decode((string) @file_get_contents(__DIR__ . '/pop_json.json'), true);
    if (isset($data[$index])) {
        $popupItem = $data[$index];
    }
}
?>

<html>
<head>
    <title>ADMIN - <?= $popupItem ? '팝업 수정' : '새 팝업 추가' ?></title>
   
     <link rel="stylesheet" type="text/css" href="main.css?v=261014c">
</head>
<body>
<div class="main_bg">
    <div class="main_warp">
        <?php include __DIR__ . '/admin_sidebar.php'; ?>
        <div class="right_warp">
        <div class="right_warp1">
            <form action="save_pop.php" method="post" enctype="multipart/form-data">
            <div class="right_tit">팝업관리자 - 팝업작성</div>
            <div class="right_cont">
                <div class="right_cont1 formwarp">
                 
                
                <input type="hidden" name="index" value="<?= htmlspecialchars($index) ?>">
                <label for="start_date">시작 날짜:</label>
                <input class="half" type="datetime-local" id="start_date" name="start_date" value="<?= $popupItem['start_date'] ?? '' ?>" required><br>
                <label for="end_date">종료 날짜:</label>
                <input class="half" type="datetime-local" id="end_date" name="end_date" value="<?= $popupItem['end_date'] ?? '' ?>" required><br>
                <label for="title">팝업 제목:</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($popupItem['title'] ?? '') ?>" required><br>
                <label for="pop_link">팝업 링크연결 :</label>
                <input type="text" id="pop_link" name="pop_link" value="<?= htmlspecialchars($popupItem['pop_link'] ?? '') ?>"><br>
                <div class="check_row">
                    <input class="checkcb" type="checkbox" id="pop_link_none" name="pop_link_none"
                        <?php if ($popupItem && ($popupItem['pop_link'] ?? '') === 'javascript:void(0)') echo 'checked'; ?>>
                    <label for="pop_link_none">링크 삽입없이 사용</label>
                </div>

                <?php if ($popupItem && !empty($popupItem['image_url'])): ?>
                    <p class="current_image">현재 이미지: <img src="<?= htmlspecialchars(admin_pop_image_src($popupItem['image_url'])) ?>" width="200" alt=""></p>
                <?php endif; ?>
                <label class="file_label" for="popup_image">이미지 업로드:</label>
                <input type="file" id="popup_image" name="popup_image" accept="image/*"<?= ($popupItem && !empty($popupItem['image_url'])) ? '' : ' required' ?>>

               
           


                </div>
            </div>
            <div class="right_sub_btn_warp">
                 <input type="submit" class="btn" value="<?= $popupItem ? '수정' : '저장' ?>">

                 <button type="button" class="btn color2" onclick="returnToList()">리스트로 돌아가기</button>
            </div>
            </form>
        </div>
         </div>
    </div>
 </div>







<script>
    // 폼 변경 여부를 추적하는 변수
    var formChanged = false;

    // 폼에 입력된 데이터가 변경되었을 때 formChanged 변수를 true로 설정
    document.querySelector('form').addEventListener('input', function() {
        formChanged = true;
    });

    document.querySelector('form').addEventListener('submit', function (e) {
        var start = document.getElementById('start_date').value;
        var end = document.getElementById('end_date').value;
        if (start && end && start >= end) {
            e.preventDefault();
            alert('종료 날짜는 시작 날짜보다 늦어야 합니다. 기간을 다시 확인해 주세요.');
            document.getElementById('end_date').focus();
        }
    });

    // 리스트로 돌아가기 함수
    function returnToList() {
        if (formChanged) {
            // 변경 사항이 있을 경우 경고 메시지 표시
            var confirmLeave = confirm("변경된 내용이 있습니다. 정말로 리스트로 돌아가시겠습니까?");
            if (confirmLeave) {
                // 확인을 클릭한 경우 리스트 페이지로 리다이렉션
                window.location.href = 'pop_list.php';
            } else {
                // 취소를 클릭한 경우 아무 동작도 하지 않음
            }
        } else {
            // 변경 사항이 없을 경우 바로 리스트 페이지로 리다이렉션
            window.location.href = 'pop_list.php';
        }
    }

    // 체크박스 체크 여부에 따라 링크 입력 필드 상태 변경
    var popLinkNoneCheckbox = document.getElementById('pop_link_none');
    var popLinkInput = document.getElementById('pop_link');

    popLinkNoneCheckbox.addEventListener('change', function() {
        if (popLinkNoneCheckbox.checked) {
            popLinkInput.value = 'javascript:void(0)';
            popLinkInput.disabled = true;
        } else {
            if (popLinkInput.value === 'javascript:void(0)') {
                popLinkInput.value = '';
            }
            popLinkInput.disabled = false;
        }
    });

    // 페이지 로드 시 초기 설정 반영
    if (popLinkNoneCheckbox.checked) {
        popLinkInput.value = 'javascript:void(0)';
        popLinkInput.disabled = true;
    }
</script>
</body>
</html>
