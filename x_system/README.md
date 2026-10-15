# 팝업·비급여 관리자 (`x_system`)

이 폴더와 사이트 루트의 **`관리자페이지_적용가이드.md`** 를 함께 복사하면 다른 홈페이지에 같은 관리자를 붙일 수 있습니다.

자세한 절차·데이터 이전·AI 프롬프트는 가이드를 보세요. 아래는 요약입니다.

## 접속

`https://도메인/x_system/`

계정은 `password.json`입니다. **병원마다 바꿉니다.**

## 홈에 붙이기

```html
<link rel="stylesheet" href="x_system/popup.css">
<script src="x_system/popup.js"></script>
```

슬라이드형은 Swiper 11이 필요합니다. 본문은 `.wrap`으로 감쌉니다.

비급여:

```html
<div id="non-benefit-popup-cont"></div>
<script src="x_system/nonbenefit.js"></script>
```

## 다른 사이트에 붙일 때 (필수)

1. `x_system` 전체를 홈 `index.html` 옆에 복사
2. **원본 병원 데이터는 남기지 말 것** — `pop_json.json`, `pop_settings.json`, `nonbenefit.json`, `password.json`, `uploads/img/pop/`
3. 구 관리자가 있으면 그 데이터로 교체 (표시설정 `display_mode` → `mode`/`preset` 변환은 가이드 참고)
4. 관리자 로고는 홈 헤더와 같은 파일명으로 맞춤 (예: 헤더 `img/h_logo.png` → `../img/h_logo.png`)
5. 홈에 `popup.css` / `popup.js` 연결, 구 인라인 팝업 스크립트 삭제

운영 중인 `pop_json.json`과 업로드 이미지를 빈 파일로 덮어쓰지 마세요.
