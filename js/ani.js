/* =========================================================
 * ani.js
 * 앵커 이동, 섹션 진입 트리거(.is-in-view), 모달·모바일 메뉴
 * ========================================================= */
(function () {
  'use strict';

  var ANCHOR_OFFSET = 60;

  var $main = null;
  var $sections = [];
  var $gnbLinks = [];
  var $anchorLinks = [];
  var current = -1;
  var sectionObserver = null;
  var scrollRaf = null;
  var scrollEndTimer = null;
  var pendingNavIndex = null;
  var SCROLL_END_MS = 150;
  var ANCHOR_SCROLL_DURATION_MS = 1400;
  var isProgrammaticScroll = false;
  var programmaticScrollId = 0;
  var revealTargets = [];

  function getScrollY() {
    return window.pageYOffset || window.scrollY || 0;
  }

  function setProgrammaticScrollLock(locked) {
    isProgrammaticScroll = locked;
    document.documentElement.classList.toggle('is-programmatic-scroll', locked);
    window.dispatchEvent(new CustomEvent(locked ? 'programmatic-scroll-start' : 'programmatic-scroll-end'));
    if (!locked) {
      requestAnimationFrame(flushReveal);
    }
  }

  function onLockedUserScroll(e) {
    if (!isProgrammaticScroll) return;
    e.preventDefault();
  }

  function readScrollTarget(targetTop) {
    return typeof targetTop === 'function' ? targetTop() : targetTop;
  }

  function scrollDurationFor(distance) {
    return Math.min(ANCHOR_SCROLL_DURATION_MS, Math.max(360, Math.abs(distance) * 0.55));
  }

  function smoothScrollTo(targetTop, onComplete) {
    if (isProgrammaticScroll) return;

    var startY = window.pageYOffset || window.scrollY || 0;
    var destY = readScrollTarget(targetTop);
    var distance = destY - startY;

    if (Math.abs(distance) < 1) {
      if (onComplete) onComplete();
      return;
    }

    var startTime = null;
    var duration = scrollDurationFor(distance);
    var animId = ++programmaticScrollId;
    setProgrammaticScrollLock(true);

    function easeOutCubic(progress) {
      return 1 - Math.pow(1 - progress, 3);
    }

    function finish() {
      if (animId !== programmaticScrollId) return;
      window.scrollTo(0, readScrollTarget(targetTop));
      setProgrammaticScrollLock(false);
      if (onComplete) onComplete();
    }

    function step(timestamp) {
      if (animId !== programmaticScrollId) return;
      if (!startTime) startTime = timestamp;
      var elapsed = timestamp - startTime;
      var progress = Math.min(elapsed / duration, 1);
      var liveDest = readScrollTarget(targetTop);
      window.scrollTo(0, startY + (liveDest - startY) * easeOutCubic(progress));
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        finish();
      }
    }

    requestAnimationFrame(step);
  }

  function setReveal(el, on) {
    if (!el) return;
    if (el.classList.contains('con3-panel')) {
      if (!el.classList.contains('is-active')) {
        el.classList.remove('is-in-view', 'is-tab-in');
        return;
      }
      if (on) {
        el.classList.add('is-in-view');
        return;
      }
      if (!el.classList.contains('is-tab-in')) {
        el.classList.remove('is-in-view');
      }
      return;
    }
    el.classList.toggle('is-in-view', on);
  }

  function isRevealVisible(el) {
    if (!el || !el.getClientRects().length) return false;
    var rect = el.getBoundingClientRect();
    var vh = window.innerHeight || document.documentElement.clientHeight;
    var top = Math.max(rect.top, 0);
    var bottom = Math.min(rect.bottom, vh * 0.88);
    var visible = bottom - top;
    if (visible <= 0) return false;
    return visible / Math.max(rect.height, 1) >= 0.05 || visible >= 8;
  }

  function flushReveal() {
    revealTargets.forEach(function (el) {
      var visible = isRevealVisible(el);
      if (!visible) {
        setReveal(el, false);
        return;
      }
      if (!el.classList.contains('is-in-view')) {
        el.classList.add('is-in-view');
      }
    });
  }

  function bindSectionObserver() {
    if (sectionObserver) return;

    revealTargets = Array.prototype.slice.call(document.querySelectorAll([
      '#con1-1 .con1-intro',
      '#con1-2 .con-head',
      '#con1-2 .con1-profile__info',
      '#con2 .con-head',
      '#con3 .con-head',
      '#con3 .con3-panel',
      '#con4 .con-head',
      '#con4 .schedule-wrap'
    ].join(',')));

    if (!('IntersectionObserver' in window)) {
      revealTargets.forEach(function (el) {
        setReveal(el, true);
      });
      return;
    }

    sectionObserver = new IntersectionObserver(function (entries) {
      if (isProgrammaticScroll) return;
      entries.forEach(function (entry) {
        setReveal(entry.target, entry.isIntersecting);
      });
    }, {
      root: null,
      rootMargin: '0px 0px -12% 0px',
      threshold: 0.05
    });

    revealTargets.forEach(function (el) {
      sectionObserver.observe(el);
    });
  }

  function goTo(idx, opts) {
    opts = opts || {};
    if (idx < 0) idx = 0;
    if (idx > $sections.length - 1) idx = $sections.length - 1;
    if (!opts.instant && isProgrammaticScroll) return;
    if (opts.instant && idx === current) return;

    var rect = $sections[idx].getBoundingClientRect();
    var targetTop = getScrollY() + rect.top - ANCHOR_OFFSET;
    if (targetTop < 0) targetTop = 0;

    if (opts.instant) {
      pendingNavIndex = null;
      window.scrollTo({ top: targetTop, left: 0, behavior: 'auto' });
      syncActiveSectionFromScroll();
      return;
    }

    pendingNavIndex = idx;
    smoothScrollTo(function () {
      var live = getScrollY() + $sections[idx].getBoundingClientRect().top - ANCHOR_OFFSET;
      return live < 0 ? 0 : live;
    }, finishPendingNavScroll);
  }

  function getSectionTop(sec) {
    return sec.getBoundingClientRect().top + getScrollY();
  }

  /** 헤더 아래 기준선 — 이 선을 지난 마지막 섹션이 active */
  function getScrollProbeY() {
    return getScrollY() + ANCHOR_OFFSET + 1;
  }

  function syncActiveSectionFromScroll() {
    if (!$sections.length || pendingNavIndex !== null) return;

    var probe = getScrollProbeY();
    var newCurrent = 0;

    for (var i = 0; i < $sections.length; i++) {
      if (getSectionTop($sections[i]) <= probe) {
        newCurrent = i;
      }
    }

    if (newCurrent === current) return;
    current = newCurrent;
    updateActiveNav();
  }

  function finishPendingNavScroll() {
    if (pendingNavIndex === null) return;
    current = pendingNavIndex;
    pendingNavIndex = null;
    updateActiveNav();
  }

  function syncHeaderScrollState() {
    var header = document.querySelector('header');
    if (!header) return;
    header.classList.toggle('is-scrolled', getScrollY() > 10);
  }

  function onScroll() {
    if (scrollRaf) return;
    scrollRaf = requestAnimationFrame(function () {
      scrollRaf = null;
      syncHeaderScrollState();
      syncActiveSectionFromScroll();
    });

    clearTimeout(scrollEndTimer);
    scrollEndTimer = setTimeout(function () {
      if (pendingNavIndex !== null) {
        finishPendingNavScroll();
      } else {
        syncActiveSectionFromScroll();
      }
    }, SCROLL_END_MS);
  }

  function updateActiveNav() {
    var sec = current >= 0 ? $sections[current] : null;
    var currentGroup = sec ? (sec.getAttribute('data-group') || sec.id) : null;

    $gnbLinks.forEach(function (a) {
      var href = a.getAttribute('href') || '';
      var anchor = href.replace(/^#/, '');
      var isActive = !!(sec && (anchor === sec.id || anchor === currentGroup));
      a.classList.toggle('active', isActive);
      if (a.parentElement) {
        a.parentElement.classList.toggle('active', isActive);
      }
    });
  }

  function bindPolicyModals() {
    var modals = document.querySelectorAll('.policy-modal');
    if (!modals.length) return;

    function openModal(id) {
      var modal = document.querySelector('.policy-modal[data-popup-id="' + id + '"]');
      if (!modal) return;
      modal.hidden = false;
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          modal.classList.add('is-open');
        });
      });
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('is-modal-open');
      var body = modal.querySelector('.policy-modal__body');
      if (body) body.scrollTop = 0;
    }

    function closeModal(modal) {
      if (!modal || !modal.classList.contains('is-open')) return;
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      window.setTimeout(function () {
        modal.hidden = true;
        if (!document.querySelector('.policy-modal.is-open')) {
          document.body.classList.remove('is-modal-open');
        }
      }, 300);
    }

    document.querySelectorAll('[data-popup]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var id = a.getAttribute('data-popup');
        if (id === 'preparing') {
          window.alert('준비중입니다.');
          return;
        }
        openModal(id);
      });
    });

    document.querySelectorAll('[data-popup-close]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.stopPropagation();
        var modal = el.closest('.policy-modal');
        if (modal) closeModal(modal);
      });
    });

    window.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      var open = document.querySelector('.policy-modal.is-open');
      if (open) closeModal(open);
    });
  }

  function bindMobileMenu() {
    var $menuBtn = document.querySelector('.hamburger');
    var $nav = document.getElementById('header-nav');
    if (!$menuBtn || !$nav) return;

    function closeMenu() {
      $menuBtn.classList.remove('is-active');
      $menuBtn.setAttribute('aria-expanded', 'false');
      $menuBtn.setAttribute('aria-label', '메뉴 열기');
      $nav.classList.remove('menu_open');
      document.body.classList.remove('is-menu-open');
    }

    function openMenu() {
      $menuBtn.classList.add('is-active');
      $menuBtn.setAttribute('aria-expanded', 'true');
      $menuBtn.setAttribute('aria-label', '메뉴 닫기');
      $nav.classList.add('menu_open');
      document.body.classList.add('is-menu-open');
    }

    $menuBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if ($menuBtn.classList.contains('is-active')) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    document.querySelectorAll('header nav .gnb a').forEach(function (a) {
      a.addEventListener('click', closeMenu);
    });

    document.addEventListener('click', function (e) {
      if (!$nav.classList.contains('menu_open')) return;
      if ($nav.contains(e.target) || $menuBtn.contains(e.target)) return;
      closeMenu();
    });

    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeMenu();
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 1200) closeMenu();
    });
  }

  function getElementScrollTop(el, cachedMargin) {
    var rect = el.getBoundingClientRect();
    var margin = cachedMargin;
    if (margin == null) {
      margin = parseFloat(window.getComputedStyle(el).scrollMarginTop);
      if (isNaN(margin)) margin = ANCHOR_OFFSET;
    }
    var top = getScrollY() + rect.top - margin;
    return top < 0 ? 0 : top;
  }

  function scrollToElement(el) {
    if (isProgrammaticScroll) return;
    pendingNavIndex = null;
    var margin = parseFloat(window.getComputedStyle(el).scrollMarginTop);
    if (isNaN(margin)) margin = ANCHOR_OFFSET;
    var destY = getElementScrollTop(el, margin);
    smoothScrollTo(destY, function () {
      syncActiveSectionFromScroll();
    });
  }

  function onAnchorClick(e) {
    var a = e.currentTarget;
    var href = a.getAttribute('href') || '';
    if (!href.startsWith('#') || href === '#') return;
    var target = document.querySelector(href);
    if (!target) return;
    var idx = $sections.indexOf(target);
    e.preventDefault();
    if (idx >= 0) {
      goTo(idx, { instant: false });
      return;
    }
    scrollToElement(target);
  }

  function applyInitialHash() {
    var hash = location.hash;
    if (!hash || hash === '#') return;
    var target = document.querySelector(hash);
    if (!target) return;
    var idx = $sections.indexOf(target);
    setTimeout(function () {
      if (idx >= 0) {
        goTo(idx, { instant: true });
        syncActiveSectionFromScroll();
        return;
      }
      window.scrollTo({ top: getElementScrollTop(target), left: 0, behavior: 'auto' });
      syncActiveSectionFromScroll();
    }, 50);
  }

  function bindQuickMenu() {
    var topBtn = document.getElementById('top_btn');
    if (!topBtn) return;
    topBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function init() {
    bindMobileMenu();
    bindQuickMenu();
    bindPolicyModals();

    $main = document.getElementById('main');
    if (!$main) return;

    $sections = Array.prototype.slice.call($main.querySelectorAll('.fp-section'));
    $gnbLinks = Array.prototype.slice.call(document.querySelectorAll('header nav .gnb a'));
    $anchorLinks = Array.prototype.slice.call(document.querySelectorAll('header nav a, .fp-anchor, .con2-parts a'));

    $anchorLinks.forEach(function (a) {
      a.addEventListener('click', onAnchorClick);
    });

    bindSectionObserver();
    window.addEventListener('wheel', onLockedUserScroll, { passive: false });
    window.addEventListener('touchmove', onLockedUserScroll, { passive: false });
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', function () {
      onScroll();
    }, { passive: true });

    syncHeaderScrollState();
    syncActiveSectionFromScroll();
    applyInitialHash();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
