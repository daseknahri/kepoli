/**
 * WP Automator Pro — admin-script.js
 * Dynamic Grid + Language Selector + Distribution Hub
 */
(function ($) {
  "use strict";

  var WPAP_DATA = window.WPAP || {};
  var AJAX  = WPAP_DATA.ajax_url || window.ajaxurl || "";
  var NONCE = WPAP_DATA.nonce || "";

  /* Keep the AJAX nonce fresh so long overnight batches don't 403 when the
     original wpap_nonce ages out mid-run. The server returns a new nonce on
     each successful generate/publish response; adopt it for every later call.
     Only overwrite when a non-empty nonce is actually present. */
  function applyFreshNonce(res) {
    var n = res && res.data && res.data.nonce;
    if (n) { NONCE = n; if (window.WPAP) { window.WPAP.nonce = n; } }
  }

  /* State */
  var isProcessing   = false;
  var stopFlag       = false;
  var gridQueue      = [];
  var queueIndex     = 0;
  var results        = [];
  var gridRowCounter = 0;
  var fbTextsStore   = {};
  var currentPage    = 1;
  var currentSearch  = "";
  var currentStatus  = "all";   /* Distribution Hub status filter */
  var currentPerPage = 10;      /* Distribution Hub rows per page */
  var lastRows       = [];   /* the exact rows currently rendered in the Distribution Hub table */

  /* BOOT */
  $(function () {
    try {
      renderShell();
      loadTable(1);
      loadDashboardStats();
    } catch (err) {
      console.error(err);
      var app = document.getElementById("wpap-app");
      if (app) {
        app.innerHTML = '<div style="padding:24px;background:#111827;color:#fca5a5;border:1px solid #7f1d1d;border-radius:12px;font-family:sans-serif;">WP Automator failed to load. Please check the browser console.</div>';
      }
    }
  });

  /* ─────────────────────────────────────────
     SHELL
  ───────────────────────────────────────── */
  function renderShell() {
    document.getElementById("wpap-app").innerHTML =
      '<div class="wpap-wrap">' +

      '<header class="wpap-header">' +
        '<div class="wpap-header-inner">' +
          '<div class="wpap-logo"><span class="wpap-logo-icon">⚡</span><span class="wpap-logo-text">WP Automator <span>Pro</span></span></div>' +
          '<nav class="wpap-header-nav">' +
            '<a href="#bulk-section" class="wpap-nav-link active" data-tab="bulk">Bulk Generator</a>' +
            '<a href="#publish-section" class="wpap-nav-link" data-tab="publish">📦 Direct Publish</a>' +
            '<a href="#dist-section" class="wpap-nav-link" data-tab="dist">Distribution Hub</a>' +
            '<a href="?page=wp-automator-pro-settings" class="wpap-nav-link">⚙ Settings</a>' +
          '</nav>' +
        '</div>' +
      '</header>' +

      '<div id="wpap-stat-strip" class="wpap-stat-strip" style="display:none"></div>' +

      '<section id="bulk-section" class="wpap-section">' +
        '<div class="wpap-card">' +
          '<div class="wpap-card-header">' +
            '<h2 class="wpap-card-title">🚀 Bulk Content Generator</h2>' +
            '<p class="wpap-card-sub">Add titles row by row — each becomes a full SEO post with AI image + Facebook share.</p>' +
          '</div>' +
          '<div class="wpap-card-body">' +

            '<div class="wpap-grid-toolbar">' +
              '<button id="btn-add-row"    class="wpap-btn wpap-btn-outline wpap-btn-sm">＋ Add Row</button>' +
              '<button id="btn-bulk-paste" class="wpap-btn wpap-btn-outline wpap-btn-sm">📋 Bulk Import Rows</button>' +
              '<button id="btn-bulk-images" class="wpap-btn wpap-btn-outline wpap-btn-sm">🖼 Bulk Image URLs</button>' +
              '<button id="btn-clear-all"  class="wpap-btn wpap-btn-ghost  wpap-btn-sm">🗑 Clear All</button>' +
              '<div class="wpap-lang-wrap">' +
                '<span class="wpap-lang-globe">🌐</span>' +
                '<select id="wpap-lang-select" class="wpap-lang-select" title="Target Language">' +
                  '<option value="auto">Auto-Detect</option>' +
                  '<optgroup label="── Europe ──────────">' +
                    '<option value="en">🇬🇧 English</option>' +
                    '<option value="fr">🇫🇷 French</option>' +
                    '<option value="es">🇪🇸 Spanish</option>' +
                    '<option value="de">🇩🇪 German</option>' +
                    '<option value="it">🇮🇹 Italian</option>' +
                    '<option value="pt">🇵🇹 Portuguese</option>' +
                    '<option value="nl">🇳🇱 Dutch</option>' +
                    '<option value="pl">🇵🇱 Polish</option>' +
                    '<option value="ro">🇷🇴 Romanian</option>' +
                    '<option value="hu">🇭🇺 Hungarian</option>' +
                    '<option value="bg">🇧🇬 Bulgarian</option>' +
                    '<option value="cs">🇨🇿 Czech</option>' +
                    '<option value="sk">🇸🇰 Slovak</option>' +
                    '<option value="hr">🇭🇷 Croatian</option>' +
                    '<option value="sv">🇸🇪 Swedish</option>' +
                    '<option value="da">🇩🇰 Danish</option>' +
                    '<option value="fi">🇫🇮 Finnish</option>' +
                    '<option value="el">🇬🇷 Greek</option>' +
                    '<option value="ru">🇷🇺 Russian</option>' +
                    '<option value="uk">🇺🇦 Ukrainian</option>' +
                    '<option value="tr">🇹🇷 Turkish</option>' +
                  '</optgroup>' +
                  '<optgroup label="── Middle East ─────">' +
                    '<option value="ar">🇸🇦 Arabic</option>' +
                    '<option value="he">🇮🇱 Hebrew</option>' +
                    '<option value="fa">🇮🇷 Persian</option>' +
                  '</optgroup>' +
                  '<optgroup label="── Asia ────────────">' +
                    '<option value="zh">🇨🇳 Chinese</option>' +
                    '<option value="ja">🇯🇵 Japanese</option>' +
                    '<option value="ko">🇰🇷 Korean</option>' +
                    '<option value="hi">🇮🇳 Hindi</option>' +
                    '<option value="id">🇮🇩 Indonesian</option>' +
                    '<option value="vi">🇻🇳 Vietnamese</option>' +
                    '<option value="th">🇹🇭 Thai</option>' +
                  '</optgroup>' +
                '</select>' +
              '</div>' +
              '<div class="wpap-ctrl-wrap">' +
              '<span class="wpap-ctrl-label">📄 Pages:</span>' +
              '<select id="wpap-pages-select" class="wpap-ctrl-select" title="Number of pages">' +
                '<option value="2">2 Pages</option>' +
                '<option value="3">3 Pages</option>' +
                '<option value="4">4 Pages</option>' +
                '<option value="5">5 Pages</option>' +
              '</select>' +
            '</div>' +
            '<div class="wpap-ctrl-wrap">' +
              '<span class="wpap-ctrl-label">🎨 Image:</span>' +
              '<select id="wpap-img-engine-select" class="wpap-ctrl-select" title="Image Engine">' +
                '<option value="gemini_flash">Gemini Free (Flash)</option>' +
                '<option value="gemini_pro">Gemini Pro (Paid)</option>' +
                '<option value="claude">Claude (Premium)</option>' +
                '<option value="pexels">Pexels (Unlimited)</option>' +
                '<option value="manual_only">Manual Only</option>' +
              '</select>' +
            '</div>' +
            '<div class="wpap-ctrl-wrap">' +
              '<span class="wpap-ctrl-label">✍️ Content:</span>' +
              '<select id="wpap-content-engine-select" class="wpap-ctrl-select" title="Content Engine">' +
                '<option value="claude_haiku">Claude Budget (Haiku)</option>' +
                '<option value="gemini_flash">Gemini Free (Flash)</option>' +
                '<option value="gemini_pro">Gemini Pro (Paid)</option>' +
              '</select>' +
            '</div>' +
            '<div class="wpap-ctrl-wrap">' +
              '<span class="wpap-ctrl-label">🕒 Schedule:</span>' +
              '<select id="wpap-schedule-select" class="wpap-ctrl-select" title="Auto-schedule to a random time in the next hours">' +
                '<option value="0">Publish now</option>' +
                '<option value="1">Random ≤ 1h</option>' +
                '<option value="3">Random ≤ 3h</option>' +
                '<option value="6" selected>Random ≤ 6h</option>' +
                '<option value="12">Random ≤ 12h</option>' +
                '<option value="24">Random ≤ 24h</option>' +
                '<option value="48">Random ≤ 48h</option>' +
              '</select>' +
            '</div>' +
'<span class="wpap-row-count" id="wpap-row-count">0 rows</span>' +
            '</div>' +

            '<div class="wpap-grid-wrap">' +
              '<table class="wpap-grid-table">' +
                '<thead><tr>' +
                  '<th class="gc-num">#</th>' +
                  '<th class="gc-title">Post Title</th>' +
                  '<th class="gc-img">Image <small>(optional)</small></th>' +
                  '<th class="gc-status">Status</th>' +
                  '<th class="gc-del"></th>' +
                '</tr></thead>' +
                '<tbody id="wpap-grid-body"></tbody>' +
              '</table>' +
              '<button id="btn-add-row-bottom" class="wpap-grid-add-bottom">＋ Add Row</button>' +
            '</div>' +

            '<div id="wpap-paste-modal" class="wpap-modal-overlay" style="display:none">' +
              '<div class="wpap-modal-box">' +
                '<h3>📋 Bulk Import Rows</h3>' +
                '<p>Paste JSON like <code>[{"title":"Hello","imageUrl":"https://..."},{"title":"World","imageUrl":"https://..."}]</code> or one title per line. For line input, use <code>Title | Image URL</code> when you want to pair both in one pass.</p>' +
                '<textarea id="wpap-paste-area" rows="12" class="wpap-modal-ta" placeholder="[{&quot;title&quot;:&quot;Hello&quot;,&quot;imageUrl&quot;:&quot;https://...&quot;}]"></textarea>' +
                '<div class="wpap-modal-actions">' +
                  '<button id="btn-paste-ok"     class="wpap-btn wpap-btn-primary">✅ Import Rows</button>' +
                  '<button id="btn-paste-cancel" class="wpap-btn wpap-btn-ghost">Cancel</button>' +
                '</div>' +
              '</div>' +
            '</div>' +

            '<div id="wpap-images-modal" class="wpap-modal-overlay" style="display:none">' +
              '<div class="wpap-modal-box">' +
                '<h3>🖼 Bulk Import Image URLs</h3>' +
                '<p>Paste one original image URL per line. Each image is downloaded into Media Library and added as a row with a blank title so the AI can generate it later.</p>' +
                '<textarea id="wpap-images-area" rows="12" class="wpap-modal-ta" placeholder="https://example.com/image-1.jpg&#10;https://example.com/image-2.jpg&#10;..."></textarea>' +
                '<div class="wpap-modal-actions">' +
                  '<button id="btn-images-ok" class="wpap-btn wpap-btn-primary">✅ Import Images</button>' +
                  '<button id="btn-images-cancel" class="wpap-btn wpap-btn-ghost">Cancel</button>' +
                '</div>' +
              '</div>' +
            '</div>' +

            '<div class="wpap-actions">' +
              '<button id="wpap-start-btn" class="wpap-btn wpap-btn-primary"><span class="btn-icon">▶</span> Start Processing</button>' +
              '<button id="wpap-stop-btn"  class="wpap-btn wpap-btn-danger" style="display:none"><span class="btn-icon">■</span> Stop Queue</button>' +
              '<button id="wpap-retry-btn" class="wpap-btn wpap-btn-outline" style="display:none"><span class="btn-icon">↻</span> Retry Failed</button>' +
              '<div id="wpap-queue-stats" class="wpap-stats" style="display:none">' +
                '<span id="wpap-stat-done" class="stat-done">0</span> done &nbsp;·&nbsp;' +
                '<span id="wpap-stat-left" class="stat-left">0</span> remaining &nbsp;·&nbsp;' +
                '<span id="wpap-stat-err"  class="stat-err">0</span> errors' +
              '</div>' +
            '</div>' +

            '<div id="wpap-progress-wrap" class="wpap-progress-wrap" style="display:none">' +
              '<div id="wpap-progress-bar" class="wpap-progress-bar"></div>' +
            '</div>' +

            '<div id="wpap-log" class="wpap-log" style="display:none">' +
              '<div class="wpap-log-header"><span>📋 Live Log</span><button onclick="document.getElementById(\'wpap-log-body\').innerHTML=\'\'" class="wpap-log-clear">Clear</button></div>' +
              '<div class="wpap-log-body" id="wpap-log-body"></div>' +
            '</div>' +

          '</div></div>' +
        '</section>' +

        '<section id="publish-section" class="wpap-section" style="display:none">' +
          '<div class="wpap-card">' +
            '<div class="wpap-card-header">' +
              '<h2 class="wpap-card-title">📦 Direct Publish <span>(No AI)</span></h2>' +
              '<p class="wpap-card-sub">Paste a JSON list of ready-made posts and publish them straight to WordPress — no Claude/Gemini calls. They land in the Distribution Hub for export.</p>' +
            '</div>' +
            '<div class="wpap-card-body">' +
              '<p class="wpap-card-sub" style="margin-bottom:12px">Each item: <code>title</code>, <code>content</code> (post body — HTML allowed), <code>imageUrl</code> (featured image — optional), <code>hook</code> (Facebook caption — optional), <code>category</code>, <code>parts</code>. <strong>SEO (all optional, auto-filled if omitted):</strong> <code>metaDescription</code> (else auto-derived from the content), <code>metaTitle</code>, <code>focusKeyword</code>, <code>tags</code> (array or comma-separated). The meta description is written into your SEO plugin (Yoast / Rank Math) or emitted directly if you have none. Example:<br/>' +
              '<code>[{&quot;title&quot;:&quot;My Post&quot;,&quot;imageUrl&quot;:&quot;https://...&quot;,&quot;content&quot;:&quot;&lt;p&gt;Full article…&lt;/p&gt;&quot;,&quot;hook&quot;:&quot;Catchy caption&quot;}]</code></p>' +
              '<textarea id="wpap-publish-area" rows="16" class="wpap-modal-ta" placeholder="[{&quot;title&quot;:&quot;...&quot;,&quot;imageUrl&quot;:&quot;https://...&quot;,&quot;content&quot;:&quot;&lt;p&gt;...&lt;/p&gt;&quot;,&quot;hook&quot;:&quot;...&quot;}]"></textarea>' +
              '<div class="wpap-actions">' +
                '<button id="btn-publish-go" class="wpap-btn wpap-btn-primary"><span class="btn-icon">📦</span> Publish All</button>' +
                '<button id="btn-publish-file" class="wpap-btn wpap-btn-outline"><span class="btn-icon">📁</span> Load JSON file</button>' +
                '<input type="file" id="wpap-publish-file" accept=".json,application/json,text/plain" style="display:none" />' +
                '<button id="btn-publish-clear" class="wpap-btn wpap-btn-ghost">Clear</button>' +
                '<div class="wpap-ctrl-wrap">' +
                  '<span class="wpap-ctrl-label">📄 Split:</span>' +
                  '<select id="wpap-publish-parts" class="wpap-ctrl-select" title="Split each post into pages (single page is best for AdSense RPM)">' +
                    '<option value="1" selected>Single page</option>' +
                    '<option value="2">Split in 2</option>' +
                    '<option value="3">Split in 3</option>' +
                  '</select>' +
                '</div>' +
                '<div class="wpap-ctrl-wrap">' +
                  '<span class="wpap-ctrl-label">🕒 Schedule:</span>' +
                  '<select id="wpap-publish-schedule" class="wpap-ctrl-select" title="Spread the batch evenly over the next hours, publishing in the order you added them">' +
                    '<option value="0">Publish now (all at once)</option>' +
                    '<option value="1">Spread over 1h (in order)</option>' +
                    '<option value="3">Spread over 3h (in order)</option>' +
                    '<option value="6" selected>Spread over 6h (in order)</option>' +
                    '<option value="12">Spread over 12h (in order)</option>' +
                    '<option value="24">Spread over 24h (in order)</option>' +
                    '<option value="48">Spread over 48h (in order)</option>' +
                  '</select>' +
                '</div>' +
                '<span id="wpap-publish-stats" class="wpap-stats" style="display:none"></span>' +
              '</div>' +
              '<div id="wpap-publish-log" class="wpap-log" style="display:none">' +
                '<div class="wpap-log-header"><span>📋 Publish Log</span><button onclick="document.getElementById(\'wpap-publish-log-body\').innerHTML=\'\'" class="wpap-log-clear">Clear</button></div>' +
                '<div class="wpap-log-body" id="wpap-publish-log-body"></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</section>' +

        '<section id="dist-section" class="wpap-section" style="display:none">' +
          '<div class="wpap-card">' +
            '<div class="wpap-card-header">' +
              '<h2 class="wpap-card-title">📡 Distribution Hub</h2>' +
              '<p class="wpap-card-sub">All generated posts. Copy, share, and distribute with one click.</p>' +
            '</div>' +
            '<div class="wpap-card-body">' +
              '<div class="wpap-table-controls">' +
                '<div class="wpap-search-wrap"><span class="wpap-search-icon">🔍</span><input type="text" id="wpap-search" placeholder="Search articles…" class="wpap-search-input" /></div>' +
                '<div class="wpap-ctrl-wrap"><span class="wpap-ctrl-label">Status:</span>' +
                  '<select id="wpap-status-filter" class="wpap-ctrl-select" title="Filter by status">' +
                    '<option value="all">All</option>' +
                    '<option value="publish">Published</option>' +
                    '<option value="future">Scheduled</option>' +
                    '<option value="draft">Draft</option>' +
                  '</select>' +
                '</div>' +
                '<div class="wpap-ctrl-wrap"><span class="wpap-ctrl-label">Show:</span>' +
                  '<select id="wpap-perpage" class="wpap-ctrl-select" title="Rows per page — show more to flip fewer pages">' +
                    '<option value="10">10 / page</option>' +
                    '<option value="25">25 / page</option>' +
                    '<option value="50">50 / page</option>' +
                    '<option value="100">100 / page</option>' +
                  '</select>' +
                '</div>' +
                '<button id="wpap-bulk-import-btn" class="wpap-btn wpap-btn-primary wpap-btn-sm">📥 Bulk Import JSON</button>' +
                '<button id="wpap-export-json-btn" class="wpap-btn wpap-btn-outline wpap-btn-sm">📤 Export JSON</button>' +
                '<button id="wpap-cleanup-btn" class="wpap-btn wpap-btn-outline wpap-btn-sm" title="Remove Hub entries whose post was deleted or trashed">🧹 Clean Deleted</button>' +
                '<button id="wpap-delete-selected-btn" class="wpap-btn wpap-btn-danger wpap-btn-sm" style="display:none">🗑 Delete Selected (<span id="wpap-sel-count">0</span>)</button>' +
                '<button id="wpap-refresh-btn" class="wpap-btn wpap-btn-outline">↻ Refresh</button>' +
              '</div>' +
              '<div class="wpap-table-wrap">' +
                '<table class="wpap-table" id="wpap-dist-table">' +
                  '<thead><tr>' +
                    '<th style="width:34px"><input type="checkbox" id="wpap-select-all" title="Select all on this page" /></th>' +
                    '<th style="width:33%">Article Title</th>' +
                    '<th style="width:11%">Image</th>' +
                    '<th style="width:12%">Copy Image</th>' +
                    '<th style="width:12%">Download</th>' +
                    '<th style="width:12%">Copy Hook</th>' +
                    '<th style="width:16%">Smart Link</th>' +
                  '</tr></thead>' +
                  '<tbody id="wpap-dist-tbody"><tr><td colspan="7" class="wpap-table-loading">Loading…</td></tr></tbody>' +
                '</table>' +
              '</div>' +
              '<div id="wpap-pagination" class="wpap-pagination"></div>' +
            '</div>' +
          '</div>' +
        '</section>' +

            '<div id="wpap-import-modal" class="wpap-modal-overlay" style="display:none">' +
              '<div class="wpap-modal-box">' +
                '<h3>📥 Bulk Import Distribution Data</h3>' +
                '<p>Paste a JSON array like <code>[{"caption":"Hello","comment":"link","imageUrl":"https://..."}]</code>. <code>comment</code> is optional, and <code>title</code> is still accepted as a fallback.</p>' +
                '<textarea id="wpap-import-area" rows="14" class="wpap-modal-ta" placeholder="[{&quot;caption&quot;:&quot;Hello&quot;,&quot;comment&quot;:&quot;link&quot;,&quot;imageUrl&quot;:&quot;https://...&quot;}]"></textarea>' +
                '<div class="wpap-modal-actions">' +
                  '<button id="btn-import-ok" class="wpap-btn wpap-btn-primary">✅ Import Items</button>' +
                  '<button id="btn-import-cancel" class="wpap-btn wpap-btn-ghost">Cancel</button>' +
                '</div>' +
              '</div>' +
            '</div>' +

        '<div id="wpap-json-modal" class="wpap-modal-overlay" style="display:none">' +
          '<div class="wpap-modal-box">' +
            '<h3>📄 Distribution JSON</h3>' +
            '<p>Copy the <strong>current page</strong> (up to 10 posts) as JSON with <code>caption</code>, <code>comment</code>, and <code>imageUrl</code>.</p>' +
            '<textarea id="wpap-json-area" rows="14" class="wpap-modal-ta" readonly></textarea>' +
            '<div class="wpap-modal-actions">' +
              '<button id="btn-json-copy" class="wpap-btn wpap-btn-primary">📋 Copy JSON</button>' +
              '<button id="btn-json-close" class="wpap-btn wpap-btn-ghost">Close</button>' +
            '</div>' +
          '</div>' +
        '</div>' +

      '</div>' +
      '<div id="wpap-toast" class="wpap-toast" style="display:none"></div>';

    bindEvents();
    addRow(); addRow(); addRow();
    restoreGridDraft();   /* offer to bring back a hand-curated list after an accidental refresh */
  }

  /* ─────────────────────────────────────────
     NAVIGATION
  ───────────────────────────────────────── */
  function bindEvents() {
    document.querySelectorAll(".wpap-nav-link[data-tab]").forEach(function (link) {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        var tab = link.dataset.tab;
        document.querySelectorAll(".wpap-nav-link").forEach(function (l) { l.classList.remove("active"); });
        link.classList.add("active");
        document.getElementById("bulk-section").style.display = tab === "bulk" ? "" : "none";
        document.getElementById("publish-section").style.display = tab === "publish" ? "" : "none";
        document.getElementById("dist-section").style.display = tab === "dist" ? "" : "none";
        if (tab === "dist") loadTable(1);
      });
    });
    document.getElementById("btn-add-row").addEventListener("click", function () { addRow(); });
    document.getElementById("btn-add-row-bottom").addEventListener("click", function () {
      addRow();
      document.getElementById("btn-add-row-bottom").scrollIntoView({ behavior: "smooth" });
    });
    document.getElementById("btn-clear-all").addEventListener("click", function () {
      if (!confirm("Clear all rows?")) return;
      document.getElementById("wpap-grid-body").innerHTML = "";
      gridRowCounter = 0;
      addRow();
      updateRowCount();
      try { localStorage.removeItem("wpap_grid_draft"); } catch (e) {}
    });
    document.getElementById("btn-bulk-paste").addEventListener("click", function () {
      document.getElementById("wpap-paste-modal").style.display = "flex";
      document.getElementById("wpap-paste-area").focus();
    });
    document.getElementById("btn-bulk-images").addEventListener("click", function () {
      openImageModal();
    });
    document.getElementById("btn-paste-cancel").addEventListener("click", function () {
      document.getElementById("wpap-paste-modal").style.display = "none";
    });
    document.getElementById("btn-images-cancel").addEventListener("click", function () {
      closeImageModal();
    });
    document.getElementById("btn-paste-ok").addEventListener("click", function () {
      submitBulkGeneratorRows();
    });
    document.getElementById("btn-images-ok").addEventListener("click", function () {
      submitImageUrls();
    });
    document.getElementById("wpap-paste-modal").addEventListener("click", function (e) {
      if (e.target === this) this.style.display = "none";
    });
    document.getElementById("wpap-images-modal").addEventListener("click", function (e) {
      if (e.target === this) closeImageModal();
    });
    document.getElementById("btn-publish-go").addEventListener("click", submitBulkPublish);
    /* Load a .json file straight into the paste box (no need to open + copy). */
    var pubFileBtn = document.getElementById("btn-publish-file");
    var pubFileIn  = document.getElementById("wpap-publish-file");
    if (pubFileBtn && pubFileIn) {
      pubFileBtn.addEventListener("click", function () { pubFileIn.click(); });
      pubFileIn.addEventListener("change", function () {
        var f = pubFileIn.files && pubFileIn.files[0];
        if (!f) return;
        if (f.size > 8 * 1024 * 1024) { toast("That file is larger than 8 MB — open it and paste in smaller batches.", "warn"); pubFileIn.value = ""; return; }
        var reader = new FileReader();
        reader.onload = function () {
          var text = String(reader.result || "").trim();
          var ta = document.getElementById("wpap-publish-area");
          if (ta) ta.value = text;
          /* Quick sanity: parse + count items so the user sees it loaded. */
          var n = 0;
          try { var j = JSON.parse(text); n = Array.isArray(j) ? j.length : (j && Array.isArray(j.items) ? j.items.length : 1); }
          catch (e) { toast("Loaded, but the file isn't valid JSON — check it before publishing.", "warn"); pubFileIn.value = ""; return; }
          toast("Loaded " + n + " item(s) from " + f.name + ". Review, then click Publish All.", "success");
          pubFileIn.value = "";   /* allow re-selecting the same file later */
        };
        reader.onerror = function () { toast("Could not read that file.", "error"); pubFileIn.value = ""; };
        reader.readAsText(f);
      });
    }
    document.getElementById("btn-publish-clear").addEventListener("click", function () {
      document.getElementById("wpap-publish-area").value = "";
      var lw = document.getElementById("wpap-publish-log"); if (lw) lw.style.display = "none";
      var se = document.getElementById("wpap-publish-stats"); if (se) se.style.display = "none";
    });
    document.getElementById("wpap-start-btn").addEventListener("click", startQueue);
    document.getElementById("wpap-stop-btn").addEventListener("click", stopQueue);
    var retryBtn = document.getElementById("wpap-retry-btn");
    if (retryBtn) retryBtn.addEventListener("click", retryFailed);
    var gridBody = document.getElementById("wpap-grid-body");
    if (gridBody) {
      var draftTimer;
      gridBody.addEventListener("input", function () {
        clearTimeout(draftTimer);
        draftTimer = setTimeout(saveGridDraft, 600);
      });
    }
    var st;
    document.getElementById("wpap-search").addEventListener("input", function () {
      var v = this.value; clearTimeout(st);
      st = setTimeout(function () { loadTable(1, v); }, 400);
    });
    document.getElementById("wpap-refresh-btn").addEventListener("click", function () {
      loadTable(1, document.getElementById("wpap-search").value);
    });
    document.getElementById("wpap-bulk-import-btn").addEventListener("click", function () {
      openImportModal();
    });
    document.getElementById("btn-import-cancel").addEventListener("click", function () {
      closeImportModal();
    });
    document.getElementById("wpap-import-modal").addEventListener("click", function (e) {
      if (e.target === this) closeImportModal();
    });
    document.getElementById("btn-import-ok").addEventListener("click", function () {
      submitImportItems();
    });
    document.getElementById("wpap-export-json-btn").addEventListener("click", function () {
      loadDistributionJson();
    });
    var cleanupBtn = document.getElementById("wpap-cleanup-btn");
    if (cleanupBtn) cleanupBtn.addEventListener("click", cleanupDeletedRows);
    var statusFilter = document.getElementById("wpap-status-filter");
    if (statusFilter) statusFilter.addEventListener("change", function () { currentStatus = this.value; loadTable(1, currentSearch || ""); });
    var perPageSel = document.getElementById("wpap-perpage");
    if (perPageSel) perPageSel.addEventListener("change", function () { currentPerPage = parseInt(this.value, 10) || 10; loadTable(1, currentSearch || ""); });
    var selectAll = document.getElementById("wpap-select-all");
    if (selectAll) selectAll.addEventListener("change", function () {
      var checked = this.checked;
      document.querySelectorAll("#wpap-dist-tbody .wpap-row-cb").forEach(function (cb) { cb.checked = checked; });
      updateSelectedCount();
    });
    var distTbody = document.getElementById("wpap-dist-tbody");
    if (distTbody) distTbody.addEventListener("change", function (e) {
      if (e.target && e.target.classList && e.target.classList.contains("wpap-row-cb")) updateSelectedCount();
    });
    var delSelBtn = document.getElementById("wpap-delete-selected-btn");
    if (delSelBtn) delSelBtn.addEventListener("click", deleteSelected);
    document.getElementById("btn-json-close").addEventListener("click", function () {
      closeJsonModal();
    });
    document.getElementById("btn-json-copy").addEventListener("click", function () {
      copyJsonToClipboard();
    });
    document.getElementById("wpap-json-modal").addEventListener("click", function (e) {
      if (e.target === this) closeJsonModal();
    });
  }

  /* ─────────────────────────────────────────
     DYNAMIC GRID
  ───────────────────────────────────────── */
  function addRow(titleVal, imageData) {
    gridRowCounter++;
    var num = gridRowCounter;
    var tr  = document.createElement("tr");
    tr.className = "wpap-grid-row";
    tr.innerHTML =
      '<td class="gc-num">' + num + '</td>' +
      '<td class="gc-title"><input type="text" class="grid-title-input wpap-input-field" placeholder="Post title…" value="' + escHtml(titleVal || "") + '" /></td>' +
      '<td class="gc-img"><div class="grid-img-cell">' +
        '<label class="grid-img-pick" title="Choose image">📁<input type="file" class="grid-img-file" accept="image/*" style="display:none" /></label>' +
        '<span class="grid-img-name">No image</span>' +
        '<img class="grid-img-thumb" src="" alt="" style="display:none" />' +
        '<button class="grid-img-clear" style="display:none" title="Remove">✕</button>' +
        '<input type="hidden" class="grid-attach-id" value="0" />' +
      '</div></td>' +
      '<td class="gc-status"><span class="row-status row-idle">Pending</span></td>' +
      '<td class="gc-del"><button class="grid-run-btn" title="Run just this row">▶</button> <button class="grid-del-btn" title="Remove row">✕</button></td>';

    document.getElementById("wpap-grid-body").appendChild(tr);
    updateRowCount();

    var fileInput = tr.querySelector(".grid-img-file");
    var nameLbl   = tr.querySelector(".grid-img-name");
    var thumb     = tr.querySelector(".grid-img-thumb");
    var clearBtn  = tr.querySelector(".grid-img-clear");

    fileInput.addEventListener("change", function () {
      var file = this.files[0]; if (!file) return;
      nameLbl.textContent = "⏳ Uploading…";
      var fd = new FormData();
      fd.append("action", "wpap_upload_fallback");
      fd.append("nonce",  NONCE);
      fd.append("image",  file);
      $.ajax({ url: AJAX, type: "POST", data: fd, processData: false, contentType: false, timeout: 30000,
        success: function (res) {
          if (res.success) {
            applyRowImageState(tr, {
              attachId: res.data.attach_id,
              imageUrl: res.data.image_url,
              label: file.name.length > 18 ? file.name.substr(0, 16) + "…" : file.name
            });
            toast("✅ Image uploaded!", "success");
          } else { nameLbl.textContent = "❌ Failed"; toast("Upload failed: " + escHtml(res.data || ""), "error"); }
        },
        error: function () { nameLbl.textContent = "❌ Error"; toast("Upload error.", "error"); }
      });
    });
    clearBtn.addEventListener("click", function () {
      applyRowImageState(tr, { attachId: 0, imageUrl: "", label: "No image" });
      fileInput.value = "";
    });
    tr.querySelector(".grid-del-btn").addEventListener("click", function () { tr.remove(); renumberRows(); saveGridDraft(); });
    tr.querySelector(".grid-run-btn").addEventListener("click", function () { runSingleRow(tr); });

    if (imageData) {
      applyRowImageState(tr, imageData);
    }

    return tr;
  }

  function applyRowImageState(rowEl, imageData) {
    if (!rowEl) return;
    var attachId  = rowEl.querySelector(".grid-attach-id");
    var nameLbl   = rowEl.querySelector(".grid-img-name");
    var thumb     = rowEl.querySelector(".grid-img-thumb");
    var clearBtn  = rowEl.querySelector(".grid-img-clear");
    var fileInput = rowEl.querySelector(".grid-img-file");
    var nextAttachId = imageData && imageData.attachId ? parseInt(imageData.attachId, 10) || 0 : 0;
    var nextUrl      = imageData && imageData.imageUrl ? imageData.imageUrl : "";
    var nextLabel    = imageData && imageData.label ? imageData.label : (nextAttachId ? "Image attached" : "No image");

    if (attachId) attachId.value = nextAttachId ? String(nextAttachId) : "0";
    if (nameLbl) nameLbl.textContent = nextLabel;
    if (thumb) {
      if (nextUrl) {
        thumb.src = nextUrl;
        thumb.style.display = "";
      } else {
        thumb.src = "";
        thumb.style.display = "none";
      }
    }
    if (clearBtn) clearBtn.style.display = nextAttachId ? "" : "none";
    if (fileInput && nextAttachId) fileInput.value = "";
  }

  function removeBlankRows() {
    document.querySelectorAll("#wpap-grid-body .wpap-grid-row").forEach(function (tr) {
      var titleInput = tr.querySelector(".grid-title-input");
      var attachId   = tr.querySelector(".grid-attach-id");
      var hasTitle   = titleInput && titleInput.value.trim();
      var hasImage   = attachId && (parseInt(attachId.value, 10) || 0) > 0;
      if (!hasTitle && !hasImage) {
        tr.remove();
      }
    });
    renumberRows();
  }

  function parseBulkGeneratorRows(raw) {
    var text = (raw || "").trim();
    if (!text) {
      return [];
    }

    if (text.charAt(0) === "[" || text.charAt(0) === "{") {
      var parsed = JSON.parse(text);
      if (parsed && !Array.isArray(parsed) && Array.isArray(parsed.items)) {
        parsed = parsed.items;
      } else if (parsed && !Array.isArray(parsed) && (parsed.title !== undefined || parsed.caption !== undefined || parsed.imageUrl !== undefined || parsed.image_url !== undefined || parsed.image !== undefined)) {
        parsed = [parsed];
      }
      if (!Array.isArray(parsed)) {
        throw new Error('Expected a JSON array like [{"title":"Hello","imageUrl":"https://..."}].');
      }

      return parsed.map(function (item) {
        if (typeof item === "string") {
          item = item.trim();
          var looksLikeUrl = /^https?:\/\//i.test(item);
          return {
            title: looksLikeUrl ? "" : item,
            imageUrl: looksLikeUrl ? item : ""
          };
        }
        item = item || {};
        return {
          title: (item.title || item.caption || "").toString().trim(),
          imageUrl: (item.imageUrl || item.image_url || item.image || "").toString().trim()
        };
      }).filter(function (item) {
        return item.title || item.imageUrl;
      });
    }

    return text.split(/\r?\n/).map(function (line) {
      line = line.trim();
      if (!line) {
        return null;
      }

      var pipeIndex = line.indexOf("|");
      if (pipeIndex > -1) {
        return {
          title: line.slice(0, pipeIndex).trim(),
          imageUrl: line.slice(pipeIndex + 1).trim()
        };
      }

      return {
        title: line,
        imageUrl: ""
      };
    }).filter(Boolean);
  }

  function submitBulkGeneratorRows() {
    var textarea = document.getElementById("wpap-paste-area");
    var btn = document.getElementById("btn-paste-ok");
    if (!textarea || !btn) return;

    var raw = textarea.value.trim();
    if (!raw) {
      toast("Paste rows or JSON first.", "warn");
      return;
    }

    var items;
    try {
      items = parseBulkGeneratorRows(raw);
    } catch (err) {
      toast("Invalid input: " + err.message, "error");
      return;
    }

    if (!items.length) {
      toast("No rows found in the input.", "warn");
      return;
    }

    var originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳ Importing…";

    $.ajax({
      url: AJAX,
      method: "POST",
      dataType: "json",
      data: {
        action: "wpap_bulk_import_remote_images",
        nonce: NONCE,
        items: JSON.stringify(items)
      }
    }).done(function (res) {
      if (!res || !res.success) {
        var message = "Import failed.";
        if (res && res.data) {
          message = res.data.message || res.data || message;
        }
        toast(message, "error");
        return;
      }

      var data = res.data || {};
      var returnedItems = data.items || [];
      var total = parseInt(data.total, 10) || items.length;
      var created = parseInt(data.created, 10) || returnedItems.length;
      var skipped = parseInt(data.skipped, 10) || 0;
      if (data.messages && data.messages.length) {
        console.warn("WPAP bulk generator import:", data.messages);
      }

      removeBlankRows();
      returnedItems.forEach(function (item) {
        addRow(item.title || "", {
          attachId: item.attach_id,
          imageUrl: item.image_url,
          label: item.attach_id ? (item.label || item.title || "Image attached") : "No image"
        });
      });
      renumberRows();

      textarea.value = "";
      document.getElementById("wpap-paste-modal").style.display = "none";
      toast("Imported " + created + " of " + total + " rows" + (skipped ? " (" + skipped + " skipped)" : "") + ".", created ? "success" : "warn");
    }).fail(function (xhr) {
      var message = "Network error.";
      if (xhr && xhr.responseJSON && xhr.responseJSON.data) {
        message = xhr.responseJSON.data.message || xhr.responseJSON.data || message;
      }
      toast(message, "error");
    }).always(function () {
      btn.disabled = false;
      btn.textContent = originalLabel;
    });
  }

  function renumberRows() {
    document.querySelectorAll("#wpap-grid-body .wpap-grid-row").forEach(function (tr, i) {
      tr.querySelector(".gc-num").textContent = i + 1;
    });
    updateRowCount();
  }

  function updateRowCount() {
    var n = document.querySelectorAll("#wpap-grid-body .wpap-grid-row").length;
    var el = document.getElementById("wpap-row-count");
    if (el) el.textContent = n + (n === 1 ? " row" : " rows");
  }

  function setRowStatus(rowEl, state, html) {
    if (!rowEl) return;
    var s = rowEl.querySelector(".row-status");
    if (s) { s.className = "row-status row-" + state; s.innerHTML = html; }
  }

  function openImportModal() {
    var modal = document.getElementById("wpap-import-modal");
    if (!modal) return;
    modal.style.display = "flex";
    var textarea = document.getElementById("wpap-import-area");
    if (textarea) textarea.focus();
  }

  function openImageModal() {
    var modal = document.getElementById("wpap-images-modal");
    if (!modal) return;
    modal.style.display = "flex";
    var textarea = document.getElementById("wpap-images-area");
    if (textarea) textarea.focus();
  }

  function closeImportModal() {
    var modal = document.getElementById("wpap-import-modal");
    if (modal) modal.style.display = "none";
  }

  function closeImageModal() {
    var modal = document.getElementById("wpap-images-modal");
    if (modal) modal.style.display = "none";
  }

  function openJsonModal(jsonText) {
    var modal = document.getElementById("wpap-json-modal");
    var textarea = document.getElementById("wpap-json-area");
    if (!modal || !textarea) return;
    textarea.value = jsonText || "[]";
    modal.style.display = "flex";
    textarea.focus();
    textarea.select();
  }

  function closeJsonModal() {
    var modal = document.getElementById("wpap-json-modal");
    if (modal) modal.style.display = "none";
  }

  async function copyPlainText(text) {
    try {
      await navigator.clipboard.writeText(text);
    } catch (e) {
      var ta = document.createElement("textarea");
      ta.value = text;
      ta.style.cssText = "position:fixed;opacity:0";
      document.body.appendChild(ta);
      ta.select();
      document.execCommand("copy");
      ta.remove();
    }
  }

  function normalizeImportItems(raw) {
    var parsed = JSON.parse(raw);
    if (parsed && !Array.isArray(parsed) && Array.isArray(parsed.items)) {
      parsed = parsed.items;
    } else if (parsed && !Array.isArray(parsed) && (parsed.title !== undefined || parsed.caption !== undefined || parsed.comment !== undefined || parsed.imageUrl !== undefined || parsed.image_url !== undefined)) {
      parsed = [parsed];
    }
    if (!Array.isArray(parsed)) {
      throw new Error('Expected a JSON array like [{"caption":"Hello","comment":"link","imageUrl":"https://..."}].');
    }
    return parsed.map(function (item) {
      item = item || {};
      return {
        caption: (item.caption || item.title || "").toString().trim(),
        comment: (item.comment || item.fb_text || item.hook || "").toString().trim(),
        imageUrl: (item.imageUrl || item.image_url || item.image || "").toString().trim()
      };
    });
  }

  function submitImportItems() {
    var textarea = document.getElementById("wpap-import-area");
    var btn = document.getElementById("btn-import-ok");
    if (!textarea || !btn) return;

    var raw = textarea.value.trim();
    if (!raw) {
      toast("Paste a JSON array first.", "warn");
      return;
    }

    var items;
    try {
      items = normalizeImportItems(raw);
    } catch (err) {
      toast("Invalid JSON: " + err.message, "error");
      return;
    }

    if (!items.length) {
      toast("No items found in the JSON payload.", "warn");
      return;
    }

    var originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳ Importing…";

    $.ajax({
      url: AJAX,
      method: "POST",
      dataType: "json",
      data: {
        action: "wpap_bulk_import_distribution",
        nonce: NONCE,
        items: JSON.stringify(items)
      }
    }).done(function (res) {
      if (!res || !res.success) {
        var message = "Import failed.";
        if (res && res.data) {
          message = res.data.message || res.data || message;
        }
        toast(message, "error");
        return;
      }

      var data = res.data || {};
      var created = parseInt(data.created, 10) || 0;
      var total = parseInt(data.total, 10) || items.length;
      var skipped = parseInt(data.skipped, 10) || 0;
      var noticeSuffix = "";
      if (data.messages && data.messages.length) {
        console.warn("WPAP bulk import:", data.messages);
        noticeSuffix = " — " + data.messages.length + " notice(s): " + data.messages[0] + (data.messages.length > 1 ? " …" : "");
      }

      toast("Imported " + created + " of " + total + " items" + (skipped ? " (" + skipped + " skipped)" : "") + "." + noticeSuffix, created ? "success" : "warn");
      textarea.value = "";
      closeImportModal();
    }).fail(function (xhr) {
      var message = "Network error.";
      if (xhr && xhr.responseJSON && xhr.responseJSON.data) {
        message = xhr.responseJSON.data.message || xhr.responseJSON.data || message;
      }
      toast(message, "error");
    }).always(function () {
      btn.disabled = false;
      btn.textContent = originalLabel;
    });
  }

  function submitImageUrls() {
    var textarea = document.getElementById("wpap-images-area");
    var btn = document.getElementById("btn-images-ok");
    if (!textarea || !btn) return;

    var raw = textarea.value.trim();
    if (!raw) {
      toast("Paste one image URL per line first.", "warn");
      return;
    }

    var urls = raw.split(/\r?\n/).map(function (line) {
      return line.trim();
    }).filter(Boolean);

    if (!urls.length) {
      toast("No image URLs found.", "warn");
      return;
    }

    var originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳ Importing…";

    $.ajax({
      url: AJAX,
      method: "POST",
      dataType: "json",
      data: {
        action: "wpap_bulk_import_remote_images",
        nonce: NONCE,
        items: JSON.stringify(urls)
      }
    }).done(function (res) {
      if (!res || !res.success) {
        var message = "Image import failed.";
        if (res && res.data) {
          message = res.data.message || res.data || message;
        }
        toast(message, "error");
        return;
      }

      var data = res.data || {};
      var items = data.items || [];
      var total = parseInt(data.total, 10) || urls.length;
      var created = parseInt(data.created, 10) || items.length;
      var skipped = parseInt(data.skipped, 10) || 0;
      var noticeSuffix = "";
      if (data.messages && data.messages.length) {
        console.warn("WPAP bulk image import:", data.messages);
        noticeSuffix = " — " + data.messages.length + " notice(s): " + data.messages[0] + (data.messages.length > 1 ? " …" : "");
      }

      removeBlankRows();
      items.forEach(function (item, index) {
        addRow("", {
          attachId: item.attach_id,
          imageUrl: item.image_url,
          label: item.attach_id ? (item.label || ("Image " + (index + 1))) : "No image"
        });
      });
      renumberRows();

      toast("Imported " + created + " of " + total + " images" + (skipped ? " (" + skipped + " skipped)" : "") + "." + noticeSuffix, created ? "success" : "warn");
      textarea.value = "";
      closeImageModal();
    }).fail(function (xhr) {
      var message = "Network error.";
      if (xhr && xhr.responseJSON && xhr.responseJSON.data) {
        message = xhr.responseJSON.data.message || xhr.responseJSON.data || message;
      }
      toast(message, "error");
    }).always(function () {
      btn.disabled = false;
      btn.textContent = originalLabel;
    });
  }

  function submitBulkPublish() {
    var ta  = document.getElementById("wpap-publish-area");
    var btn = document.getElementById("btn-publish-go");
    if (!ta || !btn) return;

    var raw = ta.value.trim();
    if (!raw) { toast("Paste a JSON array first.", "warn"); return; }

    var items;
    try {
      items = JSON.parse(raw);
      if (items && !Array.isArray(items) && Array.isArray(items.items)) items = items.items;
      if (items && !Array.isArray(items)) items = [items];
    } catch (err) {
      toast("Invalid JSON: " + err.message, "error");
      return;
    }
    if (!Array.isArray(items) || !items.length) { toast("No items found in the JSON.", "warn"); return; }

    var partsSel    = document.getElementById("wpap-publish-parts");
    var schedSel    = document.getElementById("wpap-publish-schedule");
    var numParts    = partsSel ? partsSel.value : "1";
    var schedWindow = schedSel ? schedSel.value : "0";
    var partsN      = parseInt(numParts, 10) || 1;
    var winN        = parseFloat(schedWindow) || 0;

    var confirmMsg = "Publish " + items.length + " post(s) directly to WordPress?"
      + "\n• Split: " + (partsN > 1 ? ("into " + partsN + " pages (per-item \"parts\" overrides this)") : "single page")
      + "\n• Timing: " + (winN > 0 ? ("spread evenly over the next " + winN + "h, published in the order listed") : "publish now");
    if (!confirm(confirmMsg)) return;

    var logWrap = document.getElementById("wpap-publish-log");
    var logBody = document.getElementById("wpap-publish-log-body");
    var statsEl = document.getElementById("wpap-publish-stats");

    function pubLog(html, cls) {
      if (!logBody) return;
      var klass = cls === "ok" ? "wpap-log-success" : cls === "warn" ? "wpap-log-warn" : "wpap-log-info";
      var d = document.createElement("div");
      d.className = "wpap-log-line " + klass;
      d.innerHTML = html;
      logBody.appendChild(d);
      logBody.scrollTop = logBody.scrollHeight;
    }
    function pubMessages(msgs) {
      if (!msgs || !msgs.length) return;
      msgs.forEach(function (m) { pubLog("⚠ " + escHtml(m), "warn"); });
    }

    if (logWrap) logWrap.style.display = "";
    if (logBody) logBody.innerHTML = "";
    if (statsEl) { statsEl.style.display = ""; statsEl.textContent = "⏳ Publishing " + items.length + " item(s)…"; }

    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = "⏳ Publishing…";

    $.ajax({
      url: AJAX, method: "POST", dataType: "json", timeout: 600000,
      data: { action: "wpap_bulk_publish_posts", nonce: NONCE, items: JSON.stringify(items), num_parts: numParts, schedule_window: schedWindow }
    }).done(function (res) {
      if (!res || !res.success) {
        var em = (res && res.data && (res.data.message || res.data)) || "Publish failed.";
        em = (typeof em === "string") ? em : "Publish failed.";
        toast(em, "error");
        if (res && res.data && res.data.messages) pubMessages(res.data.messages);
        if (statsEl) statsEl.textContent = "❌ " + em;
        return;
      }
      applyFreshNonce(res);
      var d = res.data || {};
      var created = parseInt(d.created, 10) || 0;
      var skipped = parseInt(d.skipped, 10) || 0;
      var total   = parseInt(d.total, 10) || items.length;
      (d.rows || []).forEach(function (r) {
        var extra = "";
        if (r.parts && r.parts > 1) extra += " · 📄 " + r.parts + " pages";
        if (r.post_status === "future" && r.scheduled_label) extra += " · 🕒 " + escHtml(r.scheduled_label);
        pubLog((r.post_status === "future" ? "🕒 " : "✅ ") + escHtml(r.title || "(untitled)") + (r.has_image ? " 🖼" : "") + extra +
               ' — <a href="' + escHtml(r.post_url || "#") + '" target="_blank" rel="noopener">view post</a>', "ok");
      });
      pubMessages(d.messages);
      if (statsEl) statsEl.textContent = "✅ " + created + " published · " + skipped + " skipped · " + total + " total";
      toast("Published " + created + " of " + total + " post(s)" + (skipped ? " (" + skipped + " skipped)" : "") + ".", created ? "success" : "warn");
      if (created) ta.value = "";
      if (created) { loadDashboardStats(); loadTable(1, ""); }   /* show the new posts in the Hub right away */
    }).fail(function (xhr) {
      var fm = (xhr && xhr.responseJSON && xhr.responseJSON.data && (xhr.responseJSON.data.message || xhr.responseJSON.data)) || "Network error.";
      fm = (typeof fm === "string") ? fm : "Network error.";
      toast(fm, "error");
      if (statsEl) statsEl.textContent = "❌ " + fm;
    }).always(function () {
      btn.disabled = false;
      btn.innerHTML = orig;
    });
  }

  function loadDistributionJson() {
    /* Export EXACTLY the rows currently shown in the table below (the current
       page — up to 10), built from the same data that rendered them. */
    if (!lastRows || !lastRows.length) {
      toast("Nothing to export on this page.", "warn");
      openJsonModal("[]");
      return;
    }
    var items = lastRows.map(function (r) {
      return {
        caption:  r.fb_text || "",                       /* caption  = the hook */
        comment:  r.smart_link || r.post_url || "",      /* comment  = the post link */
        imageUrl: r.image_url || ""                      /* imageUrl = the original image */
      };
    });
    openJsonModal(JSON.stringify(items));
  }

  async function copyJsonToClipboard() {
    var textarea = document.getElementById("wpap-json-area");
    if (!textarea) return;
    var text = textarea.value || "[]";
    await copyPlainText(text);
    toast("JSON copied to clipboard!", "success");
  }

  /* ─────────────────────────────────────────
     QUEUE
  ───────────────────────────────────────── */
  function collectRows(rowEls) {
    var q = [];
    rowEls.forEach(function (tr) {
      var titleEl = tr.querySelector(".grid-title-input");
      var aidEl   = tr.querySelector(".grid-attach-id");
      var title   = titleEl ? titleEl.value.trim() : "";
      var aid     = aidEl ? (parseInt(aidEl.value, 10) || 0) : 0;
      /* Queue if title exists, OR if title is empty but an image is attached (vision analysis) */
      if (title || aid > 0) q.push({ title: title, attachId: aid, rowEl: tr });
    });
    return q;
  }

  function runQueue(q, label) {
    if (isProcessing) { toast("A run is already in progress.", "warn"); return; }
    if (!q.length) { toast("⚠ Add at least one title or upload an image!", "warn"); return; }

    gridQueue = q;
    queueIndex = 0; isProcessing = true; stopFlag = false; results = [];
    document.getElementById("wpap-start-btn").style.display     = "none";
    document.getElementById("wpap-stop-btn").style.display      = "";
    var retryBtn = document.getElementById("wpap-retry-btn");
    if (retryBtn) retryBtn.style.display = "none";
    document.getElementById("wpap-queue-stats").style.display   = "";
    document.getElementById("wpap-progress-wrap").style.display = "";
    document.getElementById("wpap-log").style.display           = "";
    updateStats();

    var sel = document.getElementById("wpap-lang-select");
    var lbl = sel ? sel.options[sel.selectedIndex].text : "Auto";
    var pgSel  = document.getElementById("wpap-pages-select");
    var imgSel = document.getElementById("wpap-img-engine-select");
    var cntSel = document.getElementById("wpap-content-engine-select");
    var pgLbl  = pgSel  ? pgSel.options[pgSel.selectedIndex].text   : "2 Pages";
    var imgLbl = imgSel ? imgSel.options[imgSel.selectedIndex].text : "Gemini";
    var cntLbl = cntSel ? cntSel.options[cntSel.selectedIndex].text : "Claude";
    var schSel = document.getElementById("wpap-schedule-select");
    var schLbl = schSel ? schSel.options[schSel.selectedIndex].text : "Publish now";
    logLine("🚀 Starting" + (label ? " (" + escHtml(label) + ")" : "") + " — " + gridQueue.length + " posts · Lang: <strong>" + escHtml(lbl) + "</strong> · " + escHtml(pgLbl) + " · Content: " + escHtml(cntLbl) + " · Img: " + escHtml(imgLbl) + " · 🕒 " + escHtml(schLbl), "info");
    processNext();
  }

  function startQueue() {
    /* Skip rows already published (row-done) so a second Start — or a per-row
       Run followed by Start — never republishes them. */
    var rows = Array.prototype.slice.call(document.querySelectorAll("#wpap-grid-body .wpap-grid-row")).filter(function (tr) {
      var s = tr.querySelector(".row-status");
      return !(s && s.classList.contains("row-done"));
    });
    runQueue(collectRows(rows), "");
  }

  /* Re-run ONLY the rows that errored (saves API spend + avoids duplicate publishes). */
  function retryFailed() {
    var errorRows = Array.prototype.slice.call(document.querySelectorAll("#wpap-grid-body .wpap-grid-row")).filter(function (tr) {
      var st = tr.querySelector(".row-status");
      return st && st.classList.contains("row-error");
    });
    if (!errorRows.length) { toast("No failed rows to retry.", "info"); return; }
    errorRows.forEach(function (tr) { setRowStatus(tr, "idle", "Pending"); });
    runQueue(collectRows(errorRows), errorRows.length + " retry");
  }

  /* Run a single grid row (test one title before a big batch). */
  function runSingleRow(tr) {
    var q = collectRows([tr]);
    if (!q.length) { toast("Add a title or image to this row first.", "warn"); return; }
    runQueue(q, "1 row");
  }

  function stopQueue() {
    isProcessing = false; stopFlag = true;
    document.getElementById("wpap-start-btn").style.display = "";
    document.getElementById("wpap-stop-btn").style.display  = "none";
    logLine("🛑 Stopped by user.", "warn");
  }

  function processNext() {
    if (!isProcessing || stopFlag || queueIndex >= gridQueue.length) { finishQueue(); return; }
    var item = gridQueue[queueIndex];
    setRowStatus(item.rowEl, "processing", "⏳ Processing…");
    logLine("⏳ [" + (queueIndex + 1) + "/" + gridQueue.length + "] <strong>" + escHtml(item.title) + "</strong>…");

    var sel  = document.getElementById("wpap-lang-select");
    var lang = sel ? sel.value : "auto";

    $.ajax({
      url: AJAX, type: "POST", timeout: 180000,
      data: { action: "wpap_process_title", nonce: NONCE, title: item.title, fallback_attach_id: item.attachId, target_lang: lang,
               num_pages: (document.getElementById("wpap-pages-select") ? document.getElementById("wpap-pages-select").value : "2"),
               image_engine: (document.getElementById("wpap-img-engine-select") ? document.getElementById("wpap-img-engine-select").value : "gemini_flash"),
               content_engine: (document.getElementById("wpap-content-engine-select") ? document.getElementById("wpap-content-engine-select").value : "claude_haiku"),
               schedule_window: (document.getElementById("wpap-schedule-select") ? document.getElementById("wpap-schedule-select").value : "0") },
      success: function (res) {
        if (res.success) {
          applyFreshNonce(res);
          results.push({ status: "ok" });
          var imgI = res.data.image_url ? "🖼✅" : ("🖼❌ " + escHtml(res.data.image_debug || ""));
          var fbI  = res.data.fb_post_id ? "📘✅" : ("📘❌ " + escHtml(res.data.fb_status || ""));
          var isScheduled = res.data.post_status === "future";
          var schedInfo   = (isScheduled && res.data.scheduled_label) ? (" 🕒 " + escHtml(res.data.scheduled_label)) : "";
          setRowStatus(item.rowEl, "done", (isScheduled ? "🕒" : "✅") + " <a href='" + escHtml(res.data.post_url || "#") + "' target='_blank'>View ↗</a>");
          logLine((isScheduled ? "🕒 " : "✅ ") + "<strong>" + escHtml(item.title) + "</strong>" + schedInfo + " — <a href='" + escHtml(res.data.post_url || "#") + "' target='_blank'>View ↗</a>  " + imgI + " | " + fbI + " | 🌐" + escHtml(res.data.lang || ""), "success");
          var nid = "live_" + Date.now();
          fbTextsStore[nid] = res.data.fb_text || "";
          injectNewRow({ id: nid, title: res.data.title || item.title, post_url: res.data.post_url, image_url: res.data.image_url, fb_text: res.data.fb_text, smart_link: res.data.smart_link || res.data.post_url, created_at: new Date().toISOString().split("T")[0] });
          setTimeout(function () { loadTable(1, ""); }, 600);
        } else {
          results.push({ status: "error" });
          setRowStatus(item.rowEl, "error", "❌ " + escHtml((res.data || "Error").toString().substr(0, 50)));
          logLine("❌ <strong>" + escHtml(item.title) + "</strong> — " + escHtml(res.data || "Unknown error"), "error");
        }
      },
      error: function (xhr, status) {
        results.push({ status: "error" });
        var d = status === "timeout" ? "Timeout (>180s)" : "HTTP " + xhr.status;
        setRowStatus(item.rowEl, "error", "❌ " + d);
        logLine("❌ <strong>" + escHtml(item.title) + "</strong> — " + d, "error");
      },
      complete: function () {
        queueIndex++; updateStats();
        if (isProcessing && !stopFlag) { setTimeout(processNext, 400); } else { finishQueue(); }
      }
    });
  }

  function finishQueue() {
    isProcessing = false;
    document.getElementById("wpap-start-btn").style.display = "";
    document.getElementById("wpap-stop-btn").style.display  = "none";
    document.getElementById("wpap-progress-bar").style.width = "100%";
    var ok  = results.filter(function (r) { return r.status === "ok"; }).length;
    var err = results.filter(function (r) { return r.status === "error"; }).length;
    logLine("🏁 Complete — " + ok + " published, " + err + " errors.", ok ? "success" : "warn");
    toast("✅ Done! " + ok + " posts published.", "success");
    var retryBtn = document.getElementById("wpap-retry-btn");
    if (retryBtn) retryBtn.style.display = err > 0 ? "" : "none";
    if (ok > 0) { try { localStorage.removeItem("wpap_grid_draft"); } catch (e) {} }
    loadDashboardStats();
  }

  /* Autosave the grid titles to localStorage so an accidental refresh doesn't lose a
     hand-curated list. Cleared after a successful run or Clear All. */
  function saveGridDraft() {
    try {
      var titles = [];
      document.querySelectorAll("#wpap-grid-body .wpap-grid-row").forEach(function (tr) {
        var st = tr.querySelector(".row-status");
        if (st && st.classList.contains("row-done")) return;   /* don't persist already-published rows */
        var el = tr.querySelector(".grid-title-input");
        var v  = el ? el.value.trim() : "";
        if (v) titles.push(v);
      });
      if (titles.length) { localStorage.setItem("wpap_grid_draft", JSON.stringify(titles)); }
      else { localStorage.removeItem("wpap_grid_draft"); }
    } catch (e) {}
  }

  function restoreGridDraft() {
    var raw;
    try { raw = localStorage.getItem("wpap_grid_draft"); } catch (e) { return; }
    if (!raw) return;
    var titles;
    try { titles = JSON.parse(raw); } catch (e) { return; }
    if (!Array.isArray(titles) || !titles.length) return;
    if (!confirm("Restore your previous " + titles.length + " unsent row(s)?")) {
      try { localStorage.removeItem("wpap_grid_draft"); } catch (e) {}
      return;
    }
    document.getElementById("wpap-grid-body").innerHTML = "";
    gridRowCounter = 0;
    titles.forEach(function (t) { addRow(t); });
    updateRowCount();
  }

  function updateStats() {
    var pct = gridQueue.length ? Math.round(queueIndex / gridQueue.length * 100) : 0;
    document.getElementById("wpap-stat-done").textContent = queueIndex;
    document.getElementById("wpap-stat-left").textContent = Math.max(0, gridQueue.length - queueIndex);
    document.getElementById("wpap-stat-err").textContent  = results.filter(function (r) { return r.status === "error"; }).length;
    document.getElementById("wpap-progress-bar").style.width = pct + "%";
  }

  /* ─────────────────────────────────────────
     DISTRIBUTION TABLE
  ───────────────────────────────────────── */
  /* Distribution Hub row selection + bulk delete. */
  function updateSelectedCount() {
    var n     = document.querySelectorAll("#wpap-dist-tbody .wpap-row-cb:checked").length;
    var total = document.querySelectorAll("#wpap-dist-tbody .wpap-row-cb").length;
    var btn = document.getElementById("wpap-delete-selected-btn");
    var cnt = document.getElementById("wpap-sel-count");
    var all = document.getElementById("wpap-select-all");
    if (cnt) cnt.textContent = n;
    if (btn) btn.style.display = n > 0 ? "" : "none";
    if (all) { all.checked = total > 0 && n === total; all.indeterminate = n > 0 && n < total; }
  }

  function deleteSelected() {
    var ids = [];
    document.querySelectorAll("#wpap-dist-tbody .wpap-row-cb:checked").forEach(function (cb) {
      var v = parseInt(cb.value, 10);
      if (v > 0) ids.push(v);
    });
    if (!ids.length) { toast("Select some rows first.", "warn"); return; }
    if (!confirm("Remove " + ids.length + " selected entr" + (ids.length === 1 ? "y" : "ies") + " from the Distribution Hub?\n\nThis removes the Hub listing ONLY — your WordPress posts are NOT touched.")) return;
    var btn = document.getElementById("wpap-delete-selected-btn");
    if (btn) btn.disabled = true;
    /* Hub-only: never send delete_posts. Deleting actual posts is done from the
       WordPress Posts screen (which uses the Trash, so it's recoverable). */
    $.post(AJAX, { action: "wpap_bulk_delete_distribution", nonce: NONCE, ids: ids }, function (res) {
      if (res && res.success) {
        var d = res.data || {};
        toast("Removed " + (d.deleted || 0) + " from the Hub (posts untouched).", "success");
        loadTable(currentPage, currentSearch || "");
        loadDashboardStats();
      } else {
        toast((res && res.data) ? res.data : "Delete failed.", "error");
      }
    }).fail(function () { toast("Network error.", "error"); }).always(function () {
      if (btn) btn.disabled = false;
    });
  }

  /* Dashboard stat strip (plugin posts at a glance). */
  function loadDashboardStats() {
    if (!AJAX) return;
    $.get(AJAX, { action: "wpap_dashboard_stats", nonce: NONCE }, function (res) {
      if (!res || !res.success) return;
      var d = res.data || {};
      var strip = document.getElementById("wpap-stat-strip");
      if (!strip) return;
      function card(label, val, cls) {
        return '<div class="wpap-stat-card ' + (cls || "") + '"><span class="wpap-stat-num">' + (parseInt(val, 10) || 0) + '</span><span class="wpap-stat-lbl">' + label + '</span></div>';
      }
      strip.innerHTML =
        card("Published", d.published) +
        card("Scheduled", d.scheduled) +
        card("Drafts", d.drafts) +
        card("Last 7 days", d.last7) +
        card("No image", d.no_image, (parseInt(d.no_image, 10) > 0 ? "wpap-stat-warn" : "")) +
        card("Total", d.total);
      strip.style.display = "";
    });
  }

  function loadTable(page, search) {
    currentPage   = page || 1;
    currentSearch = search !== undefined ? search : currentSearch;
    var tbody = document.getElementById("wpap-dist-tbody");
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="wpap-table-loading"><span class="wpap-spinner"></span> Loading…</td></tr>';
    if (!AJAX) {
      renderTableError("AJAX configuration missing.");
      return;
    }
    $.get(AJAX, { action: "wpap_get_posts", nonce: NONCE, page: currentPage, search: currentSearch, status: currentStatus, per_page: currentPerPage }, function (res) {
      if (!res.success) { renderTableError(res.data); return; }
      renderTable(res.data);
    }).fail(function () { renderTableError("Network error."); });
  }

  function renderTable(data) {
    var tbody = document.getElementById("wpap-dist-tbody");
    if (!tbody) return;
    lastRows = (data && data.rows) ? data.rows.slice() : [];   /* keep export in sync with what's shown */
    if (!data.rows || !data.rows.length) {
      /* If this page is empty but earlier pages have rows (e.g. we just deleted
         the whole last page), don't strand the user on a blank page — jump back
         to page 1 instead of showing "No posts". */
      if (currentPage > 1 && (parseInt(data.total, 10) || 0) > 0) { loadTable(1, currentSearch || ""); return; }
      tbody.innerHTML = '<tr><td colspan="7" class="wpap-table-empty">' + (currentSearch ? "No posts match your search." : (currentStatus !== "all" ? "No posts with this status." : "No posts yet — generate some above! 🚀")) + '</td></tr>';
      document.getElementById("wpap-pagination").innerHTML = "";
      updateSelectedCount();
      return;
    }
    tbody.innerHTML = data.rows.map(function (r) { return buildRow(r); }).join("");
    renderPagination(data);
    updateSelectedCount();
  }

  function buildRow(row) {
    var id   = row.id || ("r" + Math.random().toString(36).substr(2, 6));
    var t    = escHtml(row.title    || "");
    var pu   = escHtml(row.post_url || "#");
    var iu   = escHtml(row.image_url || "");
    var sl   = escHtml(row.smart_link || row.post_url || "");
    var dt   = (row.created_at || "").split(" ")[0];
    fbTextsStore[id] = row.fb_text || "";
    var thumb = iu ? '<img src="' + iu + '" class="wpap-thumb" loading="lazy" decoding="async" width="56" height="56" alt="" />' : '<span class="wpap-no-img">No image</span>';
    var cimg  = iu ? '<button class="wpap-action-btn" data-url="' + iu + '" onclick="wpapCopyImage(this)">📋 Copy</button>' : '<button class="wpap-action-btn" disabled>—</button>';
    var dl    = iu ? '<a class="wpap-action-btn wpap-dl-btn" href="' + iu + '" download>⬇ Save</a>' : '<span class="wpap-action-btn" style="opacity:.4">—</span>';
    return '<tr>' +
      '<td class="wpap-cb-cell"><input type="checkbox" class="wpap-row-cb" value="' + escHtml(String(row.id || "")) + '" /></td>' +
      '<td class="wpap-title-cell"><a href="' + pu + '" target="_blank" class="wpap-post-link" title="' + t + '">' + t + '</a><span class="wpap-date">' + dt + '</span></td>' +
      '<td class="wpap-img-cell">' + thumb + '</td><td>' + cimg + '</td><td>' + dl + '</td>' +
      '<td><button class="wpap-action-btn" data-rowid="' + id + '" onclick="wpapCopyHook(this)">💬 Hook</button></td>' +
      '<td><button class="wpap-action-btn" data-url="' + sl + '" onclick="wpapCopyText(this,this.dataset.url,\'Link copied!\')">🔗 Link</button>' +
      ' <button class="wpap-action-btn wpap-del-btn" style="color:#e5534b" data-id="' + escHtml(String(row.id || "")) + '" data-postid="' + (parseInt(row.post_id, 10) || 0) + '" onclick="wpapDeleteRow(this)" title="Remove this entry from the Hub">🗑</button></td>' +
      '</tr>';
  }

  function injectNewRow(row) {
    var tbody = document.getElementById("wpap-dist-tbody");
    if (!tbody) return;
    var empty = tbody.querySelector(".wpap-table-empty, .wpap-table-loading");
    if (empty) { var ep = empty.closest("tr"); if (ep) ep.remove(); }
    fbTextsStore[row.id] = row.fb_text || "";
    lastRows.unshift(row);   /* mirror the injected row so Export matches the list */
    tbody.insertAdjacentHTML("afterbegin", buildRow(row));
    var first = tbody.querySelector("tr:first-child");
    if (first) { first.style.background = "rgba(63,185,80,.12)"; setTimeout(function () { first.style.background = ""; }, 2000); }
  }

  function renderPagination(data) {
    var wrap = document.getElementById("wpap-pagination");
    if (!wrap) return;
    if (data.total_pages <= 1) { wrap.innerHTML = ""; return; }
    var tp = data.total_pages, pg = data.page;
    var s = Math.max(1, pg - 2);
    var e = Math.min(tp, s + 4);
    if (e - s < 4) s = Math.max(1, e - 4);   /* keep a stable window of up to 5 page numbers */
    var h = '<div class="wpap-pager"><span class="wpap-pager-info">Showing ' + ((pg - 1) * data.per_page + 1) + "–" + Math.min(pg * data.per_page, data.total) + " of " + data.total + '</span><div class="wpap-pager-btns">';
    if (pg > 1) {
      h += '<button class="wpap-pager-btn" onclick="wpapGoPage(1)" title="First page">« First</button>';
      h += '<button class="wpap-pager-btn" onclick="wpapGoPage(' + (pg - 1) + ')">← Prev</button>';
    }
    if (s > 1) h += '<span class="wpap-pager-gap">…</span>';
    for (var i = s; i <= e; i++) h += '<button class="wpap-pager-btn' + (i === pg ? " active" : "") + '" onclick="wpapGoPage(' + i + ')">' + i + '</button>';
    if (e < tp) h += '<span class="wpap-pager-gap">…</span>';
    if (pg < tp) {
      h += '<button class="wpap-pager-btn" onclick="wpapGoPage(' + (pg + 1) + ')">Next →</button>';
      h += '<button class="wpap-pager-btn" onclick="wpapGoPage(' + tp + ')" title="Last page">Last »</button>';
    }
    /* Jump-to-page: type a number + Enter (or Go) to leap straight there. */
    h += '<span class="wpap-pager-jump">Go to <input type="number" id="wpap-pager-input" min="1" max="' + tp + '" value="' + pg + '" style="width:64px" '
       + 'onkeydown="if(event.key===\'Enter\'){wpapJumpPage(' + tp + ');event.preventDefault();}" /> / ' + tp
       + ' <button class="wpap-pager-btn" onclick="wpapJumpPage(' + tp + ')">Go</button></span>';
    h += '</div></div>';
    wrap.innerHTML = h;
  }
  /* Clamp + navigate from the jump-to-page input. */
  window.wpapJumpPage = function (totalPages) {
    var el = document.getElementById("wpap-pager-input");
    if (!el) return;
    var n = parseInt(el.value, 10);
    if (isNaN(n)) return;
    n = Math.max(1, Math.min(totalPages, n));
    wpapGoPage(n);
  };

  function renderTableError(msg) {
    var tbody = document.getElementById("wpap-dist-tbody");
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="wpap-table-error">⚠ ' + escHtml(msg) + '</td></tr>';
  }

  /* ─────────────────────────────────────────
     GLOBAL HELPERS
  ───────────────────────────────────────── */
  window.wpapGoPage = function (page) {
    loadTable(page);
    document.getElementById("dist-section").scrollIntoView({ behavior: "smooth" });
  };

  window.wpapCopyHook = async function (btn) {
    var id = btn.dataset.rowid;
    var text = fbTextsStore[id] || "";
    if (!text) { flashBtn(btn, "❌ Empty!", "error"); toast("No hook text for this post.", "error"); return; }
    await wpapCopyText(btn, text, "Hook copied!");
  };

  window.wpapCopyText = async function (btn, text, msg) {
    try { await navigator.clipboard.writeText(text); }
    catch (e) {
      var ta = document.createElement("textarea");
      ta.value = text; ta.style.cssText = "position:fixed;opacity:0";
      document.body.appendChild(ta); ta.select(); document.execCommand("copy"); ta.remove();
    }
    flashBtn(btn, "✅ Copied!", "success"); toast(msg || "Copied!", "success");
  };

  window.wpapCopyImage = async function (btn) {
    var url = btn.dataset.url; if (!url) return;
    flashBtn(btn, "⏳…", "loading");
    try {
      var proxy = AJAX + "?action=wpap_proxy_image&nonce=" + NONCE + "&url=" + encodeURIComponent(url);
      var src   = url.startsWith(window.location.origin) ? url : proxy;
      var res   = await fetch(src);
      var blob  = await res.blob();
      var bmp   = await createImageBitmap(blob);
      var c     = document.createElement("canvas");
      c.width = bmp.width; c.height = bmp.height;
      c.getContext("2d").drawImage(bmp, 0, 0);
      c.toBlob(async function (png) {
        try { await navigator.clipboard.write([new ClipboardItem({ "image/png": png })]); flashBtn(btn, "✅ Copied!", "success"); toast("🖼 Image copied — Ctrl+V to paste!", "success"); }
        catch (e) { flashBtn(btn, "❌ Failed", "error"); toast("Clipboard blocked. Try downloading.", "error"); }
      }, "image/png");
    } catch (e) { flashBtn(btn, "❌ Error", "error"); toast("Could not fetch image: " + e.message, "error"); }
  };

  function logLine(html, type) {
    var body = document.getElementById("wpap-log-body"); if (!body) return;
    var cls  = type ? ("wpap-log-" + type) : "";
    var ts   = new Date().toLocaleTimeString();
    body.innerHTML += '<div class="wpap-log-line ' + cls + '"><span class="wpap-log-ts">[' + ts + ']</span> ' + html + '</div>';
    body.scrollTop  = body.scrollHeight;
  }

  function toast(msg, type) {
    var el = document.getElementById("wpap-toast"); if (!el) return;
    el.textContent = msg;
    el.className   = "wpap-toast wpap-toast-" + (type || "info") + " wpap-toast-show";
    el.style.display = "";
    clearTimeout(el._t);
    el._t = setTimeout(function () { el.classList.remove("wpap-toast-show"); setTimeout(function () { el.style.display = "none"; }, 400); }, 3200);
  }

  function flashBtn(btn, label, state) {
    var orig = btn._orig || btn.textContent; btn._orig = orig;
    btn.textContent = label; btn.classList.add("wpap-btn-flash-" + state);
    setTimeout(function () { btn.textContent = orig; btn.classList.remove("wpap-btn-flash-" + state); }, 2500);
  }

  function escHtml(str) {
    return String(str == null ? "" : str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  /* Delete a single Distribution Hub entry (does not touch the WordPress post). */
  window.wpapDeleteRow = function (btn) {
    var id  = parseInt(btn.dataset.id, 10) || 0;
    var pid = parseInt(btn.dataset.postid, 10) || 0;
    if (!id && !pid) { toast("Please refresh the list, then try deleting.", "warn"); return; }
    if (!confirm("Remove this entry from the Distribution Hub?\n\nThis removes the Hub listing ONLY — your WordPress post is NOT touched.")) return;
    var tr = btn.closest("tr");
    btn.disabled = true;
    /* Hub-only: never send delete_post. */
    $.post(AJAX, { action: "wpap_delete_distribution", nonce: NONCE, id: id, post_id: pid }, function (res) {
      if (res && res.success) {
        if (tr) { tr.style.transition = "opacity .3s"; tr.style.opacity = "0"; setTimeout(function () { tr.remove(); }, 300); }
        lastRows = (lastRows || []).filter(function (r) { return String(r.id) !== String(id); });
        toast("Removed from Hub (post untouched).", "success");
      } else {
        btn.disabled = false;
        toast((res && res.data) ? res.data : "Could not remove the entry.", "error");
      }
    }).fail(function () { btn.disabled = false; toast("Network error.", "error"); });
  };

  /* Bulk-remove every Hub entry whose post was deleted or trashed. */
  function cleanupDeletedRows() {
    if (!confirm("Remove all Hub entries whose WordPress post was deleted or trashed?")) return;
    var btn = document.getElementById("wpap-cleanup-btn");
    var orig = btn ? btn.innerHTML : "";
    if (btn) { btn.disabled = true; btn.innerHTML = "🧹 Cleaning…"; }
    $.post(AJAX, { action: "wpap_cleanup_distribution", nonce: NONCE }, function (res) {
      if (res && res.success) {
        var n = (res.data && res.data.removed) || 0;
        toast(n ? ("Removed " + n + " deleted post(s) from the Hub.") : "Nothing to clean — every entry still has a live post.", n ? "success" : "info");
        loadTable(1, currentSearch || "");
      } else {
        toast((res && res.data) ? res.data : "Cleanup failed.", "error");
      }
    }).fail(function () { toast("Network error.", "error"); }).always(function () {
      if (btn) { btn.disabled = false; btn.innerHTML = orig; }
    });
  }

})(jQuery);
