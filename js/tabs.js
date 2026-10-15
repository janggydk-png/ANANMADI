/**
 * tabs.js - 비수술 치료 안내(#con3) 탭
 */
(function () {
  'use strict';

  function initStackedTabs(rootId, navSel, bundleSel, panelSel) {
    var root = document.getElementById(rootId);
    if (!root) return;

    var tabs = root.querySelectorAll(navSel);
    var bundles = root.querySelectorAll(bundleSel);
    var panels = root.querySelectorAll(panelSel);
    var indicator = root.querySelector('.con3-indicator');
    var indicatorItems = indicator ? indicator.querySelectorAll('li') : [];
    if (!tabs.length || !bundles.length || !panels.length) return;

    var activeIdx = -1;
    tabs.forEach(function (tab, i) {
      if (tab.classList.contains('is-active')) activeIdx = i;
    });
    if (activeIdx < 0) activeIdx = 0;

    function activate(idx) {
      if (idx === activeIdx) return;
      activeIdx = idx;

      tabs.forEach(function (tab, i) {
        var on = i === idx;
        tab.classList.toggle('is-active', on);
        tab.setAttribute('aria-selected', on ? 'true' : 'false');
        tab.setAttribute('tabindex', on ? '0' : '-1');
      });
      if (indicator) {
        indicator.style.setProperty('--con3-i', String(idx));
      }
      indicatorItems.forEach(function (item, i) {
        item.classList.toggle('is-active', i === idx);
      });
      bundles.forEach(function (bundle, i) {
        bundle.classList.toggle('is-active', i === idx);
      });
      panels.forEach(function (panel, i) {
        var on = i === idx;
        panel.setAttribute('aria-hidden', on ? 'false' : 'true');
        panel.classList.remove('is-tab-in');
        if (!on) panel.classList.remove('is-in-view');
      });

      var current = panels[idx];
      current.classList.add('is-in-view');
      void current.offsetWidth;
      current.classList.add('is-tab-in');
    }

    var compactQuery = window.matchMedia('(max-width: 900px)');

    function syncIndicatorLayout() {
      if (!indicator) return;
      var compact = compactQuery.matches;
      indicator.classList.toggle('is-compact', compact);
      var activeIdx = 0;
      indicatorItems.forEach(function (item, i) {
        if (item.classList.contains('is-active')) activeIdx = i;
      });
      indicator.style.setProperty('--con3-i', String(activeIdx));
    }

    if (compactQuery.addEventListener) {
      compactQuery.addEventListener('change', syncIndicatorLayout);
    } else if (compactQuery.addListener) {
      compactQuery.addListener(syncIndicatorLayout);
    }
    window.addEventListener('resize', syncIndicatorLayout);
    syncIndicatorLayout();

    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () {
        activate(i);
      });

      tab.addEventListener('keydown', function (e) {
        var next = i;
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
          next = (i + 1) % tabs.length;
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
          next = (i - 1 + tabs.length) % tabs.length;
        } else {
          return;
        }
        e.preventDefault();
        activate(next);
        tabs[next].focus();
      });
    });
  }

  initStackedTabs('con3', '.con3-nav button', '.con3-panel', '.con3-panel');
})();
