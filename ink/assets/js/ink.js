/**
 * 墨 Ink —— 交互
 */
(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var progress = doc.getElementById('progressBar');
  var toTop = doc.getElementById('toTop');
  var pick0 = doc.getElementById('palettePick');

  /* ---------------------------------------------------------
   * 配色切换（玫瑰紫 / 珊瑚橙 / 孔雀青 / 石墨蓝 / 深海蓝 / 靛蓝紫 / 罗兰紫）
   * 选择写入 cookie，下次首屏由 PHP 直接输出，不会闪色。
   *
   * 配色清单不写死在这里：直接读后台渲染出来的按钮，
   * 以后加一套配色只要改 ink_palettes()，这段 JS 不用动。
   * ------------------------------------------------------- */
  var paletteBtns = doc.querySelectorAll('[data-palette-set]');
  var palettes = [];
  Array.prototype.forEach.call(paletteBtns, function (b) {
    var n = b.getAttribute('data-palette-set');
    if (n && palettes.indexOf(n) < 0) {
      palettes.push(n);
    }
  });
  var paletteDefault = (pick0 && pick0.getAttribute('data-default')) || 'rose';

  function cookieDays(days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 864e5);
    return ';expires=' + d.toUTCString();
  }

  function setPaletteCookie(name) {
    try {
      doc.cookie = 'ink_palette=' + encodeURIComponent(name) + cookieDays(365) + ';path=/;SameSite=Lax';
    } catch (e) {}
  }

  function applyPalette(name, remember) {
    if (palettes.indexOf(name) < 0) {
      name = paletteDefault;
    }
    if (name === paletteDefault) {
      root.removeAttribute('data-palette');
    } else {
      root.setAttribute('data-palette', name);
    }
    Array.prototype.forEach.call(paletteBtns, function (b) {
      var on = b.getAttribute('data-palette-set') === name;
      b.classList.toggle('is-on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    if (remember) {
      setPaletteCookie(name);
    }
    syncThemeColor();
  }

  Array.prototype.forEach.call(paletteBtns, function (b) {
    b.addEventListener('click', function () {
      applyPalette(b.getAttribute('data-palette-set'), true);
      closePanel();
    });
  });

  var pick = pick0;
  var panel = doc.getElementById('palettePanel');

  function closePanel() {
    if (panel) {
      panel.classList.remove('is-open');
    }
    if (pick) {
      pick.setAttribute('aria-expanded', 'false');
    }
  }

  if (pick && panel) {
    pick.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = panel.classList.toggle('is-open');
      pick.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    doc.addEventListener('click', function (e) {
      if (panel.classList.contains('is-open') && !panel.contains(e.target) && e.target !== pick) {
        closePanel();
      }
    });
    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closePanel();
      }
    });
  }

  /* 地址栏颜色跟着纸面色走 */
  function syncThemeColor() {
    var meta = doc.getElementById('themeColor');
    if (!meta) {
      return;
    }
    var paper = window.getComputedStyle(doc.body).backgroundColor;
    if (paper) {
      meta.setAttribute('content', paper);
    }
  }

  /* ---------------------------------------------------------
   * 明暗切换（与配色共用一个按钮，面板下半选择）
   * ------------------------------------------------------- */
  var modeBtns = doc.querySelectorAll('[data-mode-set]');

  function applyMode(mode) {
    if (mode !== 'light' && mode !== 'dark') {
      mode = 'light';
    }
    root.setAttribute('data-theme', mode);
    try {
      localStorage.setItem('ink-theme', mode);
    } catch (e) {}
    Array.prototype.forEach.call(modeBtns, function (b) {
      var on = b.getAttribute('data-mode-set') === mode;
      b.classList.toggle('is-on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    syncThemeColor();
  }

  Array.prototype.forEach.call(modeBtns, function (b) {
    b.addEventListener('click', function () {
      applyMode(b.getAttribute('data-mode-set'));
    });
  });

  /* 首屏把面板里的模式按钮点亮（data-theme 已由内联脚本定好） */
  applyMode(root.getAttribute('data-theme') || 'light');

  /* ---------------------------------------------------------
   * 滚动行为：进度线 / 返回顶部
   * （导航固定，不再吸顶或随滚动隐藏）
   * ------------------------------------------------------- */
  var lastY = window.pageYOffset || 0;
  var queued = false;

  function onScroll() {
    var y = window.pageYOffset;

    if (progress) {
      var max = doc.documentElement.scrollHeight - window.innerHeight;
      var pct = max > 0 ? Math.min(100, Math.max(0, (y / max) * 100)) : 0;
      progress.style.width = pct.toFixed(2) + '%';
    }

    if (toTop) {
      toTop.classList.toggle('is-show', y > 460);
    }

    lastY = y;
    queued = false;
  }

  function requestScroll() {
    if (!queued) {
      queued = true;
      window.requestAnimationFrame(onScroll);
    }
  }

  window.addEventListener('scroll', requestScroll, { passive: true });
  window.addEventListener('resize', requestScroll);

  if (toTop) {
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---------------------------------------------------------
   * 代码块：语言标签 + 复制
   * ------------------------------------------------------- */
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = doc.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.top = '-1000px';
      ta.style.opacity = '0';
      doc.body.appendChild(ta);
      ta.select();
      try {
        var ok = doc.execCommand('copy');
        doc.body.removeChild(ta);
        ok ? resolve() : reject(new Error('copy failed'));
      } catch (err) {
        doc.body.removeChild(ta);
        reject(err);
      }
    });
  }

  var ICON_COPY = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 5.5V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h.5"/></svg>';
  var ICON_DONE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.5 9.5 17.5 19.5 6.5"/></svg>';

  /* 给代码逐行包一层 span，让 CSS 计数器排出行号；
     包之前先确认每行都是纯文本节点，有高亮标签的保持原样 */
  function addLineNumbers(code) {
    if (code.children.length > 0 || code.querySelector('.tok-key, .tok-str, .tok-com')) {
      return false;
    }
    var text = code.textContent;
    if (!text || text.length > 40000 || text.indexOf('\n') < 0) {
      return false;
    }
    var lines = text.replace(/\n$/, '').split('\n');
    var frag = doc.createDocumentFragment();
    lines.forEach(function (line) {
      var span = doc.createElement('span');
      span.className = 'ln';
      span.textContent = line === '' ? '\u200b' : line;
      frag.appendChild(span);
    });
    code.textContent = '';
    code.appendChild(frag);
    return true;
  }

  function decorateCode() {
    var pres = doc.querySelectorAll('.content pre');

    Array.prototype.forEach.call(pres, function (pre) {
      if (pre.getAttribute('data-ink-code') === '1') {
        return;
      }
      var code = pre.querySelector('code');
      if (!code) {
        return;
      }
      pre.setAttribute('data-ink-code', '1');

      var cls = (code.className || '') + ' ' + (pre.className || '');
      var m = cls.match(/(?:language|lang)-([\w+#.-]+)/i);
      var lang = m ? m[1].toLowerCase() : '';

      if (lang) {
        var tag = doc.createElement('span');
        tag.className = 'lang-tag';
        tag.textContent = lang;
        pre.appendChild(tag);
        pre.classList.add('has-lang');
      }

      /* 行数够多才铺行号，两三行的短片段铺上去反而更碎 */
      if (code.textContent.split('\n').length > 3 && addLineNumbers(code)) {
        pre.classList.add('has-lines');
      }

      var btn = doc.createElement('button');
      btn.type = 'button';
      btn.className = 'copy-btn';
      btn.innerHTML = ICON_COPY + '<span>复制</span>';
      btn.setAttribute('aria-label', '复制代码');
      pre.appendChild(btn);

      btn.addEventListener('click', function () {
        // 只取纯文本：行号由 CSS 生成，不在 textContent 里
        copyText(code.textContent).then(function () {
          btn.innerHTML = ICON_DONE + '<span>已复制</span>';
          btn.classList.add('is-done');
          window.setTimeout(function () {
            btn.innerHTML = ICON_COPY + '<span>复制</span>';
            btn.classList.remove('is-done');
          }, 1600);
        }).catch(function () {
          btn.innerHTML = ICON_COPY + '<span>复制失败</span>';
          window.setTimeout(function () {
            btn.innerHTML = ICON_COPY + '<span>复制</span>';
          }, 1600);
        });
      });
    });
  }

  /* ---------------------------------------------------------
   * 图片灯箱
   * ------------------------------------------------------- */
  function initLightbox() {
    var lb = doc.getElementById('lightbox');
    var lbImg = doc.getElementById('lbImage');
    var lbClose = doc.getElementById('lbClose');
    if (!lb || !lbImg || !lbClose) {
      return;
    }

    var opener = null;

    function open(src, alt) {
      lbImg.setAttribute('src', src);
      lbImg.setAttribute('alt', alt || '');
      lb.classList.add('is-open');
      doc.body.style.overflow = 'hidden';
      opener = doc.activeElement;
      lbClose.focus();
    }

    function close() {
      lb.classList.remove('is-open');
      doc.body.style.overflow = '';
      if (opener && typeof opener.focus === 'function') {
        opener.focus();
      }
      window.setTimeout(function () {
        if (!lb.classList.contains('is-open')) {
          lbImg.removeAttribute('src');
        }
      }, 280);
    }

    var imgs = doc.querySelectorAll('.content img');

    Array.prototype.forEach.call(imgs, function (img) {
      if (img.closest('a')) {
        return;
      }
      img.setAttribute('tabindex', '0');
      img.setAttribute('role', 'button');
      img.addEventListener('click', function () {
        open(img.currentSrc || img.src, img.alt);
      });
      img.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          open(img.currentSrc || img.src, img.alt);
        }
      });
    });

    lbClose.addEventListener('click', close);
    lb.addEventListener('click', function (e) {
      if (e.target === lb) {
        close();
      }
    });
    doc.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && lb.classList.contains('is-open')) {
        close();
      }
    });
  }

  /* ---------------------------------------------------------
   * 启动
   * ------------------------------------------------------- */
  if (window.InkHighlight) {
    window.InkHighlight.init();
  }

  decorateCode();
  initLightbox();

  // 首屏按服务端已输出的配色点亮对应色卡，不写 cookie
  try {
    applyPalette(root.getAttribute('data-palette') || paletteDefault, false);
  } catch (e) {}

  requestScroll();

  window.addEventListener('load', requestScroll);
})();
