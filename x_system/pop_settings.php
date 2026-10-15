<?php

function pop_clamp($n, $min, $max)
{
    $n = (int) $n;
    if ($n < $min) return $min;
    if ($n > $max) return $max;
    return $n;
}

function pop_settings_defaults()
{
    return [
        'mode' => 'preset',
        'preset' => 'list',
        'slide_per_view' => 2,
        'autoplay' => true,
        'autoplay_ms' => 3000,
        'slide_names' => false,
        'close_all' => false,
        'close_all_pos' => 'top',
        'groups' => [],
    ];
}

function pop_settings_path()
{
    return __DIR__ . '/pop_settings.json';
}

function pop_settings_load()
{
    $raw = json_decode((string) @file_get_contents(pop_settings_path()), true);
    if (!is_array($raw)) {
        return pop_settings_defaults();
    }
    return pop_settings_normalize($raw);
}

function pop_normalize_group($group, $index)
{
    if (!is_array($group)) {
        $group = [];
    }
    $type = ($group['type'] ?? 'list') === 'slide' ? 'slide' : 'list';
    $position = (string) ($group['position'] ?? 'left');
    if (!in_array($position, ['left', 'center', 'right'], true)) {
        $position = 'left';
    }
    if ($position === 'center') {
        $position = 'left';
    }
    $ids = [];
    if (!empty($group['ids']) && is_array($group['ids'])) {
        foreach ($group['ids'] as $id) {
            $id = trim((string) $id);
            if ($id !== '') $ids[] = $id;
        }
    }
    return [
        'id' => trim((string) ($group['id'] ?? '')) !== '' ? (string) $group['id'] : ('g' . ($index + 1)),
        'type' => $type,
        'position' => $position,
        'ids' => $ids,
        'autoplay' => !empty($group['autoplay']),
        'autoplay_ms' => pop_clamp($group['autoplay_ms'] ?? 3000, 1000, 15000),
        'show_names' => !empty($group['show_names']),
    ];
}

function pop_settings_normalize($input)
{
    $settings = pop_settings_defaults();
    if (!is_array($input)) {
        return $settings;
    }

    $mode = (string) ($input['mode'] ?? '');
    if ($mode !== 'custom') {
        $mode = 'preset';
    }
    $settings['mode'] = $mode;

    $preset = (string) ($input['preset'] ?? $input['template'] ?? $input['layout'] ?? 'list');
    if (!in_array($preset, ['list', 'slide', 'slide3'], true)) {
        $preset = 'list';
    }
    $settings['preset'] = $preset;
    $perView = $input['slide_per_view'] ?? null;
    // 구버전 설정 파일(slide_per_view 없음)은 dummy_count로 노출 장수를 추정한다. 저장은 하지 않는다.
    if ($perView === null && (int) ($input['dummy_count'] ?? 0) === 3) {
        $perView = 3;
    }
    $settings['slide_per_view'] = pop_clamp($perView ?? 2, 2, 3);
    $settings['autoplay'] = array_key_exists('autoplay', $input) ? !empty($input['autoplay']) : true;
    $settings['autoplay_ms'] = pop_clamp($input['autoplay_ms'] ?? 3000, 1000, 15000);
    $settings['slide_names'] = !empty($input['slide_names']);
    $settings['close_all'] = !empty($input['close_all']);
    $settings['close_all_pos'] = (($input['close_all_pos'] ?? '') === 'bottom') ? 'bottom' : 'top';

    $groups = [];
    $usedIds = [];
    if (!empty($input['groups']) && is_array($input['groups'])) {
        foreach ($input['groups'] as $i => $group) {
            $normalized = pop_normalize_group($group, $i);
            $kept = [];
            foreach ($normalized['ids'] as $id) {
                if (in_array($id, $usedIds, true)) {
                    continue;
                }
                $usedIds[] = $id;
                $kept[] = $id;
            }
            $normalized['ids'] = $kept;
            $groups[] = $normalized;
        }
    }
    $settings['groups'] = $groups;

    return $settings;
}

function pop_settings_prune_expired($settings)
{
    if (!is_array($settings) || empty($settings['groups']) || !is_array($settings['groups'])) {
        return $settings;
    }

    $byId = [];
    foreach (pop_ensure_unique_ids() as $item) {
        $id = (string) ($item['uniqueId'] ?? '');
        if ($id !== '') $byId[$id] = $item;
    }

    $groups = [];
    foreach ($settings['groups'] as $i => $group) {
        $ids = [];
        foreach ((array) ($group['ids'] ?? []) as $id) {
            $id = (string) $id;
            if (!isset($byId[$id])) continue;
            $ids[] = $id;
        }
        $group['ids'] = $ids;
        $groups[] = pop_normalize_group($group, count($groups));
    }
    $settings['groups'] = $groups;
    return $settings;
}

function pop_settings_save($settings)
{
    $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return $json !== false && file_put_contents(pop_settings_path(), $json, LOCK_EX) !== false;
}

function pop_json_path()
{
    return __DIR__ . '/pop_json.json';
}

function pop_load_items()
{
    $data = json_decode((string) @file_get_contents(pop_json_path()), true);
    return is_array($data) ? $data : [];
}

function pop_ensure_unique_ids()
{
    $path = pop_json_path();
    $data = pop_load_items();
    $changed = false;
    foreach ($data as $i => $item) {
        if (empty($item['uniqueId'])) {
            $data[$i]['uniqueId'] = uniqid();
            $changed = true;
        }
    }
    if ($changed) {
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }
    return $data;
}

/** 지금 홈에 실제로 뜨는 팝업인지 (이미지 있음 + 시작함 + 종료 전). popup.js 노출 조건과 같다. */
function pop_item_is_live($item)
{
    if (empty($item['image_url'])) {
        return false;
    }
    try {
        $now = new DateTime();
        $start = new DateTime((string) ($item['start_date'] ?? ''));
        $end = new DateTime((string) ($item['end_date'] ?? ''));
        return $start <= $now && $now <= $end;
    } catch (Exception $e) {
        return false;
    }
}

function pop_live_count()
{
    $count = 0;
    foreach (pop_load_items() as $item) {
        if (pop_item_is_live($item)) {
            $count++;
        }
    }
    return $count;
}

function pop_item_is_active($item)
{
    try {
        $end = new DateTime((string) ($item['end_date'] ?? ''));
        return new DateTime() <= $end;
    } catch (Exception $e) {
        return true;
    }
}

function pop_items_for_admin()
{
    $items = [];
    foreach (pop_ensure_unique_ids() as $item) {
        $items[] = [
            'id' => (string) $item['uniqueId'],
            'title' => (string) ($item['title'] ?? ''),
            'has_image' => !empty($item['image_url']),
            'active' => pop_item_is_active($item),
            'live' => pop_item_is_live($item),
        ];
    }
    return $items;
}
