(function () {
  function unicodeToBase64(str) {
    return btoa(unescape(encodeURIComponent(str)));
  }

  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
  }

  function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(";");
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i].trim();
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  }

  function getHiddenPopups() {
    var hiddenPopupsString = getCookie("hiddenPopupsNew");
    return hiddenPopupsString ? Array.from(new Set(hiddenPopupsString.split(","))) : [];
  }

  function setHiddenPopups(hiddenPopups) {
    setCookie("hiddenPopupsNew", Array.from(new Set(hiddenPopups)).join(","), 1);
  }

  function loadJson(url) {
    return fetch(url, { cache: "no-cache" }).then(function (res) {
      if (!res.ok) throw new Error("load failed");
      return res.json();
    });
  }

  function escAttr(value) {
    return String(value == null ? "" : value).replace(/"/g, "&quot;");
  }

  function popupImageSrc(url) {
    url = String(url == null ? "" : url);
    var match = url.match(/uploads\/img\/pop\/([^/?#]+)/);
    if (match) return popupBase + "uploads/img/pop/" + match[1];
    if (/^https?:\/\//i.test(url) || url.indexOf("/") === 0) return url;
    return popupBase + url.replace(/^\.\//, "");
  }

  function popupImageHtml(popup) {
    var img = "<img src=\"" + popupImageSrc(popup.image_url) + "\" alt=\"" + escAttr(popup.title) + "\" draggable=\"false\" fetchpriority=\"high\" decoding=\"async\">";
    var popLink = popup.pop_link;
    var isValidLink =
      popLink &&
      popLink !== "javascript:void(0)" &&
      popLink !== "#" &&
      popLink.indexOf("javascript:") !== 0;
    if (isValidLink) return "<a href=\"" + popLink + "\">" + img + "</a>";
    return "<div>" + img + "</div>";
  }

  function nameGridMeta(n, maxCols) {
    maxCols = maxCols || 3;
    if (n <= 1) return { cols: 1, rem: 0 };
    if (maxCols <= 3) {
      var cols = n === 2 || n === 4 ? 2 : 3;
      return { cols: cols, rem: n % cols };
    }
    if (n <= maxCols) return { cols: n, rem: 0 };
    return { cols: maxCols, rem: n % maxCols };
  }

  function layoutNameRow(row, maxCols) {
    if (!row) return;
    maxCols = maxCols || 3;
    var meta = nameGridMeta(row.querySelectorAll(".popup_name").length, maxCols);
    row.setAttribute("data-max", String(maxCols));
    row.setAttribute("data-cols", String(meta.cols));
    row.setAttribute("data-rem", String(meta.rem));
  }

  function footHtml() {
    return "<label><span><input type=\"checkbox\" class=\"dontShowToday\" /> 오늘 하루 보지 않기</span></label><span class=\"popup_close\">닫기</span>";
  }

  function whenSwiperReady(callback) {
    if (typeof Swiper === "function") {
      callback();
      return;
    }
    var tries = 0;
    var timer = setInterval(function () {
      tries += 1;
      if (typeof Swiper === "function") {
        clearInterval(timer);
        callback();
      } else if (tries > 100) {
        clearInterval(timer);
        console.error("Swiper 라이브러리를 불러오지 못했습니다.");
      }
    }, 50);
  }

  var scriptEl = document.currentScript;
  var popupBase = "x_system/";
  if (scriptEl && scriptEl.src) {
    popupBase = scriptEl.src.replace(/[^/]+$/, "");
  }

  /** 헤더 실측 높이 → --header-offset. 나열형 top = header + list-inset */
  function syncHeaderOffset() {
    var header =
      document.getElementById("header") ||
      document.querySelector("header") ||
      document.querySelector(".header");
    var h = 0;
    if (header) {
      h = Math.round(header.getBoundingClientRect().height || header.offsetHeight || 0);
    }
    if (h < 1) h = window.matchMedia("(max-width: 1024px)").matches ? 80 : 100;
    document.documentElement.style.setProperty("--header-offset", h + "px");
    return h;
  }

  Promise.all([
    loadJson(popupBase + "pop_json.json"),
    loadJson(popupBase + "pop_settings.json").catch(function () { return {}; }),
  ])
    .then(function (results) {
      var data = results[0] || [];
      var settings = results[1] || {};
      var now = new Date();
      var hiddenPopups = getHiddenPopups();

      data.forEach(function (popup) {
        if (!popup.uniqueId) {
          popup.uniqueId = unicodeToBase64(popup.start_date + "_" + popup.title);
        }
      });

      var validPopups = data.filter(function (popup) {
        if (!popup.image_url) return false;
        return (
          new Date(popup.start_date) <= now &&
          new Date(popup.end_date) >= now &&
          hiddenPopups.indexOf(popup.uniqueId) === -1
        );
      });
      if (!validPopups.length) return;

      var byId = {};
      validPopups.forEach(function (popup) {
        byId[popup.uniqueId] = popup;
      });

      function pickIds(ids) {
        if (!ids || !ids.length) return [];
        return ids.map(function (id) { return byId[id]; }).filter(Boolean);
      }

      var mode = settings.mode === "custom" ? "custom" : "preset";
      var preset = settings.preset || settings.template || "list";
      var groups = [];

      if (mode === "custom" && settings.groups && settings.groups.length) {
        settings.groups.forEach(function (group) {
          var items = pickIds(group.ids);
          if (!items.length) return;
          var type = group.type === "slide" ? "slide" : "list";
          groups.push({
            type: type,
            position: group.position === "right" ? "right" : "left",
            items: items,
            per_view: 1,
            autoplay: type === "slide" && !!group.autoplay,
            autoplay_ms: parseInt(group.autoplay_ms, 10) || parseInt(settings.autoplay_ms, 10) || 3000,
            show_names: type === "slide" && !!group.show_names,
          });
        });
      } else if (preset === "list") {
        groups.push({ type: "list", position: "left", items: validPopups, per_view: 1, autoplay: false });
      } else {
        groups.push({
          type: "slide",
          position: "center",
          items: validPopups,
          per_view: preset === "slide3" ? (parseInt(settings.slide_per_view, 10) || 2) : 1,
          autoplay: settings.autoplay !== false,
          show_names: !!settings.slide_names,
        });
      }

      if (!groups.length) return;

      var autoplayMs = parseInt(settings.autoplay_ms, 10) || 3000;
      var listOnly = groups.every(function (group) { return group.type === "list"; });
      var listLayout = listOnly || mode === "custom";
      var overlay = document.createElement("div");
      overlay.className = "popups" + (listLayout ? " is-list-mode" : "");
      overlay.id = "popupwrap";
      if (listLayout) {
        groups.forEach(function (group) {
          overlay.classList.add("has-" + (group.position === "right" ? "right" : "left"));
        });
      }

      var visibleUnits = 0;
      var useCloseAll = listLayout && !!settings.close_all;

      function lockPageScroll() {
        document.documentElement.classList.add("is-modal-open");
        document.body.classList.add("is-modal-open");
      }
      function unlockPageScroll() {
        document.documentElement.classList.remove("is-modal-open");
        document.body.classList.remove("is-modal-open");
      }
      var scrollPane = null;
      function scrollBox() {
        return scrollPane || overlay;
      }
      function syncOverlayScroll() {
        if (!useCloseAll || overlay.style.display === "none") return;
        var box = scrollBox();
        overlay.classList.toggle("is-scrollable", box.scrollHeight > box.clientHeight + 1);
      }

      function hideIfEmpty() {
        if (visibleUnits > 0) return;
        overlay.style.display = "none";
        if (useCloseAll) unlockPageScroll();
      }

      function stopSliders(root) {
        var targets = root.classList && root.classList.contains("is-slide")
          ? [root]
          : root.querySelectorAll(".popup_group.is-slide");
        Array.prototype.forEach.call(targets, function (group) {
          group.setAttribute("data-closed", "1");
          var sw = group._popupSwiper;
          if (!sw || sw.destroyed) return;
          if (sw.autoplay && sw.autoplay.running) sw.autoplay.stop();
          sw.destroy(false, false);
        });
      }

      function hideIds(ids, hideToday) {
        if (hideToday) {
          ids.forEach(function (id) {
            if (hiddenPopups.indexOf(id) === -1) hiddenPopups.push(id);
          });
          setHiddenPopups(hiddenPopups);
        }
      }

      var flowOrder = 0;
      function markFlow(el, n) {
        if (n == null) n = ++flowOrder;
        el.style.setProperty("--flow-order", String(n));
        el.dataset.flowOrder = String(n);
        return n;
      }

      function isUnitHidden(el) {
        if (!el || el.style.display === "none") return true;
        var group = el.closest(".popup_group");
        return !!(group && group.style.display === "none");
      }

      function syncPileTop() {
        overlay.querySelectorAll(".is-pile-top").forEach(function (el) {
          el.classList.remove("is-pile-top");
        });
        var piled = !overlay.classList.contains("has-close-all")
          && window.matchMedia("(max-width: 600px)").matches;
        if (!piled) return;
        var top = null;
        var topOrder = Infinity;
        overlay.querySelectorAll(".popup_wrap").forEach(function (el) {
          if (isUnitHidden(el)) return;
          var n = parseInt(el.dataset.flowOrder, 10);
          if (isNaN(n)) n = 9999;
          if (n < topOrder) {
            topOrder = n;
            top = el;
          }
        });
        if (top) top.classList.add("is-pile-top");
      }

      function markRightAnchor() {
        overlay.querySelectorAll(".is-right-anchor").forEach(function (el) {
          el.classList.remove("is-right-anchor");
        });
        if (!listLayout || overlay.classList.contains("is-split-wrap")) return;
        if (!overlay.classList.contains("has-left") || !overlay.classList.contains("has-right")) return;
        var col = overlay.querySelector(".popup_col.is-right");
        if (!col) return;
        var groups = col.children;
        for (var i = 0; i < groups.length; i++) {
          var group = groups[i];
          if (group.classList.contains("is-slide")) {
            if (!isUnitHidden(group)) {
              group.classList.add("is-right-anchor");
              return;
            }
            continue;
          }
          var wraps = group.querySelectorAll(".popup_wrap");
          for (var j = 0; j < wraps.length; j++) {
            if (!isUnitHidden(wraps[j])) {
              wraps[j].classList.add("is-right-anchor");
              return;
            }
          }
        }
      }

      function visibleWrapCount(root) {
        var n = 0;
        if (!root) return 0;
        root.querySelectorAll(".popup_wrap").forEach(function (el) {
          if (!isUnitHidden(el)) n += 1;
        });
        return n;
      }

      function sideWidth(count) {
        if (count < 1) return 0;
        return count * 420 + (count - 1) * 12;
      }

      function syncFlowWrap() {
        overlay.classList.remove("is-flow-wrap");
        if (!listLayout || !overlay.classList.contains("has-left") || !overlay.classList.contains("has-right")) {
          overlay.classList.remove("is-split-wrap");
          markRightAnchor();
          syncPileTop();
          return;
        }
        if (window.matchMedia("(max-width: 600px)").matches) {
          overlay.classList.remove("is-split-wrap");
          markRightAnchor();
          syncPileTop();
          return;
        }
        var leftN = visibleWrapCount(overlay.querySelector(".popup_col.is-left"));
        var rightN = visibleWrapCount(overlay.querySelector(".popup_col.is-right"));
        if (leftN < 1 || rightN < 1) {
          overlay.classList.remove("is-split-wrap");
          markRightAnchor();
          syncPileTop();
          return;
        }
        var room = overlay.clientWidth;
        if (room < 1) room = Math.max(0, window.innerWidth - 32);
        var needed = sideWidth(leftN) + sideWidth(rightN) + 12;
        overlay.classList.toggle("is-split-wrap", needed > room + 1);
        markRightAnchor();
        syncPileTop();
        syncOverlayScroll();
      }

      function bindUnitClose(unit, ids) {
        visibleUnits += 1;
        if (useCloseAll) return;
        var closeBtn = unit.querySelector(".popup_close");
        var today = unit.querySelector(".dontShowToday");
        function closeUnit() {
          hideIds(ids, !!(today && today.checked));
          unit.style.display = "none";
          stopSliders(unit);
          visibleUnits -= 1;
          hideIfEmpty();
          syncFlowWrap();
        }
        if (closeBtn) closeBtn.addEventListener("click", closeUnit);
      }

      function makeCard(popup) {
        var card = document.createElement("div");
        card.className = "popup_wrap";
        card.innerHTML = popupImageHtml(popup) + "<div class=\"popup_foot\">" + footHtml() + "</div>";
        bindUnitClose(card, [popup.uniqueId]);
        return card;
      }

      function attachCloseAll(ids) {
        if (!ids.length) return;
        overlay.classList.add("has-close-all");
        if (settings.close_all_pos === "bottom") overlay.classList.add("is-close-bottom");
        var bar = document.createElement("div");
        bar.className = "popup_close_all";
        bar.innerHTML =
          "<button type=\"button\" class=\"popup_close_all_x\" aria-label=\"닫기\">&times;</button>" +
          "<button type=\"button\" class=\"popup_close_all_today\">오늘 하루 보지 않기</button>";
        function closeAll(hideToday) {
          hideIds(ids, !!hideToday);
          overlay.style.display = "none";
          stopSliders(overlay);
          visibleUnits = 0;
          unlockPageScroll();
        }
        bar.querySelector(".popup_close_all_today").addEventListener("click", function () {
          closeAll(true);
        });
        bar.querySelector(".popup_close_all_x").addEventListener("click", function () {
          closeAll(false);
        });
        overlay.appendChild(bar);
      }

      function makeSlideGroup(group) {
        var wrap = document.createElement("div");
        wrap.className = "popup_group is-slide is-" + group.position;

        var card = document.createElement("div");
        card.className = "popup_wrap is-slider";
        var perView = Math.min(group.per_view || 1, group.items.length);
        if (perView < 1) perView = 1;
        var isDummy = (group.per_view || 1) > 1;
        var slideW = listLayout ? 420 : 500;
        var gap = isDummy ? 16 : 0;
        card.style.width = (slideW * perView + gap * (perView - 1)) + "px";
        if (isDummy) {
          card.style.maxWidth = "";
        } else {
          card.style.maxWidth = listLayout ? "min(420px, 100%)" : "calc(100vw - 120px)";
          card.style.width = listLayout ? "min(420px, 100%)" : card.style.width;
        }

        var swiperBox = document.createElement("div");
        swiperBox.className = "swiper swiper-container";
        var swiperWrapper = document.createElement("div");
        swiperWrapper.className = "swiper-wrapper";
        var originalCount = group.items.length;
        var canSlide = originalCount > perView;
        /* 무한 루프용으로 원본 슬라이드를 복제해 최소 장수 확보 */
        var slideItems = group.items.slice();
        if (canSlide && originalCount > 0) {
          var minSlides = Math.max(perView * 2 + 2, originalCount * 2);
          while (slideItems.length < minSlides) {
            for (var si = 0; si < originalCount && slideItems.length < minSlides; si++) {
              slideItems.push(group.items[si]);
            }
          }
        }
        slideItems.forEach(function (popup) {
          var slide = document.createElement("div");
          slide.className = "swiper-slide";
          slide.innerHTML = popupImageHtml(popup);
          swiperWrapper.appendChild(slide);
        });
        swiperBox.appendChild(swiperWrapper);

        var pagination = document.createElement("div");
        pagination.className = "swiper-pagination popup_swiper_pagination";
        var nextBtn = document.createElement("div");
        nextBtn.className = "swiper-button-next";
        var prevBtn = document.createElement("div");
        prevBtn.className = "swiper-button-prev";
        var foot = document.createElement("div");
        foot.className = "popup_foot";
        foot.innerHTML = footHtml();

        if (isDummy) {
          wrap.classList.add("is-dummy");
          card.classList.add("is-dummy");
        }

        var embed = listLayout && !isDummy;
        if (embed) card.classList.add("is-embed");

        var stage = document.createElement("div");
        stage.className = "popup_stage";
        stage.appendChild(swiperBox);
        if (embed && canSlide) {
          stage.appendChild(prevBtn);
          stage.appendChild(nextBtn);
        }
        card.appendChild(stage);
        var names = null;
        if (group.show_names && group.items.length) {
          names = document.createElement("div");
          names.className = "popup_names";
          group.items.forEach(function (popup, i) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "popup_name" + (i === 0 ? " is-active" : "");
            btn.setAttribute("data-slide", String(i));
            btn.textContent = popup.title || "제목 없음";
            names.appendChild(btn);
          });
          layoutNameRow(names, isDummy ? 7 : 3);
          if (isDummy) card.insertBefore(names, stage);
          else card.appendChild(names);
        }
        card.appendChild(foot);
        var stack = document.createElement("div");
        stack.className = "popup_slide_stack";
        var showPager = canSlide && !names;
        if (showPager) stage.appendChild(pagination);
        stack.appendChild(card);
        if (!embed && canSlide) wrap.appendChild(prevBtn);
        wrap.appendChild(stack);
        if (!embed && canSlide) wrap.appendChild(nextBtn);

        bindUnitClose(wrap, group.items.map(function (p) { return p.uniqueId; }));

        whenSwiperReady(function () {
          if (wrap.getAttribute("data-closed")) return;
          try {
            var dummyBreaks = {
              0: { slidesPerView: 1, slidesPerGroup: 1, spaceBetween: 10 }
            };
            if (perView >= 2) {
              dummyBreaks[900] = { slidesPerView: 2, slidesPerGroup: 1, spaceBetween: gap };
            }
            if (perView >= 3) {
              dummyBreaks[1280] = { slidesPerView: 3, slidesPerGroup: 1, spaceBetween: gap };
            }
            var swiper = new Swiper(swiperBox, {
              slidesPerView: perView,
              slidesPerGroup: 1,
              slidesPerGroupAuto: false,
              spaceBetween: gap,
              loop: canSlide,
              rewind: false,
              loopAddBlankSlides: false,
              autoHeight: true,
              speed: 400,
              simulateTouch: true,
              allowTouchMove: true,
              grabCursor: true,
              touchEventsTarget: "container",
              observer: true,
              observeParents: true,
              watchOverflow: true,
              updateOnWindowResize: true,
              autoplay: group.autoplay && canSlide ? { delay: parseInt(group.autoplay_ms, 10) || autoplayMs, disableOnInteraction: false } : false,
              navigation: {
                nextEl: nextBtn,
                prevEl: prevBtn,
              },
              pagination: showPager ? {
                el: pagination,
                clickable: true,
                type: "custom",
                renderCustom: function (sw) {
                  var cur = (sw.realIndex % originalCount) + 1;
                  return '<span class="swiper-pagination-current">' + cur + '</span> / <span class="swiper-pagination-total">' + originalCount + "</span>";
                },
              } : false,
              breakpoints: isDummy ? dummyBreaks : {
                0: { slidesPerView: 1, slidesPerGroup: 1, spaceBetween: 0 },
              },
            });
            wrap._popupSwiper = swiper;
            function syncSlideNav() {
              var show = !swiper.isLocked;
              var display = show ? "" : "none";
              prevBtn.style.display = display;
              nextBtn.style.display = display;
              if (showPager) pagination.style.display = show ? "" : "none";
            }
            swiper.on("slideChange", syncSlideNav);
            swiper.on("resize", syncSlideNav);
            swiper.on("breakpoint", syncSlideNav);
            swiper.on("update", syncSlideNav);
            requestAnimationFrame(syncSlideNav);
            swiperBox.addEventListener("dragstart", function (e) { e.preventDefault(); });
            function refreshSlideHeight() {
              if (swiper.destroyed) return;
              try { swiper.updateAutoHeight(0); } catch (e1) {}
              try { swiper.update(); } catch (e2) {}
              syncSlideNav();
              if (listLayout) syncFlowWrap();
            }
            swiperBox.querySelectorAll("img").forEach(function (img) {
              if (img.complete) return;
              img.addEventListener("load", refreshSlideHeight);
              img.addEventListener("error", refreshSlideHeight);
            });
            requestAnimationFrame(refreshSlideHeight);
            if (names) {
              function syncNames() {
                var idx = (swiper.realIndex % originalCount + originalCount) % originalCount;
                names.querySelectorAll(".popup_name").forEach(function (btn, i) {
                  var slideIdx = parseInt(btn.getAttribute("data-slide"), 10);
                  btn.classList.toggle("is-active", slideIdx === idx);
                });
              }
              names.addEventListener("click", function (e) {
                var btn = e.target.closest(".popup_name");
                if (!btn) return;
                var i = parseInt(btn.getAttribute("data-slide"), 10);
                if (isNaN(i)) return;
                if (swiper.params.loop && typeof swiper.slideToLoop === "function") swiper.slideToLoop(i);
                else swiper.slideTo(i);
              });
              swiper.on("slideChange", syncNames);
              syncNames();
            }
          } catch (err) {
            console.error("팝업 슬라이더 초기화 실패:", err);
          }
        });

        return wrap;
      }

      var columns = { left: null, center: null, right: null };
      if (useCloseAll) {
        scrollPane = document.createElement("div");
        scrollPane.className = "popup_scroll";
        overlay.appendChild(scrollPane);
      }
      function col(position) {
        if (!columns[position]) {
          columns[position] = document.createElement("div");
          columns[position].className = "popup_col is-" + position;
          (scrollPane || overlay).appendChild(columns[position]);
        }
        return columns[position];
      }

      groups.forEach(function (group) {
        var slot = col(group.position || "center");
        if (group.type === "list") {
          var row = document.createElement("div");
          row.className = "popup_group is-list";
          group.items.forEach(function (popup) {
            var card = makeCard(popup);
            markFlow(card);
            row.appendChild(card);
          });
          slot.appendChild(row);
        } else {
          var slide = makeSlideGroup(group);
          var n = markFlow(slide);
          var slideCard = slide.querySelector(".popup_wrap");
          if (slideCard) markFlow(slideCard, n);
          var slideStack = slide.querySelector(".popup_slide_stack");
          if (slideStack) markFlow(slideStack, n);
          slot.appendChild(slide);
        }
      });

      if (useCloseAll) {
        var allIds = [];
        groups.forEach(function (group) {
          group.items.forEach(function (popup) {
            if (popup.uniqueId) allIds.push(popup.uniqueId);
          });
        });
        attachCloseAll(allIds);
      }

      overlay.addEventListener("click", function (e) {
        if ((listLayout && !useCloseAll) || e.target !== overlay) return;
        overlay.style.display = "none";
        visibleUnits = 0;
        stopSliders(overlay);
        if (useCloseAll) unlockPageScroll();
      });

      var pageWrap = document.querySelector(".wrap");
      if (pageWrap) {
        if (window.getComputedStyle(pageWrap).position === "static") {
          pageWrap.style.position = "relative";
        }
        pageWrap.insertBefore(overlay, pageWrap.firstChild);
      } else {
        document.body.appendChild(overlay);
      }

      if (listLayout) {
        syncHeaderOffset();
        requestAnimationFrame(syncHeaderOffset);
        window.addEventListener("resize", syncHeaderOffset);
      }

      if (useCloseAll) {
        lockPageScroll();
        overlay.addEventListener("wheel", function (e) {
          var box = scrollBox();
          if (!overlay.classList.contains("is-scrollable")) {
            e.preventDefault();
            return;
          }
          if (e.target !== box && !box.contains(e.target)) {
            e.preventDefault();
            return;
          }
          var max = box.scrollHeight - box.clientHeight;
          if ((e.deltaY < 0 && box.scrollTop <= 0) || (e.deltaY > 0 && box.scrollTop >= max - 1)) {
            e.preventDefault();
          }
        }, { passive: false });
        overlay.addEventListener("touchmove", function (e) {
          var box = scrollBox();
          if (!overlay.classList.contains("is-scrollable") || (e.target !== box && !box.contains(e.target))) {
            e.preventDefault();
          }
        }, { passive: false });
        requestAnimationFrame(function () {
          syncOverlayScroll();
          requestAnimationFrame(syncOverlayScroll);
        });
      }
      if (listLayout) {
        syncFlowWrap();
        if (window.ResizeObserver) {
          var flowObs = new ResizeObserver(function () {
            syncFlowWrap();
            syncOverlayScroll();
          });
          flowObs.observe(overlay);
          if (scrollPane) flowObs.observe(scrollPane);
        }
        window.addEventListener("resize", function () {
          syncFlowWrap();
          syncOverlayScroll();
        });
      }
    })
    .catch(function (error) {
      console.error("팝업 데이터를 로드하는데 실패했습니다.", error);
    });
})();
