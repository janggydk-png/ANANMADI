<?php

/** JSON에 저장된 팝업 이미지 주소를 현재 폴더 기준 상대 경로로 바꾼다. */
function admin_pop_image_src($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#uploads/img/pop/([^/?#]+)#', $url, $m)) {
        return 'uploads/img/pop/' . $m[1];
    }
    return $url;
}
