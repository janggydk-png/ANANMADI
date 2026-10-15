<?php
session_start();
require_once __DIR__ . '/clinic_name.php';
require_once __DIR__ . '/pop_settings.php';
$clinicName = admin_clinic_name();
$adminMenu = 'popup_display';
$s = pop_settings_load();
$popItems = pop_items_for_admin();
$liveCount = pop_live_count();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ADMIN - 팝업 표시 형태</title>
    <link rel="stylesheet" type="text/css" href="main.css?v=261014d">
</head>
<body>
    <div class="main_bg">
        <div class="main_warp">
            <?php include __DIR__ . '/admin_sidebar.php'; ?>
            <div class="right_warp">
                <div class="right_warp1">
                    <div class="right_tit">팝업 표시 형태</div>
                    <div class="right_cont">
                        <div class="right_cont1 pd_page" data-mode="<?= htmlspecialchars($s['mode'], ENT_QUOTES, 'UTF-8') ?>" data-preset="<?= htmlspecialchars($s['preset'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="pd_main">
                                <section class="pd_group">
                                    <h3>구성 방식</h3>
                                    <div class="pop_templates" id="pd_modes">
                                        <button type="button" class="pop_tpl<?= $s['mode'] === 'preset' ? ' is-active' : '' ?>" data-mode="preset">기본 형태</button>
                                        <button type="button" class="pop_tpl<?= $s['mode'] === 'custom' ? ' is-active' : '' ?>" data-mode="custom">직접 구성</button>
                                    </div>
                                    <div class="pd_help pd_help_defs">
                                        <p><strong>나열형</strong>팝업이 각각 개별 창으로 표시되며, 원하는 팝업을 하나씩 닫을 수 있습니다.</p>
                                        <p><strong>슬라이드형</strong>여러 개의 팝업을 하나의 창에서 슬라이드로 확인하며, 창을 한 번에 닫을 수 있습니다.</p>
                                    </div>
                                </section>

                                <section class="pd_group pd_preset_box">
                                    <h3>기본 형태</h3>
                                    <div class="pop_templates" id="pd_presets">
                                        <button type="button" class="pop_tpl<?= $s['preset'] === 'list' ? ' is-active' : '' ?>" data-preset="list">나열형</button>
                                        <button type="button" class="pop_tpl<?= $s['preset'] === 'slide' ? ' is-active' : '' ?>" data-preset="slide">슬라이드형</button>
                                        <button type="button" class="pop_tpl<?= $s['preset'] === 'slide3' ? ' is-active' : '' ?>" data-preset="slide3">슬라이드 더미</button>
                                    </div>
                                    <div class="pd_grid" style="margin-top:14px">
                                        <label class="pd_sec_field pd_opt_dummy">노출 장수
                                            <select id="slide_per_view">
                                                <option value="2"<?= (int) $s['slide_per_view'] === 2 ? ' selected' : '' ?>>2장</option>
                                                <option value="3"<?= (int) $s['slide_per_view'] === 3 ? ' selected' : '' ?>>3장</option>
                                            </select>
                                        </label>
                                        <div class="pd_slide_opts pd_opt_slide">
                                            <label class="pd_check"><input type="checkbox" id="autoplay"<?= !empty($s['autoplay']) ? ' checked' : '' ?>> 자동 슬라이드</label>
                                            <label class="pd_check"><input type="checkbox" id="slide_names"<?= !empty($s['slide_names']) ? ' checked' : '' ?>> 이름표 표시</label>
                                            <label class="pd_sec_field">슬라이드 간격
                                                <span class="pd_sec">
                                                    <input type="number" id="autoplay_sec" min="1" max="15" step="0.5" value="<?= htmlspecialchars(rtrim(rtrim(number_format(((int) $s['autoplay_ms']) / 1000, 1, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <em>초</em>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="pd_help pd_opt_dummy">
                                        <p>한 화면에 2장 또는 3장의 팝업을 함께 노출할 수 있으며, 슬라이드 이동 시에는 한 장씩 넘어갑니다.</p>
                                        <p>※ 화면 크기에 따라 노출되는 팝업 수가 자동으로 조정되며, 화면이 좁을 경우 3장 → 2장 → 1장으로 표시될 수 있습니다.</p>
                                    </div>
                                </section>

                                <section class="pd_group pd_opt_close_all<?= !empty($s['close_all']) ? ' is-on' : '' ?>">
                                    <h3>한 번에 닫기</h3>
                                    <label class="pd_check"><input type="checkbox" id="close_all"<?= !empty($s['close_all']) ? ' checked' : '' ?>> 닫기 버튼을 하나로 합치기</label>
                                    <div class="pop_templates pd_close_pos" id="pd_close_pos">
                                        <button type="button" class="pop_tpl<?= ($s['close_all_pos'] ?? 'top') !== 'bottom' ? ' is-active' : '' ?>" data-close-pos="top">상단</button>
                                        <button type="button" class="pop_tpl<?= ($s['close_all_pos'] ?? 'top') === 'bottom' ? ' is-active' : '' ?>" data-close-pos="bottom">하단</button>
                                    </div>
                                    <p class="pd_help">켜면 각 팝업의 닫기 대신, 닫기 버튼 하나로 전체를 닫습니다. 버튼은 화면 위 또는 아래에 둘 수 있습니다.</p>
                                </section>

                                <section class="pd_group pd_custom_box">
                                    <h3>직접 구성</h3>
                                    <p class="pd_help">팝업 1, 팝업 2처럼 묶음을 추가한 뒤 나열 또는 슬라이드를 고르세요. 슬라이드는 한 장씩 넘어갑니다. 더미(여러 장 동시)는 기본 형태에서만 쓸 수 있습니다.</p>
                                    <div id="pd_groups"></div>
                                    <button type="button" class="nb_add_row" id="pd_add_group">+ 팝업 추가</button>
                                </section>
                            </div>
                            <aside class="pd_preview_wrap">
                                <div class="pd_preview_label">미리보기 <span>실제 화면에서 어디에 어떻게 붙는지 확인합니다</span></div>
                                <div class="pd_preview_frame">
                                    <div class="pd_preview_header">헤더</div>
                                    <div class="pd_preview_stage" id="pd_preview_stage"></div>
                                </div>
                            </aside>
                        </div>
                    </div>
                    <div class="right_main_btn" id="pd_save_btn">저장</div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var POP_ITEMS = <?= json_encode($popItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        var LIVE_COUNT = <?= (int) $liveCount ?>;
        var state = {
            mode: <?= json_encode($s['mode']) ?>,
            preset: <?= json_encode($s['preset']) ?>,
            groups: <?= json_encode($s['groups'], JSON_UNESCAPED_UNICODE) ?>
        };
        if (!Array.isArray(state.groups)) {
            state.groups = [];
        }

        function emptyGroup(type, position) {
            var isSlide = type === 'slide';
            return {
                id: 'g' + Date.now() + Math.floor(Math.random() * 1000),
                type: isSlide ? 'slide' : 'list',
                position: position || 'left',
                ids: [],
                autoplay: isSlide,
                autoplay_ms: 3000,
                show_names: false
            };
        }

        function esc(text) {
            return String(text || '').replace(/[&<>"]/g, function (ch) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[ch];
            });
        }

        function closeAllPos() {
            var btn = document.querySelector('#pd_close_pos .pop_tpl.is-active');
            return (btn && btn.getAttribute('data-close-pos') === 'bottom') ? 'bottom' : 'top';
        }

        function collect() {
            return {
                mode: state.mode,
                preset: state.preset,
                slide_per_view: parseInt(document.getElementById('slide_per_view').value, 10) || 2,
                autoplay: document.getElementById('autoplay').checked,
                autoplay_ms: Math.round((parseFloat(document.getElementById('autoplay_sec').value) || 3) * 1000),
                slide_names: document.getElementById('slide_names').checked,
                close_all: document.getElementById('close_all').checked,
                close_all_pos: closeAllPos(),
                groups: state.groups
            };
        }

        var savedSnap = '';
        function snapshot() {
            return JSON.stringify(collect());
        }
        function rememberSaved() {
            savedSnap = snapshot();
        }
        function isDirty() {
            return savedSnap !== '' && snapshot() !== savedSnap;
        }

        function secValue(ms) {
            var n = Math.round(((parseInt(ms, 10) || 3000) / 1000) * 10) / 10;
            return String(n);
        }

        function itemById(id) {
            for (var i = 0; i < POP_ITEMS.length; i++) {
                if (POP_ITEMS[i].id === id) return POP_ITEMS[i];
            }
            return null;
        }

        function renderGroups() {
            var wrap = document.getElementById('pd_groups');
            if (!state.groups.length) {
                wrap.innerHTML = '<p class="pd_help">아직 없습니다. 아래 버튼으로 팝업 묶음을 추가하세요.</p>';
                return;
            }
            wrap.innerHTML = state.groups.map(function (group, index) {
                var selected = (group.ids || []).map(function (id, n) {
                    var item = itemById(id);
                    var title = item ? (item.title || '제목 없음') : '삭제된 팝업';
                    var miss = '';
                    if (item && !item.has_image) miss += '<em>이미지 없음</em>';
                    if (item && item.active === false) miss += '<em>종료됨</em>';
                    if (!item) miss += '<em>삭제됨</em>';
                    return '<li class="pd_row" draggable="true" data-id="' + esc(id) + '">' +
                        '<span class="pd_row_handle" title="끌어 순서 변경">⋮⋮</span>' +
                        '<span class="pd_row_num">' + (n + 1) + '</span>' +
                        '<span class="pd_row_tit">' + esc(title) + ' ' + miss + '</span>' +
                        '<button type="button" class="pd_row_btn" data-act="remove" data-id="' + esc(id) + '">제거</button>' +
                        '</li>';
                }).join('');
                var rest = POP_ITEMS.filter(function (item) {
                    if (item.active === false) return false;
                    return !state.groups.some(function (g) {
                        return (g.ids || []).indexOf(item.id) !== -1;
                    });
                }).map(function (item) {
                    return '<li class="pd_row">' +
                        '<span class="pd_row_tit">' + esc(item.title || '제목 없음') +
                        (item.has_image ? '' : ' <em>이미지 없음</em>') + '</span>' +
                        '<button type="button" class="pd_row_btn" data-act="add" data-id="' + esc(item.id) + '">추가</button>' +
                        '</li>';
                }).join('');
                var posSelect = '<label class="pd_field">위치<select data-key="position">' +
                    '<option value="left"' + (group.position === 'left' ? ' selected' : '') + '>왼쪽</option>' +
                    '<option value="right"' + (group.position === 'right' ? ' selected' : '') + '>오른쪽</option>' +
                    '</select></label>';
                return '<article class="pd_group_card is-' + group.type + '" data-index="' + index + '">' +
                    '<div class="pd_group_top"><strong>팝업 ' + (index + 1) + '</strong>' +
                    '<button type="button" class="nb_del pd_del_group">삭제</button></div>' +
                    '<div class="pd_grid">' +
                    '<label class="pd_field">표시 형태<select data-key="type">' +
                    '<option value="list"' + (group.type === 'list' ? ' selected' : '') + '>나열</option>' +
                    '<option value="slide"' + (group.type === 'slide' ? ' selected' : '') + '>슬라이드</option>' +
                    '</select></label>' +
                    posSelect +
                    '<div class="pd_slide_opts pd_if_slide">' +
                    '<label class="pd_check"><input type="checkbox" data-key="autoplay"' + (group.autoplay ? ' checked' : '') + '> 자동 슬라이드</label>' +
                    '<label class="pd_sec_field">슬라이드 간격<span class="pd_sec"><input type="number" data-key="autoplay_sec" min="1" max="15" step="0.5" value="' + esc(secValue(group.autoplay_ms)) + '"><em>초</em></span></label>' +
                    '</div>' +
                    '</div>' +
                    '<div class="pd_names_box pd_if_slide">' +
                    '<label class="pd_check"><input type="checkbox" data-key="show_names"' + (group.show_names ? ' checked' : '') + '> 이름표 사용</label>' +
                    '<p class="pd_help">켜면 이 슬라이드 묶음의 모든 팝업에 이름표가 표시되고, 끄면 전부 사라집니다.</p>' +
                    '</div>' +
                    '<p class="pd_help pd_if_list">⋮⋮ 를 끌어 순서를 바꿉니다. 목록 순서대로 왼쪽부터 붙습니다.</p>' +
                    '<p class="pd_help pd_if_slide">⋮⋮ 를 끌어 순서를 바꿉니다. 한 장씩 보이고 넘어갑니다.</p>' +
                    '<div class="pd_list_cols">' +
                    '<div class="pd_list">' +
                    '<div class="pd_list_cap">목록</div>' +
                    '<ul class="pd_sel_list">' + (selected || '<li class="pd_row is-empty">목록이 비어있습니다.</li>') + '</ul>' +
                    '</div>' +
                    '<div class="pd_list is-rest">' +
                    '<div class="pd_list_cap">추가할 팝업</div>' +
                    '<ul>' + (rest || '<li class="pd_row is-empty">추가할 팝업이 없습니다.</li>') + '</ul>' +
                    '</div>' +
                    '</div>' +
                    '</article>';
            }).join('');
        }

        function liveIds(group) {
            return (group.ids || []).filter(function (id) {
                var item = itemById(id);
                return !!(item && item.live);
            });
        }

        function mockPopupName(id) {
            var item = itemById(id);
            if (!item) return '삭제된 팝업';
            return item.title || '제목 없음';
        }

        function mockBox(main, sub) {
            if (sub) {
                return '<div class="pd_mock_card"><span class="pd_mock_tag">' + esc(main) + '</span><b>' + esc(sub) + '</b></div>';
            }
            return '<div class="pd_mock_card"><b>' + esc(String(main)) + '</b></div>';
        }

        function mockNameRow(group) {
            var show = state.mode === 'custom'
                ? !!group.show_names
                : document.getElementById('slide_names').checked;
            if (!show || group.type !== 'slide') return '';
            var labels = [];
            if (state.mode === 'custom') {
                liveIds(group).forEach(function (id) {
                    labels.push(mockPopupName(id));
                });
            } else {
                var n = Math.max(LIVE_COUNT, 1);
                for (var i = 1; i <= n; i++) labels.push('팝업 ' + i);
            }
            if (!labels.length) return '';
            var n = labels.length;
            var maxCols = arguments.length > 1 ? arguments[1] : 3;
            var cols;
            var rem;
            if (maxCols >= 7) {
                cols = n <= 1 ? 1 : (n <= maxCols ? n : maxCols);
                rem = n <= maxCols ? 0 : n % maxCols;
            } else {
                cols = n <= 1 ? 1 : (n === 2 || n === 4 ? 2 : 3);
                rem = n % cols;
            }
            return '<div class="pd_mock_names" data-max="' + maxCols + '" data-cols="' + cols + '" data-rem="' + rem + '">' + labels.map(function (name) {
                return '<span class="pd_mock_name">' + esc(name) + '</span>';
            }).join('') + '</div>';
        }

        function mockCards(group, index) {
            var custom = state.mode === 'custom';
            var tag = '팝업 ' + (index + 1);
            if (custom && (!group.ids || !group.ids.length)) return '';
            var ids = custom ? liveIds(group) : [];
            if (custom && !ids.length) return '<p class="pd_help">' + tag + ': 지금 홈에 뜨는 팝업 없음</p>';
            if (group.type === 'slide') {
                var view = group.per_view || 1;
                var names = mockNameRow(group, view > 1 ? 7 : 3);
                if (view > 1) {
                    var slots = '';
                    for (var s = 1; s <= view; s++) slots += mockBox(s);
                    return '<div class="pd_mock_dummy_wrap">' + names + '<div class="pd_mock_dummy">' + slots + '</div></div>';
                }
                var total = custom
                    ? ids.length
                    : Math.max(LIVE_COUNT, 1);
                var foot = custom ? '' : '<div class="pd_mock_slider_foot">닫기</div>';
                return '<div class="pd_mock_slider' + (custom ? ' is-custom' : '') + '">' +
                    '<div class="pd_mock_slider_stage">' +
                    (custom ? mockBox(tag + ' 슬라이드', mockPopupName(ids[0])) : mockBox(1)) +
                    (names ? '' : '<span class="pd_mock_frac">1/' + total + '</span>') +
                    '</div>' +
                    names +
                    foot +
                    '</div>';
            }
            var count = custom ? ids.length : LIVE_COUNT;
            if (count < 1) return '<p class="pd_help">팝업 없음</p>';
            var html = '';
            for (var i = 1; i <= count; i++) {
                html += custom ? mockBox(tag, mockPopupName(ids[i - 1])) : mockBox(i);
            }
            return html;
        }

        function renderPreview() {
            var stage = document.getElementById('pd_preview_stage');
            var groups = state.mode === 'custom'
                ? state.groups
                : [{
                    type: state.preset === 'list' ? 'list' : 'slide',
                    position: state.preset === 'list' ? 'left' : 'center',
                    per_view: state.preset === 'slide3'
                        ? (parseInt(document.getElementById('slide_per_view').value, 10) || 2)
                        : 1,
                    ids: []
                }];
            var listOnly = groups.length > 0 && groups.every(function (group) { return group.type === 'list'; });
            var listLayout = listOnly || state.mode === 'custom';
            stage.className = 'pd_preview_stage' + (listLayout ? ' is-list-mode' : ' is-slide-mode');
            var html = { left: '', center: '', right: '' };
            groups.forEach(function (group, index) {
                var pos = group.position === 'right' ? 'right' : (group.position === 'center' ? 'center' : 'left');
                html[pos] += mockCards(group, index);
            });
            var closeAllOn = document.getElementById('close_all').checked && listLayout;
            if (listLayout) {
                var parts = [];
                if (html.left) parts.push('<div class="pd_zone is-left">' + html.left + '</div>');
                if (html.right) parts.push('<div class="pd_zone is-right">' + html.right + '</div>');
                stage.innerHTML = parts.join('') || '<p class="pd_help">목록에 팝업을 넣으면 미리보기가 나옵니다.</p>';
            } else {
                stage.innerHTML = '<div class="pd_zone is-center">' + (html.center || html.left || html.right) + '</div>';
            }
            stage.classList.toggle('has-close-all', !!(closeAllOn && stage.querySelector('.pd_mock_card, .pd_mock_slider, .pd_mock_dummy')));
            stage.classList.toggle('is-close-bottom', !!(closeAllOn && closeAllPos() === 'bottom'));
            if (stage.classList.contains('has-close-all')) {
                stage.insertAdjacentHTML('afterbegin', '<div class="pd_mock_close_all"><span class="pd_mock_close_all_x">&times;</span><span class="pd_mock_close_all_today">오늘 하루 보지 않기</span></div>');
            }
        }

        function syncPage() {
            document.querySelector('.pd_page').setAttribute('data-mode', state.mode);
            document.querySelector('.pd_page').setAttribute('data-preset', state.preset);
            document.querySelectorAll('#pd_modes .pop_tpl').forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-mode') === state.mode);
            });
            document.querySelectorAll('#pd_presets .pop_tpl').forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-preset') === state.preset);
            });
            renderGroups();
            renderPreview();
        }

        document.getElementById('pd_modes').addEventListener('click', function (e) {
            var btn = e.target.closest('.pop_tpl');
            if (!btn) return;
            state.mode = btn.getAttribute('data-mode') || 'preset';
            syncPage();
        });
        document.getElementById('pd_presets').addEventListener('click', function (e) {
            var btn = e.target.closest('.pop_tpl');
            if (!btn) return;
            state.preset = btn.getAttribute('data-preset') || 'slide';
            syncPage();
        });
        document.getElementById('pd_add_group').addEventListener('click', function () {
            state.groups.push(emptyGroup('list', 'left'));
            syncPage();
        });
        document.getElementById('pd_groups').addEventListener('click', function (e) {
            var del = e.target.closest('.pd_del_group');
            if (del) {
                var index = parseInt(del.closest('.pd_group_card').getAttribute('data-index'), 10);
                state.groups.splice(index, 1);
                syncPage();
                return;
            }
            var btn = e.target.closest('[data-act]');
            if (!btn) return;
            var card = btn.closest('.pd_group_card');
            if (!card) return;
            var gi = parseInt(card.getAttribute('data-index'), 10);
            var id = btn.getAttribute('data-id');
            var act = btn.getAttribute('data-act');
            if (act === 'remove') {
                state.groups[gi].ids = state.groups[gi].ids.filter(function (v) { return v !== id; });
            }
            if (act === 'add') {
                var item = itemById(id);
                if (!item || item.active === false) return;
                var taken = state.groups.some(function (g, i) {
                    return i !== gi && (g.ids || []).indexOf(id) !== -1;
                });
                if (taken || (state.groups[gi].ids || []).indexOf(id) !== -1) return;
                state.groups[gi].ids.push(id);
            }
            syncPage();
        });
        (function initGroupListDrag() {
            var wrap = document.getElementById('pd_groups');
            var dragEl = null;
            var fromList = null;

            function selectedRow(target) {
                return target && target.closest ? target.closest('.pd_sel_list .pd_row[data-id]') : null;
            }

            function commitOrder(row) {
                var card = row.closest('.pd_group_card');
                var list = row.parentNode;
                if (!card || !list) return;
                var gi = parseInt(card.getAttribute('data-index'), 10);
                var ids = Array.prototype.map.call(list.querySelectorAll('.pd_row[data-id]'), function (el) {
                    return el.getAttribute('data-id');
                });
                var prev = (state.groups[gi].ids || []).join(',');
                if (ids.join(',') === prev) return;
                state.groups[gi].ids = ids;
                syncPage();
            }

            wrap.addEventListener('dragstart', function (e) {
                var row = selectedRow(e.target);
                if (!row || e.target.closest('button')) {
                    e.preventDefault();
                    return;
                }
                dragEl = row;
                fromList = row.parentNode;
                row.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                try {
                    e.dataTransfer.setData('text/plain', row.getAttribute('data-id') || '');
                } catch (err) {}
            });

            wrap.addEventListener('dragend', function () {
                wrap.querySelectorAll('.pd_row.is-dragging, .pd_row.drag-over').forEach(function (el) {
                    el.classList.remove('is-dragging', 'drag-over');
                });
                if (dragEl) commitOrder(dragEl);
                dragEl = null;
                fromList = null;
            });

            wrap.addEventListener('dragover', function (e) {
                var row = selectedRow(e.target);
                if (!dragEl || !row || row === dragEl) return;
                if (row.parentNode !== fromList) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                wrap.querySelectorAll('.pd_row.drag-over').forEach(function (el) {
                    el.classList.remove('drag-over');
                });
                row.classList.add('drag-over');
                var rect = row.getBoundingClientRect();
                var before = (e.clientY - rect.top) < rect.height / 2;
                if (before) fromList.insertBefore(dragEl, row);
                else fromList.insertBefore(dragEl, row.nextSibling);
                fromList.querySelectorAll('.pd_row[data-id]').forEach(function (el, i) {
                    var num = el.querySelector('.pd_row_num');
                    if (num) num.textContent = String(i + 1);
                });
            });

            wrap.addEventListener('drop', function (e) {
                e.preventDefault();
            });
        })();
        document.getElementById('pd_groups').addEventListener('change', function (e) {
            var card = e.target.closest('.pd_group_card');
            if (!card) return;
            var group = state.groups[card.getAttribute('data-index')];
            var key = e.target.getAttribute('data-key');
            if (key === 'type') {
                group.type = e.target.value === 'slide' ? 'slide' : 'list';
                if (group.type === 'slide') {
                    group.autoplay = true;
                    if (!group.autoplay_ms) group.autoplay_ms = 3000;
                    if (group.position !== 'right') group.position = 'left';
                    if (typeof group.show_names !== 'boolean') group.show_names = false;
                } else {
                    group.autoplay = false;
                    if (group.position !== 'right') group.position = 'left';
                }
            }
            if (key === 'position') {
                group.position = e.target.value;
                if (group.type === 'list' && group.position === 'center') group.position = 'left';
            }
            if (key === 'autoplay') group.autoplay = e.target.checked;
            if (key === 'show_names') {
                group.show_names = e.target.checked;
            }
            if (key === 'autoplay_sec') {
                group.autoplay_ms = Math.round((parseFloat(e.target.value) || 3) * 1000);
                return;
            }
            syncPage();
        });
        document.getElementById('slide_per_view').addEventListener('change', renderPreview);
        document.getElementById('autoplay').addEventListener('change', renderPreview);
        document.getElementById('slide_names').addEventListener('change', renderPreview);
        document.getElementById('close_all').addEventListener('change', function () {
            document.querySelector('.pd_opt_close_all').classList.toggle('is-on', this.checked);
            renderPreview();
        });
        document.getElementById('pd_close_pos').addEventListener('click', function (e) {
            var btn = e.target.closest('.pop_tpl');
            if (!btn) return;
            document.querySelectorAll('#pd_close_pos .pop_tpl').forEach(function (el) {
                el.classList.toggle('is-active', el === btn);
            });
            renderPreview();
        });

        document.getElementById('pd_save_btn').addEventListener('click', function () {
            var btn = document.getElementById('pd_save_btn');
            btn.textContent = '저장 중...';
            fetch('pop_settings_save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(collect())
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res.success) {
                        btn.textContent = '저장됨';
                        rememberSaved();
                        setTimeout(function () { btn.textContent = '저장'; }, 1400);
                    } else {
                        btn.textContent = '저장';
                        alert(res.message || '저장에 실패했습니다.');
                    }
                })
                .catch(function () {
                    btn.textContent = '저장';
                    alert('저장에 실패했습니다.');
                });
        });

        syncPage();
        rememberSaved();
        window.addEventListener('beforeunload', function (e) {
            if (!isDirty()) return;
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
</body>
</html>
