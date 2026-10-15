(function () {
  var root = document.getElementById("non-benefit-popup-cont");
  if (!root) return;

  function esc(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function cellVal(item, key) {
    return String(item && item[key] != null ? item[key] : "").trim();
  }

  /** 빈 mid/sub는 이전 행 값을 이어받아 rowspan 계산 */
  function fillForward(items, keys) {
    var last = {};
    return items.map(function (item) {
      var copy = {};
      Object.keys(item || {}).forEach(function (k) {
        copy[k] = item[k];
      });
      keys.forEach(function (k) {
        var v = cellVal(copy, k);
        if (v) last[k] = v;
        else if (last[k]) copy[k] = last[k];
      });
      return copy;
    });
  }

  function countRun(items, start, keys) {
    for (var k = 0; k < keys.length; k++) {
      if (!cellVal(items[start], keys[k])) return 1;
    }
    var n = 1;
    for (var i = start + 1; i < items.length; i++) {
      var same = true;
      for (var j = 0; j < keys.length; j++) {
        if (cellVal(items[start], keys[j]) !== cellVal(items[i], keys[j])) {
          same = false;
          break;
        }
      }
      if (!same) break;
      n++;
    }
    return n;
  }

  function td(value) {
    return "<td>" + esc(value) + "</td>";
  }

  function hasContent(item) {
    if (!item) return false;
    var keys = Object.keys(item);
    for (var i = 0; i < keys.length; i++) {
      if (String(item[keys[i]] == null ? "" : item[keys[i]]).trim()) return true;
    }
    return false;
  }

  function usableItems(items) {
    if (!items || !items.length) return [];
    return items.filter(hasContent);
  }

  function renderProcedure(items) {
    items = fillForward(items, ["mid", "sub"]);
    var rows = "";
    var skipMid = 0;
    var skipSub = 0;
    items.forEach(function (item, i) {
      rows += "<tr>";
      if (skipMid === 0) {
        skipMid = countRun(items, i, ["mid"]);
        rows += '<td rowspan="' + skipMid + '">' + esc(item.mid) + "</td>";
      }
      skipMid -= 1;
      if (skipSub === 0) {
        skipSub = countRun(items, i, ["mid", "sub"]);
        rows += '<td rowspan="' + skipSub + '">' + esc(item.sub) + "</td>";
      }
      skipSub -= 1;
      rows +=
        td(item.code) +
        td(item.name) +
        td(item.type) +
        td(item.price) +
        td(item.min) +
        td(item.max) +
        td(item.material) +
        td(item.drug) +
        td(item.note) +
        td(item.updated) +
        "</tr>";
    });
    return (
      '<div class="site_tit">행위료</div>' +
      '<div class="table_sty03 m_scroll"><table>' +
      "<caption>비급여 항목별 진료비용 - 행위료</caption>" +
      '<thead><tr><th scope="col" rowspan="2">중분류</th><th scope="col" rowspan="2">소분류</th>' +
      '<th scope="colgroup" colspan="2">항목</th><th scope="colgroup" colspan="6">진료비용 등(단위:원)</th>' +
      '<th scope="col" rowspan="2">특이<br />사항</th><th scope="col" rowspan="2">최종<br />변경일</th></tr>' +
      '<tr><th scope="col">코드</th><th scope="col">명칭</th><th scope="col">구분</th><th scope="col">비용</th>' +
      '<th scope="col">최저<br />비용</th><th scope="col">최고<br />비용</th>' +
      '<th scope="col">치료재료대<br />포함여부</th><th scope="col">약제비<br />포함여부</th></tr></thead>' +
      "<tbody>" +
      rows +
      "</tbody></table></div>"
    );
  }

  function renderMaterial(items) {
    items = fillForward(items, ["mid"]);
    var rows = "";
    var skipMid = 0;
    items.forEach(function (item, i) {
      rows += "<tr>";
      if (skipMid === 0) {
        skipMid = countRun(items, i, ["mid"]);
        rows += '<td rowspan="' + skipMid + '">' + esc(item.mid) + "</td>";
      }
      skipMid -= 1;
      rows +=
        td(item.code) +
        td(item.name) +
        td(item.price) +
        td(item.note) +
        td(item.updated) +
        "</tr>";
    });
    return (
      '<div class="site_tit">치료재료대</div>' +
      '<div class="table_sty03 m_scroll"><table>' +
      "<caption>비급여 항목별 진료비용 - 치료재료대</caption>" +
      '<thead><tr><th scope="col" rowspan="2">중분류</th><th scope="colgroup" colspan="2">항목</th>' +
      '<th scope="colgroup" colspan="1">진료비용 등(단위:원)</th>' +
      '<th scope="col" rowspan="2">특이사항</th><th scope="col" rowspan="2">최종변경일</th></tr>' +
      '<tr><th scope="col">코드</th><th scope="col">명칭</th><th scope="col">비용</th></tr></thead>' +
      "<tbody>" +
      rows +
      "</tbody></table></div>"
    );
  }

  function drugPrice(item) {
    var price = cellVal(item, "price");
    if (price) return price;
    var min = cellVal(item, "min");
    var max = cellVal(item, "max");
    if (min && max && min !== max) return min + " ~ " + max;
    return min || max;
  }

  function renderDrug(items) {
    var rows = items
      .map(function (item) {
        return (
          "<tr>" +
          td(item.code) +
          td(item.name) +
          td(item.type) +
          td(drugPrice(item)) +
          td(item.note) +
          td(item.updated) +
          "</tr>"
        );
      })
      .join("");
    return (
      '<div class="site_tit">약제비</div>' +
      '<div class="table_sty03 m_scroll"><table>' +
      "<caption>비급여 항목별 진료비용 - 약제비</caption>" +
      '<thead><tr><th scope="colgroup" colspan="2">항목</th><th scope="colgroup" colspan="2">진료비용 등(단위:원)</th>' +
      '<th scope="col" rowspan="2">특이사항</th><th scope="col" rowspan="2">최종변경일</th></tr>' +
      '<tr><th scope="col">코드</th><th scope="col">명칭</th><th scope="col">구분</th><th scope="col">비용</th></tr></thead>' +
      "<tbody>" +
      rows +
      "</tbody></table></div>"
    );
  }

  function renderCertificate(items) {
    var rows = items
      .map(function (item) {
        var type = esc(item.type).replace(/\n/g, "<br />");
        return (
          "<tr>" +
          td(item.code) +
          td(item.name) +
          "<td>" +
          type +
          "</td>" +
          td(item.price) +
          td(item.note) +
          td(item.updated) +
          "</tr>"
        );
      })
      .join("");
    return (
      '<div class="site_tit">제증명</div>' +
      '<div class="table_sty03 m_scroll"><table>' +
      "<caption>비급여 항목별 진료비용 - 제증명 수수료</caption>" +
      '<thead><tr><th scope="colgroup" colspan="2">항목</th><th scope="colgroup" colspan="2">진료비용 등(단위:원)</th>' +
      '<th scope="col" rowspan="2">특이사항</th><th scope="col" rowspan="2">최종변경일</th></tr>' +
      '<tr><th scope="col">코드</th><th scope="col">명칭</th><th scope="col">구분</th><th scope="col">비용</th></tr></thead>' +
      "<tbody>" +
      rows +
      "</tbody></table></div>"
    );
  }

  function stripHtml(html) {
    var tmp = document.createElement("div");
    tmp.innerHTML = html || "";
    return (tmp.textContent || "").replace(/\s+/g, " ").trim();
  }

  function sectionColCount(table) {
    var row = table.tHead && table.tHead.rows[0];
    if (!row) return 0;
    var n = 0;
    for (var i = 0; i < row.cells.length; i++) n += row.cells[i].colSpan || 1;
    return n;
  }

  function walkCells(section, fn) {
    if (!section) return;
    var occupy = [];
    for (var r = 0; r < section.rows.length; r++) {
      occupy[r] = occupy[r] || [];
      var col = 0;
      var cells = section.rows[r].cells;
      for (var i = 0; i < cells.length; i++) {
        while (occupy[r][col]) col += 1;
        var cell = cells[i];
        var rs = cell.rowSpan || 1;
        var cs = cell.colSpan || 1;
        fn(cell, col, cs);
        for (var cc = 0; cc < cs; cc++) {
          for (var rr = 0; rr < rs; rr++) {
            occupy[r + rr] = occupy[r + rr] || [];
            occupy[r + rr][col + cc] = true;
          }
        }
        col += cs;
      }
    }
  }

  function fitFrontTables(root) {
    var measure = document.createElement("span");
    measure.style.cssText =
      "position:absolute;left:-9999px;top:0;visibility:hidden;white-space:nowrap;font:400 14px 'Noto Sans KR',sans-serif;padding:0 10px;";
    document.body.appendChild(measure);

    root.querySelectorAll(".table_sty03 table").forEach(function (table) {
      var colCount = sectionColCount(table);
      if (!colCount) return;
      var maxW = [];
      var i;
      for (i = 0; i < colCount; i++) maxW[i] = 40;

      function measureCell(cell, startCol, span) {
        var cs = window.getComputedStyle(cell);
        measure.style.font = cs.font;
        measure.style.letterSpacing = cs.letterSpacing;
        var html = cell.innerHTML || "";
        var lines = html.split(/<br\s*\/?>/i);
        var widest = 40;
        lines.forEach(function (line) {
          measure.textContent = stripHtml(line) || " ";
          widest = Math.max(widest, measure.offsetWidth);
        });
        var per = Math.ceil(widest / (span || 1));
        for (var c = 0; c < span; c++) {
          maxW[startCol + c] = Math.max(maxW[startCol + c] || 40, per);
        }
      }

      walkCells(table.tHead, measureCell);
      walkCells(table.tBodies[0], measureCell);

      var avail = (table.parentNode && table.parentNode.clientWidth) || table.offsetWidth;
      var sum = maxW.reduce(function (a, b) { return a + b; }, 0);
      var scale = avail > 0 && sum > avail ? avail / sum : 1;

      var old = table.querySelector("colgroup");
      if (old) old.remove();
      var group = document.createElement("colgroup");
      maxW.forEach(function (w) {
        var col = document.createElement("col");
        col.style.width = Math.max(36, Math.floor(w * scale)) + "px";
        group.appendChild(col);
      });
      table.insertBefore(group, table.firstChild);
      table.style.tableLayout = scale < 1 ? "fixed" : "auto";
      table.style.width = "100%";
    });

    measure.remove();
  }

  function formatBaseDate(value) {
    var m = String(value || "").match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (!m) return String(value || "").trim();
    return m[1] + "년 " + Number(m[2]) + "월 " + Number(m[3]) + "일";
  }

  function render(data) {
    if (!data || !data.sections) return;
    var dateEl = document.getElementById("nb-base-date");
    var dateText = cellVal(data, "base_date");
    var sections = data.sections;
    var html = "";

    if (dateEl) {
      var formatted = formatBaseDate(dateText);
      if (formatted) dateEl.textContent = formatted;
    } else {
      html =
        '<ul class="noti_txt">' +
        "<li>&middot; 의료법 제 45조 및 동법 시행규칙 제 42조의 2 에 의하여 비급여 진료비용을 고지합니다.</li>" +
        "<li>&middot; 기준일 : " +
        esc(dateText) +
        "</li></ul>";
    }

    var procedureItems = usableItems(sections.procedure && sections.procedure.items);
    if (procedureItems.length) html += renderProcedure(procedureItems);

    var materialItems = usableItems(sections.material && sections.material.items);
    if (materialItems.length) html += renderMaterial(materialItems);

    var drugItems = usableItems(sections.drug && sections.drug.items);
    if (drugItems.length) html += renderDrug(drugItems);

    var certItems = usableItems(sections.certificate && sections.certificate.items);
    if (certItems.length) html += renderCertificate(certItems);

    root.innerHTML = html;
    if (!dateEl) fitFrontTables(root);
  }

  var scriptEl = document.currentScript;
  var dataUrl = "x_system/nonbenefit.json";
  if (scriptEl && scriptEl.src) {
    dataUrl = scriptEl.src.replace(/[^/]+$/, "") + "nonbenefit.json";
  }

  fetch(dataUrl, { cache: "no-cache" })
    .then(function (res) {
      if (!res.ok) throw new Error("load failed");
      return res.json();
    })
    .then(function (data) {
      render(data);
      var resizeTimer;
      window.addEventListener("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
          if (!document.getElementById("nb-base-date") && root.querySelector(".table_sty03 table")) fitFrontTables(root);
        }, 120);
      });
    })
    .catch(function () {});
})();
