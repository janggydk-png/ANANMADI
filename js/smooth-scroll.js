/* =========================================================
 * smooth-scroll.js
 * 휠/트랙패드만 rAF 이징. 터치·내부 스크롤·Ctrl+줌은 기본 동작 유지
 * ========================================================= */
(function () {
  'use strict';

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var EASE = 0.085;
  var EPSILON = 0.6;

  var currentY = window.scrollY || window.pageYOffset || 0;
  var targetY = currentY;
  var running = false;
  var lastRounded = Math.round(currentY);
  var locked = false;

  function getMaxY() {
    return Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
  }

  function clamp(y) {
    var max = getMaxY();
    if (y < 0) return 0;
    if (y > max) return max;
    return y;
  }

  function readScrollY() {
    return window.scrollY || window.pageYOffset || 0;
  }

  function syncFromWindow() {
    currentY = readScrollY();
    targetY = currentY;
    lastRounded = Math.round(currentY);
  }

  function isScrollableOverflowY(value) {
    return value === 'auto' || value === 'scroll' || value === 'overlay';
  }

  function canScrollBox(el, deltaY) {
    var overflowY = window.getComputedStyle(el).overflowY;
    if (!isScrollableOverflowY(overflowY)) return false;
    var max = el.scrollHeight - el.clientHeight;
    if (max <= 1) return false;
    if (deltaY > 0) return el.scrollTop < max - 1;
    if (deltaY < 0) return el.scrollTop > 0;
    return false;
  }

  function shouldKeepNative(target, deltaY) {
    var el = target;
    if (el && el.nodeType !== 1) el = el.parentElement;
    while (el && el !== document.body && el !== document.documentElement) {
      if (canScrollBox(el, deltaY)) return true;
      el = el.parentElement;
    }
    return false;
  }

  function isMapArea(target) {
    var el = target && target.nodeType === 1 ? target : target && target.parentElement;
    return !!(el && el.closest && el.closest('.schedule-map, .root_daum_roughmap'));
  }

  function isPageScrollLocked() {
    var bodyOverflow = window.getComputedStyle(document.body).overflow;
    return bodyOverflow === 'hidden';
  }

  function applyScroll(y) {
    var rounded = Math.round(y);
    if (rounded === lastRounded) return;
    lastRounded = rounded;
    window.scrollTo(0, rounded);
  }

  function stopLoop() {
    running = false;
    syncFromWindow();
  }

  function tick() {
    if (locked) {
      running = false;
      return;
    }
    var max = getMaxY();
    targetY = clamp(targetY);
    if (currentY < 0) currentY = 0;
    if (currentY > max) currentY = max;

    var diff = targetY - currentY;
    if (Math.abs(diff) <= EPSILON) {
      currentY = targetY;
      applyScroll(currentY);
      running = false;
      return;
    }

    currentY += diff * EASE;
    applyScroll(currentY);
    requestAnimationFrame(tick);
  }

  function startLoop() {
    if (running) return;
    running = true;
    requestAnimationFrame(tick);
  }

  window.addEventListener('programmatic-scroll-start', function () {
    locked = true;
    stopLoop();
  });

  window.addEventListener('programmatic-scroll-end', function () {
    locked = false;
    stopLoop();
  });

  window.addEventListener('wheel', function (e) {
    if (e.ctrlKey) return;
    if (locked || document.documentElement.classList.contains('is-programmatic-scroll')) {
      e.preventDefault();
      return;
    }
    if (isPageScrollLocked()) return;
    if (isMapArea(e.target)) {
      e.preventDefault();
      if (running) stopLoop();
      return;
    }
    if (shouldKeepNative(e.target, e.deltaY)) return;

    e.preventDefault();

    if (!running) syncFromWindow();

    var deltaY = e.deltaY;
    if (e.deltaMode === 1) deltaY *= 16;
    else if (e.deltaMode === 2) deltaY *= window.innerHeight;

    targetY = clamp(targetY + deltaY);
    startLoop();
  }, { passive: false });

  window.addEventListener('scroll', function () {
    if (running) return;
    syncFromWindow();
  }, { passive: true });

  window.addEventListener('resize', function () {
    targetY = clamp(targetY);
    currentY = clamp(currentY);
  }, { passive: true });
})();
