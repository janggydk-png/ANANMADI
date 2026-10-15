<?php
session_start();
require_once __DIR__ . '/clinic_name.php';
$clinicName = admin_clinic_name();
$adminMenu = 'nonbenefit';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$data = json_decode((string) @file_get_contents(__DIR__ . '/nonbenefit.json'), true);
if (!is_array($data)) {
    $data = ['base_date' => '', 'sections' => []];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ADMIN - 비급여 항목표</title>
    <link rel="stylesheet" type="text/css" href="main.css?v=260929s">
</head>
<body>
    <div class="main_bg">
        <div class="main_warp">
            <?php include __DIR__ . '/admin_sidebar.php'; ?>
            <div class="right_warp">
                <div class="right_warp1">
                    <div class="right_tit">비급여 항목표</div>
                    <div class="right_cont">
                        <div class="right_cont1">
                            <div class="nb_notice">
                                <strong>비급여 항목표 작성 안내</strong>
                                <p>비급여 진료비용은 <b>「의료법」 제45조 및 「의료법 시행규칙」 제42조의2, 「비급여 진료비용 등의 고지 지침」</b>에 따라 홈페이지에 고지해야 합니다.</p>
                                <p>행위료, 치료재료대, 약제비, 제증명수수료별 <b>중분류·소분류·코드·명칭·비용·최종변경일 등의 정보가 필요</b>하오니, 비급여 항목표 전체 자료를 누락 없이 전달받아 정확하게 입력해 주세요.</p>
                            </div>
                            <div class="nb_toolbar">
                                <label for="nb_base_date">기준일</label>
                                <input type="text" id="nb_base_date" placeholder="비우면 기준일만 빈 칸으로 표시됩니다. 예: 2026.09.16, 2026년 9월 16일" value="<?= htmlspecialchars((string) ($data['base_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <p class="nb_hint">번호를 드래그하여 순서를 변경하고, + 버튼으로 새 행을 추가할 수 있습니다.<br>중분류·소분류를 바로 위 행과 동일하게 입력하면 하나의 그룹으로 묶여 표시됩니다.<br>※ 변경사항은 <b>저장</b> 후 홈페이지에 반영됩니다.</p>
                            </div>
                            <div class="nb_cats" id="nb_tabs" role="tablist"></div>
                            <div id="nb_tables"></div>
                        </div>
                    </div>
                    <div class="right_main_btn" id="nb_save_btn">저장</div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var NB_SECTIONS = {
            procedure: {
                title: '행위료',
                cols: [
                    { key: 'mid', label: '중분류' },
                    { key: 'sub', label: '소분류' },
                    { key: 'code', label: '코드' },
                    { key: 'name', label: '명칭' },
                    { key: 'type', label: '구분' },
                    { key: 'price', label: '비용' },
                    { key: 'min', label: '최저비용' },
                    { key: 'max', label: '최고비용' },
                    { key: 'material', label: '재료대포함' },
                    { key: 'drug', label: '약제비포함' },
                    { key: 'note', label: '특이사항' },
                    { key: 'updated', label: '최종변경일' }
                ]
            },
            drug: {
                title: '약제비',
                cols: [
                    { key: 'code', label: '코드' },
                    { key: 'name', label: '명칭' },
                    { key: 'type', label: '구분' },
                    { key: 'price', label: '비용' },
                    { key: 'note', label: '특이사항' },
                    { key: 'updated', label: '최종변경일' }
                ]
            },
            material: {
                title: '치료재료대',
                cols: [
                    { key: 'mid', label: '중분류' },
                    { key: 'code', label: '코드' },
                    { key: 'name', label: '명칭' },
                    { key: 'price', label: '비용' },
                    { key: 'note', label: '특이사항' },
                    { key: 'updated', label: '최종변경일' }
                ]
            },
            certificate: {
                title: '제증명 수수료',
                cols: [
                    { key: 'code', label: '코드' },
                    { key: 'name', label: '명칭' },
                    { key: 'type', label: '구분' },
                    { key: 'price', label: '비용' },
                    { key: 'note', label: '특이사항' },
                    { key: 'updated', label: '최종변경일' }
                ]
            }
        };

        var nbData = <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        var currentSection = 'procedure';
        var dirty = false;

        function emptyItem(key) {
            var item = {};
            NB_SECTIONS[key].cols.forEach(function (col) { item[col.key] = ''; });
            return item;
        }

        function mergeCost(item) {
            var price = String(item && item.price != null ? item.price : '').trim();
            if (price) return price;
            var min = String(item && item.min != null ? item.min : '').trim();
            var max = String(item && item.max != null ? item.max : '').trim();
            if (min && max && min !== max) return min + '~' + max;
            return min || max || '';
        }

        function ensureData() {
            if (!nbData || typeof nbData !== 'object') nbData = {};
            if (!nbData.sections) nbData.sections = {};
            Object.keys(NB_SECTIONS).forEach(function (key) {
                if (!nbData.sections[key] || !Array.isArray(nbData.sections[key].items)) {
                    nbData.sections[key] = { title: NB_SECTIONS[key].title, items: [] };
                }
            });
            (nbData.sections.drug.items || []).forEach(function (item) {
                item.price = mergeCost(item);
            });
        }

        function markDirty() {
            dirty = true;
            var btn = document.getElementById('nb_save_btn');
            if (btn) btn.textContent = '저장 *';
        }

        function collectFromDom() {
            var wrap = document.getElementById('nb_tables');
            var tbody = wrap.querySelector('tbody');
            if (!tbody) return;
            var cols = NB_SECTIONS[currentSection].cols;
            var items = [];
            tbody.querySelectorAll('tr').forEach(function (tr) {
                var item = emptyItem(currentSection);
                cols.forEach(function (col) {
                    var input = tr.querySelector('input[data-key="' + col.key + '"]');
                    item[col.key] = input ? input.value : '';
                });
                items.push(item);
            });
            nbData.sections[currentSection].items = items;
            nbData.base_date = document.getElementById('nb_base_date').value;
        }

        function renderTabs() {
            var tabs = document.getElementById('nb_tabs');
            tabs.innerHTML = '';
            Object.keys(NB_SECTIONS).forEach(function (key) {
                var count = (nbData.sections[key] && nbData.sections[key].items) ? nbData.sections[key].items.length : 0;
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'nb_cat' + (key === currentSection ? ' is-active' : '');
                btn.setAttribute('data-section', key);
                btn.innerHTML = NB_SECTIONS[key].title + ' <span>' + count + '</span>';
                btn.addEventListener('click', function () {
                    collectFromDom();
                    currentSection = key;
                    renderAll();
                });
                tabs.appendChild(btn);
            });
        }

        function renderTable() {
            var wrap = document.getElementById('nb_tables');
            var conf = NB_SECTIONS[currentSection];
            var items = nbData.sections[currentSection].items;
            var thead = '<th class="nb_order">#</th>' + conf.cols.map(function (col) {
                return '<th>' + col.label + '</th>';
            }).join('') + '<th class="nb_actions"></th>';
            wrap.innerHTML =
                '<div class="nb_table_wrap"><table class="nb_table"><thead><tr>' + thead + '</tr></thead><tbody>' +
                items.map(function (item) { return rowHtml(item); }).join('') +
                '</tbody></table></div>' +
                '<button type="button" class="nb_add_row">+ 항목 추가</button>';
            renumberRows(wrap.querySelector('tbody'));
            bindRowDrag(wrap.querySelector('tbody'));
        }

        function escapeAttr(val) {
            return String(val == null ? '' : val)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;');
        }

        function rowHtml(item) {
            var tds = NB_SECTIONS[currentSection].cols.map(function (col) {
                var val = item[col.key] != null ? String(item[col.key]) : '';
                return '<td><input type="text" data-key="' + col.key + '" value="' + escapeAttr(val) + '"></td>';
            }).join('');
            return '<tr><td class="nb_order"><span class="drag_handle" title="끌어 순서 변경"></span><button type="button" class="nb_insert" title="아래에 행 추가">+</button></td>' + tds + '<td><button type="button" class="nb_del">삭제</button></td></tr>';
        }

        function rowFromHtml(item) {
            var tmp = document.createElement('tbody');
            tmp.innerHTML = rowHtml(item);
            return tmp.firstChild;
        }

        function renumberRows(tbody) {
            if (!tbody) return;
            tbody.querySelectorAll('tr').forEach(function (tr, i) {
                var num = tr.querySelector('.drag_handle');
                if (num) num.textContent = String(i + 1);
            });
        }

        function focusNewRow(tr) {
            if (!tr) return;
            var first = tr.querySelector('input[data-key], input');
            if (!first) return;
            tr.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            window.setTimeout(function () {
                first.focus();
                first.select();
            }, 0);
        }

        function insertRowAfter(tr) {
            var tbody = tr ? tr.parentNode : document.querySelector('#nb_tables tbody');
            if (!tbody) return;
            var newTr = rowFromHtml(emptyItem(currentSection));
            if (tr) tbody.insertBefore(newTr, tr.nextSibling);
            else tbody.appendChild(newTr);
            renumberRows(tbody);
            collectFromDom();
            markDirty();
            renderTabs();
            focusNewRow(newTr);
        }

        function bindTable() {
            var wrap = document.getElementById('nb_tables');
            if (!wrap || wrap.dataset.bound === '1') return;
            wrap.dataset.bound = '1';
            wrap.addEventListener('input', function (e) {
                if (!e.target || !e.target.matches('input[data-key]')) return;
                markDirty();
            });
            wrap.addEventListener('click', function (e) {
                var del = e.target.closest('.nb_del');
                if (del) {
                    var tr = del.closest('tr');
                    if (!tr) return;
                    if (!window.confirm('이 항목을 삭제할까요?')) return;
                    var tbody = tr.parentNode;
                    tbody.removeChild(tr);
                    renumberRows(tbody);
                    collectFromDom();
                    markDirty();
                    renderTabs();
                    return;
                }
                if (e.target.closest('.nb_insert')) {
                    insertRowAfter(e.target.closest('tr'));
                    return;
                }
                if (e.target.closest('.nb_add_row')) {
                    var rows = wrap.querySelectorAll('tbody tr');
                    insertRowAfter(rows.length ? rows[rows.length - 1] : null);
                }
            });
        }

        function bindRowDrag(tbody) {
            if (!tbody) return;
            var dragRow = null;
            var moved = false;

            function onMove(e) {
                if (!dragRow) return;
                var el = document.elementFromPoint(e.clientX, e.clientY);
                var over = el && el.closest ? el.closest('.nb_table tbody tr') : null;
                if (!over || over === dragRow || over.parentNode !== tbody) return;
                var rect = over.getBoundingClientRect();
                var before = (e.clientY - rect.top) < rect.height / 2;
                if (before) tbody.insertBefore(dragRow, over);
                else tbody.insertBefore(dragRow, over.nextSibling);
                moved = true;
            }

            function onUp() {
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
                if (dragRow) dragRow.classList.remove('is-dragging');
                if (moved) {
                    renumberRows(tbody);
                    markDirty();
                }
                dragRow = null;
                moved = false;
            }

            tbody.addEventListener('pointerdown', function (e) {
                if (!e.target.closest('.drag_handle')) return;
                var row = e.target.closest('tr');
                if (!row) return;
                e.preventDefault();
                dragRow = row;
                moved = false;
                row.classList.add('is-dragging');
                document.addEventListener('pointermove', onMove);
                document.addEventListener('pointerup', onUp);
            });
        }

        function renderAll() {
            renderTabs();
            renderTable();
        }

        document.getElementById('nb_base_date').addEventListener('input', markDirty);

        document.getElementById('nb_save_btn').addEventListener('click', function () {
            collectFromDom();
            var btn = document.getElementById('nb_save_btn');
            btn.textContent = '저장 중...';
            fetch('nonbenefit_save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(nbData)
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res.success) {
                        dirty = false;
                        if (res.base_date !== undefined) {
                            nbData.base_date = res.base_date;
                            document.getElementById('nb_base_date').value = res.base_date;
                        }
                        btn.textContent = '저장됨';
                        setTimeout(function () { btn.textContent = '저장'; }, 1400);
                    } else {
                        btn.textContent = '저장 *';
                        alert(res.message || '저장에 실패했습니다.');
                    }
                })
                .catch(function () {
                    btn.textContent = '저장 *';
                    alert('저장에 실패했습니다.');
                });
        });

        window.addEventListener('beforeunload', function (e) {
            if (!dirty) return;
            e.preventDefault();
            e.returnValue = '';
        });

        ensureData();
        bindTable();
        renderAll();
    </script>
</body>
</html>
