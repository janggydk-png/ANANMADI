<?php
/**
 * 홈 index.html 푸터에서 병원 이름을 읽어 관리자 화면에 표시한다.
 * '상호명 :' 문구에 의존하지 않고, 라벨·로고 다음 텍스트·로고 alt 순으로 찾는다.
 */
function admin_clinic_name()
{
    static $name = null;
    if ($name !== null) {
        return $name;
    }

    $htmlPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.html';
    $html = @file_get_contents($htmlPath);
    $name = admin_clinic_name_from_html(is_string($html) ? $html : '');
    return $name;
}

function admin_clinic_name_from_html($html)
{
    if (!is_string($html) || $html === '') {
        return '';
    }

    $footer = admin_clinic_footer_html($html);
    if ($footer === '') {
        $footer = $html;
    }

    $candidates = [];
    foreach ([
        admin_clinic_name_labeled($footer),
        admin_clinic_name_from_alt($footer),
        admin_clinic_name_after_logo($footer),
        admin_clinic_name_from_lines($footer),
    ] as $candidate) {
        if ($candidate !== '' && !in_array($candidate, $candidates, true)) {
            $candidates[] = $candidate;
        }
    }

    foreach ($candidates as $candidate) {
        if (preg_match('/의원|병원|클리닉/u', $candidate)) {
            return $candidate;
        }
    }
    foreach ($candidates as $candidate) {
        if (preg_match('/[가-힣]/u', $candidate)) {
            return $candidate;
        }
    }

    return $candidates[0] ?? '';
}

function admin_clinic_footer_html($html)
{
    if (preg_match('/<footer\b[^>]*>.*<\/footer>/is', $html, $m)) {
        return $m[0];
    }
    if (preg_match('/<(?:div|section)\b[^>]*id=["\']footer["\'][^>]*>.*$/is', $html, $m)) {
        return $m[0];
    }
    return '';
}

function admin_clinic_name_clean($value)
{
    $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value);
    $value = trim($value, " \t\n\r\0\x0B:：|·-");
    $value = preg_replace('/\s*(?:로고|logo)$/iu', '', $value);
    return trim($value);
}

function admin_clinic_name_reject($value)
{
    $value = admin_clinic_name_clean($value);
    if ($value === '') {
        return true;
    }
    $len = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($len < 2 || $len > 80) {
        return true;
    }
    if (preg_match('/https?:|www\.|@|\d{2,3}-\d{3,4}-\d{4}/iu', $value)) {
        return true;
    }
    if (preg_match('/^(개인정보|이용약관|비급여|copyright|©|copy\b|대표\b|대표자|사업자|주소\b|전화\b|대표전화|팩스|fax|메일|e-?mail|주차|family|관련사이트)/iu', $value)) {
        return true;
    }
    if (preg_match('/^(상호명|상호|병원명|의원명)\s*:?\s*$/u', $value)) {
        return true;
    }
    return false;
}

function admin_clinic_name_labeled($html)
{
    $label = '(?:상호명|상호|병원명|의원명)';
    $patterns = [
        '/<(?:span|b|strong|em|dt|th|label)[^>]*>\s*' . $label . '\s*[:：]?\s*<\/(?:span|b|strong|em|dt|th|label)>\s*([^<]{2,80})/u',
        '/<(?:b|strong)[^>]*>\s*' . $label . '\s*[:：]?\s*<\/(?:b|strong)>\s*([^<]{2,80})/u',
        '/' . $label . '\s*[:：]\s*([^<\n|]{2,80})/u',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $m)) {
            $value = admin_clinic_name_clean($m[1]);
            if (!admin_clinic_name_reject($value)) {
                return $value;
            }
        }
    }
    return '';
}

function admin_clinic_footer_lines($html)
{
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html);
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $html = preg_replace('/<\/(?:li|p|div|h[1-6]|dt|dd|td|th|span|b|strong)>/i', "$0\n", $html);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $parts = preg_split('/\R+/u', $text);
    $lines = [];
    foreach ($parts as $line) {
        $line = admin_clinic_name_clean($line);
        if ($line !== '') {
            $lines[] = $line;
        }
    }
    return $lines;
}

function admin_clinic_name_from_lines($html)
{
    $lines = admin_clinic_footer_lines($html);
    $pendingLabel = false;
    foreach ($lines as $line) {
        if (preg_match('/^(상호명|상호|병원명|의원명)\s*:?\s*(.*)$/u', $line, $m)) {
            $rest = admin_clinic_name_clean($m[2]);
            if ($rest !== '' && !admin_clinic_name_reject($rest)) {
                return $rest;
            }
            $pendingLabel = true;
            continue;
        }
        if ($pendingLabel) {
            $pendingLabel = false;
            if (!admin_clinic_name_reject($line)) {
                return $line;
            }
        }
        if (!admin_clinic_name_reject($line)) {
            return $line;
        }
    }
    return '';
}

function admin_clinic_name_after_logo($html)
{
    $cut = $html;
    if (preg_match('/<(?:div|p|a)\b[^>]*class=["\'][^"\']*(?:footer__logo|f_logo|ft-logo|footer-logo|logo)[^"\']*["\'][^>]*>.*?<\/(?:div|p|a)>/is', $html, $m, PREG_OFFSET_CAPTURE)) {
        $cut = substr($html, $m[0][1] + strlen($m[0][0]));
    } elseif (preg_match('/<img\b[^>]*(?:logo|f_logo)[^>]*>/is', $html, $m, PREG_OFFSET_CAPTURE)) {
        $cut = substr($html, $m[0][1] + strlen($m[0][0]));
    }
    if ($cut === $html) {
        return '';
    }
    return admin_clinic_name_from_lines($cut);
}

function admin_clinic_name_from_alt($html)
{
    if (!preg_match('/<img\b[^>]*alt=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
        return '';
    }
    $alt = admin_clinic_name_clean($m[1]);
    if (strpos($alt, '|') !== false) {
        $parts = preg_split('/\s*\|\s*/', $alt);
        $alt = admin_clinic_name_clean(end($parts));
    }
    if (admin_clinic_name_reject($alt)) {
        return '';
    }
    return $alt;
}
