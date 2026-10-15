<?php

session_start(); // 세션 시작
require_once __DIR__ . '/clinic_name.php';
require_once __DIR__ . '/pop_settings.php';
$clinicName = admin_clinic_name();
$adminMenu = 'popup';
$orderLocked = pop_settings_load()['mode'] === 'custom';

// 세션에 로그인 여부 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    // 로그인되어 있지 않으면 index.php로 리다이렉트
    header('Location: index.php');
    exit;
}




// pop_json.json 파일에서 데이터 로드
$data = json_decode((string) @file_get_contents(__DIR__ . '/pop_json.json'), true);
if (!is_array($data)) {
    $data = [];
}

// 현재 날짜와 시간
$now = new DateTime();
$folderCounts = ['active' => 0, 'finished' => 0, 'all' => 0];
foreach ($data as $popup) {
    $folderCounts['all']++;
    try {
        $endDate = new DateTime($popup['end_date']);
        if ($now > $endDate) {
            $folderCounts['finished']++;
        } else {
            $folderCounts['active']++;
        }
    } catch (Exception $e) {
        $folderCounts['active']++;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ADMIN - 팝업 목록</title>
    <link rel="stylesheet" type="text/css" href="main.css?v=260922p">

</head>
<body>
    <div class="main_bg">
        <div class="main_warp">
            <?php include __DIR__ . '/admin_sidebar.php'; ?>
            <div class="right_warp">
            <div class="right_warp1">
                <div class="right_tit">팝업 목록</div>
                <div class="right_cont">
                    <div class="right_cont1">
                    <div class="pop_folder_tabs" role="tablist">
                        <button type="button" class="pop_folder_tab is-active" data-folder="active">진행중 <span><?= (int) $folderCounts['active'] ?></span></button>
                        <button type="button" class="pop_folder_tab" data-folder="finished">종료 <span><?= (int) $folderCounts['finished'] ?></span></button>
                        <button type="button" class="pop_folder_tab" data-folder="all">전체 <span><?= (int) $folderCounts['all'] ?></span></button>
                    </div>
                    <p class="pop_order_hint"><?php if ($orderLocked): ?>지금은 직접 구성이라 이 목록의 순서는 화면에 쓰이지 않습니다. 순서는 <a href="pop_display.php">팝업 표시 형태</a>에서 묶음마다 정합니다.<?php else: ?>이 목록 안에서 ⋮⋮ 를 끌어다 놓으면 순서가 바뀝니다. 1번이 앞쪽 팝업입니다.<?php endif; ?></p>
                    <table class="pop_list_table<?= $orderLocked ? ' is-order-locked' : '' ?>" id="pop_list_table">
                        <thead>
                        <tr class="tit">
                            <th width="12%">순서</th>
                            <th width="16%">진행여부</th>
                            <th width="32%">팝업제목</th>
                            <th width="24%">진행날짜</th>
                            <th width="16%"> </th>
                        </tr>
                        </thead>
                        <tbody id="pop_list_body">
                        <?php foreach ($data as $index => $popup): ?>
                            <?php
                                // 날짜 비교
                                $startDate = new DateTime($popup['start_date']);
                                $endDate = new DateTime($popup['end_date']);
                                if ($now < $startDate) {
                                    $status = '대기중';
                                } elseif ($now > $endDate) {
                                    $status = '종료';
                                } else {
                                    $status = '진행중';
                                }
                                $class = '';
                                $folder = 'active';
                                switch ($status) {
                                    case '대기중':
                                        $class = 'waiting';
                                        $folder = 'active';
                                        break;
                                    case '종료':
                                        $class = 'finished';
                                        $folder = 'finished';
                                        break;
                                    case '진행중':
                                        $class = 'ongoing';
                                        $folder = 'active';
                                        break;
                                    default:
                                        $class = '';
                                        $folder = 'active';
                                        break;
                                }
                            ?>
                            <tr class="pop_row"<?= $orderLocked ? '' : ' draggable="true"' ?> data-index="<?= $index ?>" data-folder="<?= $folder ?>">
                                <td class="order_cell">
                                    <?php if (!$orderLocked): ?><span class="drag_handle" title="끌어다 놓아 순서 변경">⋮⋮</span><?php endif; ?>
                                    <span class="order_num"><?= $index + 1 ?></span>
                                </td>
                                <td class="status <?= $class ?>"><?= $status ?></td>
                                <td><?php
                                    echo htmlspecialchars($popup['title'], ENT_QUOTES, 'UTF-8');
                                    if (empty($popup['image_url'])) {
                                        echo ' <span class="pop_noimg">이미지 없음</span>';
                                    }
                                ?></td>
                                <td><?= $startDate->format('Y-m-d H:i') ?> ~ <?= $endDate->format('Y-m-d H:i') ?></td>
                                <td>
                                <button type="button" class="edit_btn" data-index="<?= $index ?>">수정</button>
                                    <button type="button" class="del" data-index="<?= $index ?>">삭제</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            <tr class="pop_empty_row" id="pop_empty" hidden>
                                <td colspan="5">팝업 목록이 비어있습니다.</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div onclick="location.href='pop_form.php'" class="right_main_btn">팝업 추가</div>

            </div>
            </div>
        </div>
    </div>




    <script>
        function deletePopup(index) {
            if (confirm('정말로 삭제하시겠습니까?')) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'pop_delete.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert(xhr.responseText);
                        window.location.reload();
                    } else {
                        alert('삭제에 실패했습니다.');
                    }
                };
                xhr.send('index=' + index);
            }
        }

        function getCurrentFolder() {
            var tab = document.querySelector('.pop_folder_tab.is-active');
            return (tab && tab.getAttribute('data-folder')) || 'all';
        }

        function getVisibleRows(tbody) {
            return Array.prototype.filter.call(tbody.querySelectorAll('.pop_row'), function (row) {
                return !row.classList.contains('is-hidden');
            });
        }

        function renumberPopRows() {
            var tbody = document.getElementById('pop_list_body');
            if (!tbody) return;
            getVisibleRows(tbody).forEach(function (row, i) {
                var num = row.querySelector('.order_num');
                if (num) num.textContent = String(i + 1);
                row.classList.toggle('is-stripe', i % 2 === 0);
            });
            tbody.querySelectorAll('.pop_row.is-hidden').forEach(function (row) {
                row.classList.remove('is-stripe');
            });
        }

        function syncRowIndexes(tbody) {
            tbody.querySelectorAll('.pop_row').forEach(function (row, i) {
                row.setAttribute('data-index', String(i));
                var editBtn = row.querySelector('.edit_btn');
                var delBtn = row.querySelector('.del');
                if (editBtn) editBtn.setAttribute('data-index', String(i));
                if (delBtn) delBtn.setAttribute('data-index', String(i));
            });
            renumberPopRows();
        }

        (function initDragReorder() {
            var tbody = document.getElementById('pop_list_body');
            var table = document.getElementById('pop_list_table');
            if (!tbody) return;
            var orderLocked = table && table.classList.contains('is-order-locked');
            var dragRow = null;
            var saving = false;
            var orderBefore = '';

            function getFolderSaveOrder() {
                var folder = getCurrentFolder();
                var rows = Array.prototype.slice.call(tbody.querySelectorAll('.pop_row'));
                var visibleIdx = [];
                var hiddenIdx = [];
                rows.forEach(function (row) {
                    var idx = parseInt(row.getAttribute('data-index'), 10);
                    if (row.classList.contains('is-hidden')) hiddenIdx.push(idx);
                    else visibleIdx.push(idx);
                });
                if (folder === 'all') {
                    return rows.map(function (row) {
                        return parseInt(row.getAttribute('data-index'), 10);
                    });
                }
                if (folder === 'active') {
                    return visibleIdx.concat(hiddenIdx);
                }
                return hiddenIdx.concat(visibleIdx);
            }

            function syncDomToOrder(order) {
                var byIndex = {};
                tbody.querySelectorAll('.pop_row').forEach(function (row) {
                    byIndex[row.getAttribute('data-index')] = row;
                });
                order.forEach(function (idx) {
                    var row = byIndex[String(idx)];
                    if (row) tbody.appendChild(row);
                });
            }

            function saveOrder() {
                if (saving) return;
                var order = getFolderSaveOrder();
                saving = true;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'pop_reorder.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    saving = false;
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            syncDomToOrder(order);
                            syncRowIndexes(tbody);
                            showOrderSaved();
                        } else {
                            alert(res.message || '순서 저장에 실패했습니다.');
                            window.location.reload();
                        }
                    } catch (e) {
                        alert('순서 저장에 실패했습니다.');
                        window.location.reload();
                    }
                };
                xhr.onerror = function () {
                    saving = false;
                    alert('순서 저장에 실패했습니다.');
                    window.location.reload();
                };
                xhr.send('order=' + encodeURIComponent(JSON.stringify(order)));
            }

            function persistIfChanged() {
                if (getFolderSaveOrder().join(',') === orderBefore) return;
                saveOrder();
            }

            function showOrderSaved() {
                var hint = document.querySelector('.pop_order_hint');
                if (!hint) return;
                hint.classList.add('is-saved');
                hint.setAttribute('data-msg', hint.getAttribute('data-msg') || hint.textContent);
                hint.textContent = '순서가 저장되었습니다.';
                setTimeout(function () {
                    hint.textContent = hint.getAttribute('data-msg') || '이 목록 안에서 ⋮⋮ 를 끌어다 놓으면 순서가 바뀝니다.';
                    hint.classList.remove('is-saved');
                }, 1500);
            }

            tbody.addEventListener('click', function (e) {
                var editBtn = e.target.closest('.edit_btn');
                if (editBtn) {
                    location.href = 'pop_form.php?index=' + editBtn.getAttribute('data-index');
                    return;
                }
                var delBtn = e.target.closest('.del');
                if (delBtn) deletePopup(parseInt(delBtn.getAttribute('data-index'), 10));
            });

            if (orderLocked) return;

            tbody.addEventListener('dragstart', function (e) {
                var row = e.target.closest('.pop_row');
                if (!row) return;
                if (e.target.closest('button, a, input, label')) {
                    e.preventDefault();
                    return;
                }
                dragRow = row;
                orderBefore = getFolderSaveOrder().join(',');
                row.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                try {
                    e.dataTransfer.setData('text/plain', row.getAttribute('data-index') || '');
                } catch (err) {}
            });

            tbody.addEventListener('dragend', function () {
                if (dragRow) dragRow.classList.remove('is-dragging');
                tbody.querySelectorAll('.pop_row').forEach(function (row) {
                    row.classList.remove('drag-over');
                });
                persistIfChanged();
                dragRow = null;
            });

            tbody.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                var row = e.target.closest('.pop_row');
                if (!dragRow || !row || row === dragRow) return;
                if (row.classList.contains('is-hidden')) return;
                tbody.querySelectorAll('.pop_row').forEach(function (r) {
                    r.classList.remove('drag-over');
                });
                row.classList.add('drag-over');
                var rect = row.getBoundingClientRect();
                var before = (e.clientY - rect.top) < rect.height / 2;
                if (before) tbody.insertBefore(dragRow, row);
                else tbody.insertBefore(dragRow, row.nextSibling);
                renumberPopRows();
            });

            tbody.addEventListener('drop', function (e) {
                e.preventDefault();
            });
        })();

        (function initFolderTabs() {
            var tbody = document.getElementById('pop_list_body');
            var tabs = document.querySelectorAll('.pop_folder_tab');
            var emptyEl = document.getElementById('pop_empty');
            if (!tbody || !tabs.length) return;
            var current = 'active';

            function applyFolder(folder) {
                current = folder;
                var visible = 0;
                tbody.querySelectorAll('.pop_row').forEach(function (row) {
                    var match = folder === 'all' || row.getAttribute('data-folder') === folder;
                    row.classList.toggle('is-hidden', !match);
                    if (match) visible += 1;
                });
                tabs.forEach(function (tab) {
                    tab.classList.toggle('is-active', tab.getAttribute('data-folder') === folder);
                });
                if (emptyEl) emptyEl.hidden = visible > 0;
                renumberPopRows();
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    applyFolder(tab.getAttribute('data-folder'));
                });
            });

            applyFolder(current);
        })();
    </script>
</body>
</html>
