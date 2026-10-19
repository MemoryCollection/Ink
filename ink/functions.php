<?php
/**
 * 墨 Ink —— 主题函数
 *
 * @package 墨 Ink
 * @author Wu
 * @version 1.3.2
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/** 主题版本，仅用于前台注释标记 */
define('INK_VERSION', '1.4.2');

/* ---------------------------------------------------------
 * 兼容层：Typecho 1.2 用下划线类名，1.3 起命名空间类才是本体
 * ------------------------------------------------------- */
function ink_form_element($type, array $args)
{
    $candidates = array(
        'Typecho\\Widget\\Helper\\Form\\Element\\' . $type,
        'Typecho_Widget_Helper_Form_Element_' . $type,
    );

    foreach ($candidates as $class) {
        if (class_exists($class)) {
            $ref = new ReflectionClass($class);
            return $ref->newInstanceArgs($args);
        }
    }

    return null;
}

/**
 * 静态资源地址：带上文件修改时间做指纹
 *
 * 主题的 css / js 是就地改文件名不变的，如果 URL 里写死 ?v=1.0.0，
 * 浏览器和 CDN 会一直用旧文件，改完样式在线上看不到效果。
 */
function ink_asset($path)
{
    $options = Typecho_Widget::widget('Widget_Options');
    $path = ltrim($path, '/');
    $file = __DIR__ . '/' . $path;
    $ver  = is_file($file) ? filemtime($file) : false;

    $url = rtrim($options->themeUrl, '/') . '/' . $path;

    return htmlspecialchars($ver ? $url . '?v=' . $ver : $url, ENT_QUOTES, 'UTF-8');
}

/* ---------------------------------------------------------
 * 后台主题设置
 *
 * Typecho 的外观设置页只能一列到底地堆字段，字段一多就难找。
 * 这里在主题里自己画样式：按 section 分组、加折叠、章节标题、
 * 说明卡片和「当前外观」预览，不依赖后台是否支持自定义 HTML。
 * ------------------------------------------------------- */

/**
 * 设置项清单
 *
 * 每一项：array(type, name, default, label, desc[, choices])
 * 分组见 themeConfigHtml()，只影响显示，不影响取值。
 */
function ink_setting_fields()
{
    $palettes = array('auto' => _t('跟随访客（用访客上次选的）'));
    foreach (ink_palettes() as $key => $info) {
        $palettes[$key] = $info[0];
    }

    $fields = array(
        /* —— 首页 —— */
        array('Text', 'heroTitle', '', _t('首页大标题'),
            _t('显示在首页目录上方。留空则用站点描述，描述也空则用站点标题。'
                . '想让某几个字变成渐变强调色，用 &lt;em&gt; 包起来。')),
        array('Textarea', 'heroIntro', '', _t('站点描述'),
            _t('写进页面 description 标签，就是搜索结果里那两行摘要。留空则用后台「站点信息」里的描述。')),

        /* —— 外观 —— */
        array('Select', 'palette', 'graphite', _t('默认配色'),
            _t('访客第一次打开时用哪套。选「跟随访客」时，会记住访客上次选的（存在 cookie 里），没有记录就用石墨蓝。'),
            $palettes),
        array('Select', 'accentMode', 'palette', _t('强调色来源'),
            _t('「跟随配色」最稳；「自定义」可以填一个色值覆盖标题渐变、进度线、链接等所有强调色，适合想用品牌色的时候。'),
            array('palette' => _t('跟随配色方案'), 'custom' => _t('自定义色值'))),
        array('Text', 'accent', '', _t('自定义强调色'),
            _t('十六进制，例如 #4b5c6c。只在上面选了「自定义」时生效，留空等于不覆盖。')),
        array('Select', 'align', 'left', _t('首页标题对齐'),
            _t('标题区靠右会让页面多一次折返的阅读路径，左边留白更安静。手机上一律左对齐。'),
            array('left' => _t('左对齐（默认）'), 'right' => _t('靠右'), 'right-block' => _t('靠右 + 分隔线收短'))),
        array('Select', 'navCats', '1', _t('导航平铺分类'),
            _t('把全部分类铺在顶部导航里。分类多时导航可以横向滚动。'),
            array('1' => _t('显示'), '0' => _t('不显示'))),
        array('Text', 'siteEm', '', _t('标题强调词'),
            _t('填顶部站点标题里的某几个字，例如 Ink 或 墨，这几个字会换成配色的强调色（渐变）。'
                . '只影响页面顶部那一行，浏览器标题、分享卡片、RSS 都不受影响。留空则整行同色。')),

        /* —— 字体 —— */
        array('Select', 'fontPreset', 'serif', _t('字体风格'),
            _t('标题衬线、正文黑体是这套主题的默认气质。可以整体换成「全黑体」或「正文也用衬线」。'),
            array(
                'serif' => _t('标题衬线 + 正文黑体（默认）'),
                'sans'  => _t('全黑体'),
                'book'  => _t('全衬线（书页感）'),
                'mono'  => _t('标题等宽（极客感）'),
            )),
        array('Text', 'fontSerif', '', _t('自定义标题字体'),
            _t('填完整的 font-family 值，例如 "LXGW WenKai", "KaiTi", serif。留空用预设。')),
        array('Text', 'fontBody', '', _t('自定义正文字体'),
            _t('同上，作用于正文。留空用预设。')),

        /* —— 字号与版面 —— */
        array('Text', 'fontSize', '', _t('正文字号'),
            _t('只填数字，单位 px，默认 17。14 寸屏 1080p 下 16–17 比较舒服。')),
        array('Text', 'lineHeight', '', _t('正文行高'),
            _t('倍数，默认 1.9。中文长文建议 1.8–2.0。')),
        array('Text', 'sheetWidth', '', _t('纸张宽度'),
            _t('只填数字，单位 px，默认 840。含左右内边距。')),
        array('Text', 'sheetPad', '', _t('纸张内边距'),
            _t('只填数字，单位 px，默认 56。窄屏会自动缩小。')),

        /* —— 页脚 —— */
        array('Text', 'footerText', '', _t('页脚文字'),
            _t('显示在左下角，支持 HTML。留空则显示 © 年份 + 站点标题。')),
        array('Text', 'icp', '', _t('备案号'),
            _t('留空则不显示。例如：京ICP备00000000号，会自动链到工信部备案系统。')),
    );

    /* 导航右侧图标（最多 5 个，用草莓图标库） */
    for ($i = 1; $i <= 5; $i++) {
        $fields[] = array('Text', 'icon' . $i, '', sprintf(_t('图标 %d'), $i),
            $i === 1
                ? _t('填草莓图标库的名字，例如 github、rss、paper-plane、search-l，不用带 czs- 前缀。'
                    . '完整 380 枚见主题目录下的 icons.css。')
                : '');
        $fields[] = array('Text', 'iconUrl' . $i, '', sprintf(_t('图标 %d 链接'), $i),
            $i === 1 ? _t('必填，留空则这一项不渲染。只填链接、图标名留空时会按网址自动猜一个图标。') : '');
        $fields[] = array('Text', 'iconLabel' . $i, '', sprintf(_t('图标 %d 文字'), $i),
            _t('鼠标悬停与读屏用的说明，例如 GitHub。留空时用图标名。'));
        $fields[] = array('Select', 'iconBlank' . $i, '0', sprintf(_t('图标 %d 新窗口'), $i),
            '', array('1' => _t('是'), '0' => _t('否')));
    }

    return $fields;
}

/**
 * 设置项分组
 *
 * array(标题, 副标题, 字段名列表 | 'icon')
 * 列表项可以写成 array('小标题', array(字段…))，用来在一个分组里再分小节。
 */
function ink_setting_groups()
{
    return array(
        array('首页', '首屏第一眼看到的东西', array('heroTitle', 'heroIntro')),
        array('外观', '配色、强调色、标题对齐', array(
            array('配色', array('palette', 'accentMode', 'accent')),
            array('版式', array('align', 'navCats')),
            array('站点标题', array('siteEm')),
        )),
        array('字体', '标题与正文分别用什么字', array('fontPreset', 'fontSerif', 'fontBody')),
        array('字号与版面', '读起来舒不舒服全在这几项', array('fontSize', 'lineHeight', 'sheetWidth', 'sheetPad')),
        array('页脚', '版权与备案', array('footerText', 'icp')),
        array('导航图标', '最多 5 个，链接留空即不显示', 'icon'),
    );
}

/**
 * 后台设置页的样式，只在主题设置页生效
 *
 * 返回字符串而不是直接 echo：Typecho 的 Form::render() 只输出 $form->html，
 * 直接 echo 出来的内容会被表单缓冲丢掉。
 */
function ink_admin_style()
{
    return <<<'CSS'
<style id="ink-admin-style">
.ink-set { --ink-ink: #1d2321; --ink-2: #59645f; --ink-3: #8d9a94; --ink-line: #e3e8e5;
  --ink-teal: #46586a; --ink-teal-2: #5e86a1; --ink-bg: #f7f9fa;
  font-size: 13px; color: var(--ink-ink); }
.ink-set * { box-sizing: border-box; }

.ink-set .ink-hero {
  display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
  padding: 18px 20px; margin: 0 0 16px;
  border: 1px solid var(--ink-line); border-radius: 12px;
  background: linear-gradient(135deg, #f5f7f9, #fbfcfd 60%, #f5f7f9);
}
.ink-set .ink-hero svg { width: 34px; height: 34px; flex: none; }
.ink-set .ink-hero h2 { margin: 0; font-family: Georgia, "Songti SC", serif; font-size: 17px; font-weight: 600; }
.ink-set .ink-hero p { margin: 3px 0 0; font-size: 12px; color: var(--ink-3); }
.ink-set .ink-hero .ink-ver {
  margin-left: auto; padding: 3px 10px; border-radius: 99px;
  font-size: 11.5px; letter-spacing: .06em; color: var(--ink-teal);
  background: rgba(31, 122, 116, .1);
}

.ink-set details.ink-sec {
  margin: 0 0 10px; border: 1px solid var(--ink-line); border-radius: 12px;
  background: #fff; overflow: hidden;
}
.ink-set details.ink-sec[open] { box-shadow: 0 1px 2px rgba(0,0,0,.03); }
.ink-set details.ink-sec > summary {
  display: flex; align-items: baseline; gap: 10px;
  padding: 13px 18px; cursor: pointer; list-style: none; user-select: none;
  background: var(--ink-bg); border-bottom: 1px solid transparent;
}
.ink-set details.ink-sec[open] > summary { border-bottom-color: var(--ink-line); }
.ink-set details.ink-sec > summary::-webkit-details-marker { display: none; }
.ink-set details.ink-sec > summary::after {
  content: ""; margin-left: auto; width: 7px; height: 7px;
  border-right: 1.5px solid var(--ink-3); border-bottom: 1.5px solid var(--ink-3);
  transform: rotate(45deg) translateY(-2px); transition: transform .2s;
}
.ink-set details.ink-sec[open] > summary::after { transform: rotate(-135deg) translateY(-2px); }
.ink-set summary .ink-sec-t { font-size: 13.5px; font-weight: 600; }
.ink-set summary .ink-sec-d { font-size: 12px; color: var(--ink-3); }

.ink-set .ink-body { padding: 6px 18px 14px; }
.ink-set .ink-row {
  display: grid; grid-template-columns: 168px 1fr; gap: 6px 18px;
  padding: 13px 0; border-bottom: 1px dashed var(--ink-line);
}
.ink-set .ink-row:last-child { border-bottom: 0; }
.ink-set .ink-row > label { font-size: 13px; font-weight: 600; padding-top: 5px; }
.ink-set .ink-row .ink-input input[type=text],
.ink-set .ink-row .ink-input textarea,
.ink-set .ink-row .ink-input select {
  width: 100%; max-width: 520px; min-height: 32px;
  padding: 5px 9px; border: 1px solid #d7dedb; border-radius: 7px;
  font-size: 13px; color: var(--ink-ink); background: #fff;
  transition: border-color .2s, box-shadow .2s;
}
.ink-set .ink-row .ink-input input[type=text]:focus,
.ink-set .ink-row .ink-input textarea:focus,
.ink-set .ink-row .ink-input select:focus {
  border-color: var(--ink-teal); box-shadow: 0 0 0 3px rgba(70,88,106,.14); outline: none;
}
.ink-set .ink-row .ink-input textarea { min-height: 62px; resize: vertical; line-height: 1.7; }
.ink-set .ink-row .description {
  grid-column: 2; margin: 2px 0 0; font-size: 12px; line-height: 1.75; color: var(--ink-3);
}
.ink-set .ink-row .description code { font-size: 11.5px; padding: 1px 4px; border-radius: 3px; background: #f0f3f2; }

.ink-set .ink-sub {
  grid-column: 1 / -1; margin: 4px 0 2px; font-size: 12px; font-weight: 600;
  letter-spacing: .08em; color: var(--ink-teal);
}

.ink-set .ink-now {
  display: flex; flex-wrap: wrap; gap: 6px 22px;
  padding: 11px 16px; margin: 0 0 14px;
  border: 1px dashed var(--ink-line); border-radius: 10px; background: #fff;
  font-size: 12.5px;
}
.ink-set .ink-now-row { display: flex; align-items: center; gap: 7px; }
.ink-set .ink-now-k { color: var(--ink-3); }
.ink-set .ink-now-x { color: var(--ink-2); }
.ink-set .ink-now b { font-weight: 600; }
.ink-set .ink-now-dot {
  width: 13px; height: 13px; border-radius: 50%; display: block;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,.08);
}

.ink-set .ink-sub-line {
  display: flex; align-items: baseline; gap: 9px;
  margin: 16px 0 2px; padding-top: 14px;
  border-top: 1px solid var(--ink-line);
  font-size: 12px;
}
.ink-set .ink-sub-line:first-child { margin-top: 6px; padding-top: 0; border-top: 0; }
.ink-set .ink-sub-line .ink-sub { margin: 0; }
.ink-set .ink-sub-line .ink-sub-s {
  padding: 1px 7px; border-radius: 99px; font-size: 11px;
  color: var(--ink-teal); background: rgba(70,88,106,.09);
}

.ink-set .ink-tip {
  margin: 14px 0 0; padding: 12px 15px; border-radius: 9px;
  font-size: 12px; line-height: 1.85; color: var(--ink-2);
  background: rgba(31, 122, 116, .06); border: 1px solid rgba(31, 122, 116, .14);
}
.ink-set .ink-tip b { color: var(--ink-teal); }
.ink-set .ink-swatches { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.ink-set .ink-swatches i {
  width: 22px; height: 22px; border-radius: 50%; display: block;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,.08);
}
@media (max-width: 782px) {
  .ink-set .ink-row { grid-template-columns: 1fr; }
  .ink-set .ink-row .description { grid-column: 1; }
}

/* ---- 原生控件的回收样式 ----
   真正的 <li class="typecho-option"> 在 PHP 阶段 render() 到 #ink-cfg-forms 里，
   再由下面那段 JS 按 data-field 搬进分组。搬完 JS 会给它加 .is-busy 收起来；
   JS 没跑（或抛错）时它就是一块正常的原生表单，不会把字段藏没。 */
.ink-cfg-forms:not(.is-busy) {
  margin: 0 0 12px; padding: 14px 18px;
  border: 1px dashed var(--ink-line); border-radius: 12px; background: #fff;
}
.ink-cfg-forms:not(.is-busy)::before {
  content: "原生表单（脚本未接管）";
  display: block; margin: 0 0 8px; font-size: 11.5px; letter-spacing: .08em;
  color: var(--ink-3);
}
.ink-cfg-forms.is-busy { display: none; }
.ink-set .ink-row ul.typecho-option { margin: 0; padding: 0; list-style: none; }
.ink-set .ink-row > li.typecho-option { margin: 0; }
.ink-set .ink-row .typecho-option .typecho-label { display: none; }
.ink-set .ink-row .typecho-option .description { grid-column: 2; }
.ink-set details.ink-sec .ink-row:last-child { border-bottom: 0; }

/* 提交按钮跟着我们的版心：单独一条，别被塞进某个分组里 */
.ink-set .ink-submit-row {
  display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
  margin: 18px 0 0; padding: 0;
}
.ink-set .ink-submit-row .typecho-option { margin: 0; padding: 0; list-style: none; }
.ink-set .ink-submit-row .btn { min-width: 108px; }
.ink-set .ink-submit-note { font-size: 12px; color: var(--ink-3); }
</style>
CSS;
}

/**
 * 取字段的输入框 HTML
 *
 * Typecho 的表单元素不是「调用方法输出」，而是把控件放在公开属性 $element->input
 * （一个 Layout 对象）里，由 Form::render() 统一 render()。所以这里要渲染的是
 * 属性，不是方法 —— 直接调 $element->input() 会报「input 不是可调用的」。
 *
 * 元素造不出来时退回手写的纯表单控件，任何版本的后台都不会出现一整行空白。
 */
function ink_field_input($element, $field)
{
    if ($element !== null && isset($element->input) && is_object($element->input)
        && method_exists($element->input, 'render')) {
        ob_start();
        try {
            $element->input->render();
            $raw = trim(ob_get_clean());
            if ($raw !== '') {
                return $raw;
            }
        } catch (Exception $e) {
            ob_end_clean();
        } catch (Throwable $e) {
            ob_end_clean();
        }
    }

    return ink_fallback_input($field);
}

/**
 * 把一条已渲染好的字段塞进自己的行样式里
 *
 * $native 为真表示控件是 Typecho 原生的（页面上那枚 <li class="typecho-option">），
 * JS 会把它整个搬进这一行的 .ink-input；不再由 PHP 内联一份，避免出现重复的 name= 控件。
 * 但 .ink-input 这个落点无论哪条路都要留 —— JS 找不到它就搬不进来。
 *
 * @param array  $cell   array('input' => 控件 HTML, 'label' => 已转义标题, 'desc' => 说明)
 * @param string $name   字段名
 * @param bool   $native 走原生控件回收
 */
function ink_field_row($cell, $name, $native = false)
{
    $input = (!$native && $cell['input'] !== '')
        ? $cell['input']
        : '';
    $desc = $cell['desc'] !== '' ? '<p class="description">' . $cell['desc'] . '</p>' : '';

    return '<div class="ink-row" data-field="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">'
        . '<label>' . $cell['label'] . '</label>'
        . '<div class="ink-input">' . $input . '</div>'
        . $desc . '</div>';
}

/**
 * 把原生控件搬进分组的小脚本
 *
 * Typecho 输出的表单结构与我们的排版不一样，靠 JS 在浏览器里重排一次：
 * 每个 <li typecho-option> 里 <input|select|textarea name="x"> 的 x 就是字段名，
 * 按 data-field 找对应行，把控件挪进去。
 */
function ink_admin_script()
{
    return <<<'JS'
<script>
(function () {
  var root = document.getElementById('ink-set-root');
  var form = root && root.closest ? root.closest('form') : document.getElementById('theme');
  if (!root || !form) return;

  var rows = root.querySelectorAll('.ink-row[data-field]');
  var submitBox = null;

  /* 原生控件都 render() 进了这个容器，从这里取 */
  var pool = document.getElementById('ink-cfg-forms') || form;

  /*
   * Typecho 每个字段输出的是：
   *   <ul class="typecho-option" id="typecho-option-item-NAME-N">
   *     <li><label class="typecho-label">…</label><input name="NAME" …><p class="description">…</p></li>
   *   </ul>
   * 把整个 <ul> 搬走最省事，行里的说明我们自己另写一份更短的，
   * 所以搬进来之后把原生 .typecho-label 和 .description 收起来（样式里已隐藏）。
   */
  var natives = {};
  var lists = pool.querySelectorAll('ul.typecho-option');

  for (var i = 0; i < lists.length; i++) {
    var ul = lists[i];
    var field = ul.querySelector('[name]');
    /* 没有 name 的是「保存设置」按钮所在的那一条 */
    if (!field) { if (!submitBox) submitBox = ul; continue; }
    if (!natives[field.name]) natives[field.name] = ul;
  }

  /* 把控件塞进对应的行；找不到行的（比如主题以后加了新字段）挪到末尾，不丢 */
  for (var k = 0; k < rows.length; k++) {
    var row = rows[k];
    var name = row.getAttribute('data-field');
    if (!name || !natives[name]) continue;
    var holder = row.querySelector('.ink-input');
    if (holder) {
      var box = natives[name];
      /* 行里已经有我们写的标题和说明，原生那份摘掉，别重复一遍 */
      var strip = box.querySelectorAll('.typecho-label, .description');
      for (var s = 0; s < strip.length; s++) {
        if (strip[s].parentNode) strip[s].parentNode.removeChild(strip[s]);
      }
      holder.appendChild(box);
      natives[name] = null;
    }
  }

  /* 剩下没认领的原生控件，统一收在第一组里，保证不会丢字段 */
  var orphan = [];
  for (var key in natives) {
    if (natives[key]) orphan.push(natives[key]);
  }
  if (orphan.length) {
    var sec = root.querySelector('details.ink-sec');
    var body = sec ? sec.querySelector('.ink-body') : root.querySelector('.ink-set');
    if (body) {
      var box = document.createElement('div');
      box.className = 'ink-row';
      box.innerHTML = '<label>其它</label><div class="ink-input"></div>';
      var inner = box.querySelector('.ink-input');
      for (var j = 0; j < orphan.length; j++) inner.appendChild(orphan[j]);
      body.appendChild(box);
    }
  }

  /* 提交按钮单独一条，跟在设置卡下面 */
  if (submitBox) {
    var bar = document.createElement('div');
    bar.className = 'ink-submit-row';
    var note = document.createElement('span');
    note.className = 'ink-submit-note';
    note.textContent = '保存后前台立即生效。';
    bar.appendChild(submitBox);
    bar.appendChild(note);
    var set = root.querySelector('.ink-set');
    if (set) set.appendChild(bar);
  } else {
    /* 万一原生按钮没找到，别把人卡住：给一个提交本表单的按钮 */
    var set2 = root.querySelector('.ink-set');
    if (set2) {
      var bar2 = document.createElement('div');
      bar2.className = 'ink-submit-row';
      bar2.innerHTML = '<button type="submit" class="btn primary">保存设置</button>'
        + '<span class="ink-submit-note">保存后前台立即生效。</span>';
      set2.appendChild(bar2);
    }
  }

  /* 搬完了才收起原生容器；上面任何一步抛错，它就保持展开，字段依然可改 */
  if (pool.id === 'ink-cfg-forms') pool.className = 'ink-cfg-forms is-busy';
})();
</script>
JS;
}

/**
 * 元素没造出来时的兜底输入框
 *
 * 正常环境走不到这里；走到说明当前 Typecho 版本找不到表单元素类，
 * 与其整页空白不如给一个纯文本输入框。
 *
 * 注意取的是「已保存的值」而不是默认值：默认值只在第一次启用时写入，
 * 之后再改默认值不应该覆盖用户的设置。
 */
function ink_fallback_input($field)
{
    $name = $field[1];
    $options = Typecho_Widget::widget('Widget_Options');
    $saved = $options ? $options->{$name} : null;

    $current = ($saved === null || $saved === false) ? $field[2] : $saved;
    $value = htmlspecialchars((string) $current, ENT_QUOTES, 'UTF-8');

    if ($field[0] === 'Select' && isset($field[5])) {
        $html = '<select name="' . $name . '">';
        foreach ($field[5] as $k => $v) {
            $sel = ((string) $k === (string) $current) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
                . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        return $html . '</select>';
    }

    if ($field[0] === 'Textarea') {
        return '<textarea name="' . $name . '" rows="3">' . $value . '</textarea>';
    }

    return '<input type="text" name="' . $name . '" value="' . $value . '">';
}

/**
 * 把整页设置画成自己的样式
 *
 * 入口是 admin/options-theme.php 里的 `Config::alloc()->config()->render()`。
 * 渲染时只会输出 $form->html；addInput 进来的子元素被忽略。
 * 所以这里不能 echo，必须把 HTML 写回 $form->html，细节见函数末尾的注释。
 */
function themeConfigHtml($form, $options = null)
{
    $fields = array();
    $items = array();
    foreach (ink_setting_fields() as $field) {
        $fields[$field[1]] = $field;
        $items[] = $field[1];
    }

    $align = ink_opt('align', 'left');
    $paletteNow = ink_current_palette();
    $palList = ink_palettes();
    $palNow = isset($palList[$paletteNow]) ? $palList[$paletteNow] : reset($palList);

    /* 先把每个字段的控件与说明都跑一遍，按 addInput 的顺序攒成数组 */
    $cells = array();
    foreach ($items as $name) {
        $field = $fields[$name];
        $element = ink_make_element($field);
        $cells[$name] = array(
            'element' => $element,
            'input'   => ink_field_input($element, $field),
            'label'   => htmlspecialchars($field[3], ENT_QUOTES, 'UTF-8'),
            'desc'    => isset($field[4]) ? (string) $field[4] : '',
        );
    }

    /* 分组排版：$native 为真时控件由 Typecho 原生输出、JS 搬进行里，
       为假时（元素造不出来）才由 PHP 内联一份兜底控件 */
    $native = is_object($form) && method_exists($form, 'html');
    $body = '';
    $open = true;

    foreach (ink_setting_groups() as $group) {
        list($title, $sub, $keys) = $group;

        $rows = '';

        if ($keys === 'icon') {
            $rows .= '<div class="ink-tip">图标名取自 <b>草莓图标库</b>，例如 <code>github</code>、'
                . '<code>rss</code>、<code>paper-plane</code>、<code>search-l</code>。'
                . '链接留空这一项就不显示；只填链接、图标名留空时会按网址自动猜。</div>';

            for ($i = 1; $i <= 5; $i++) {
                $rows .= '<div class="ink-sub-line"><span class="ink-sub">图标 ' . $i . '</span>'
                    . '<span class="ink-sub-s">' . (ink_opt('iconUrl' . $i, '') !== '' ? '已启用' : '未启用')
                    . '</span></div>';
                foreach (array('icon' . $i, 'iconUrl' . $i, 'iconLabel' . $i, 'iconBlank' . $i) as $name) {
                    if (isset($cells[$name])) {
                        $rows .= ink_field_row($cells[$name], $name, $native);
                    }
                }
            }
        } else {
            foreach ($keys as $key) {
                if (is_array($key)) {
                    $rows .= '<div class="ink-sub-line"><span class="ink-sub">' . $key[0] . '</span></div>';
                    foreach ($key[1] as $name) {
                        if (isset($cells[$name])) {
                            $rows .= ink_field_row($cells[$name], $name, $native);
                        }
                    }
                } elseif (isset($cells[$key])) {
                    $rows .= ink_field_row($cells[$key], $key, $native);
                }
            }
        }

        $body .= '<details class="ink-sec"' . ($open ? ' open' : '') . '>'
            . '<summary><span class="ink-sec-t">' . $title . '</span>'
            . '<span class="ink-sec-d">' . $sub . '</span></summary>'
            . '<div class="ink-body">' . $rows . '</div></details>';
        $open = false;
    }

    /* 顶部：当前外观摘要 */
    $lhNow = trim((string) ink_opt('lineHeight', ''));
    $hero = '<div class="ink-hero">'
        . '<svg viewBox="0 0 200 200" aria-hidden="true"><circle cx="100" cy="100" r="74" fill="none" stroke="#46586a" stroke-width="8"/>'
        . '<path d="M62 34c-9 14-15 24-15 32a15 15 0 0 0 30 0c0-8-6-18-15-32z" fill="#46586a"/>'
        . '<g fill="none" stroke="#46586a" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M100 68h36"/><path d="M118 68v66"/><path d="M100 98h36"/><path d="M100 132h36"/></g>'
        . '<circle cx="62" cy="150" r="6.5" fill="#46586a"/></svg>'
        . '<div><h2>墨 Ink 主题设置</h2><p>改完点页面底部的「保存设置」。前台刷新即可看到，'
        . '样式表带文件时间戳，不用担心缓存。</p></div>'
        . '<span class="ink-ver">v' . INK_VERSION . '</span>'
        . '</div>';

    $now = '<div class="ink-now">'
        . '<div class="ink-now-row"><span class="ink-now-k">配色</span>'
        . '<i class="ink-now-dot" style="background:linear-gradient(135deg,' . $palNow[1] . ',' . $palNow[2] . ')"></i>'
        . '<b>' . htmlspecialchars($palNow[0], ENT_QUOTES, 'UTF-8') . '</b>'
        . '<span class="ink-now-x">'
        . ($align === 'left' ? '标题左对齐' : ($align === 'right' ? '标题靠右' : '标题靠右 + 短线'))
        . '</span></div>'
        . '<div class="ink-now-row"><span class="ink-now-k">正文</span>'
        . '<span class="ink-now-x">' . (ink_px_opt('fontSize', 13, 24) ?: 17) . 'px / 行高 '
        . htmlspecialchars($lhNow !== '' ? $lhNow : '1.9', ENT_QUOTES, 'UTF-8')
        . ' / 纸宽 ' . (ink_px_opt('sheetWidth', 560, 1280) ?: 840) . 'px'
        . ' / 页边 ' . (ink_px_opt('sheetPad', 16, 120) ?: 56) . 'px</span>'
        . '</div>'
        . '</div>';

    $foot = '<div class="ink-tip">想改字体文件、行间距这些更细的东西，'
        . '可以直接改 <b>style.css</b> 顶部的变量层，那里注释写得很清楚。'
        . '前台预览：主题目录下的 <b>preview.html</b>。</div>';

    /*
     * 注意 Typecho 的实际渲染方式（1.2 / 1.3 一致）：
     *
     *   admin/options-theme.php  →  Config::alloc()->config()->render()
     *   Layout::html() 一旦被赋过值，render() 就只 echo 这段 html，
     *   addInput 进来的子元素（每个是一个 <li class="typecho-option">）全部不再输出。
     *
     * 所以「画好看的设置页」不能光 echo 一段 HTML（会被丢掉），
     * 必须把内容写回 $form->html，render() 才会输出。
     *
     * 麻烦的地方：真正的 <input name="..."> 只有在元素 render() 时才生成，
     * PHP 阶段拿不到。所以分两步：
     *
     *  1. $form->html(...) 写整页自定义排版，每行留一个 .ink-input 落点；
     *     末尾留一个隐藏的 <div id="ink-cfg-forms">。
     *  2. 在写入 $form->html 之后、render() 之前，手动把每个元素 render()
     *     到缓冲区里，塞进那个隐藏容器 —— 这些才是带 name（和当前值）的真控件，
     *     JS 再按 data-field 把它们搬进对应行。不搬也不会丢，只是难看。
     *
     * 元素本身仍留在 $form->inputs 里，getAllRequest() 保存逻辑完全不变。
     * JS 被禁用时，隐藏容器会被 CSS 显示出来，退化成原生一列式表单，照样能设置。
     */
    if (is_object($form) && method_exists($form, 'html')) {
        $hidden = '';
        foreach ($cells as $name => $cell) {
            $element = $cell['element'];
            if (!is_object($element) || !method_exists($element, 'render')) {
                continue;
            }
            ob_start();
            try {
                $element->render();
                $hidden .= trim(ob_get_clean());
            } catch (Exception $e) {
                ob_end_clean();
            } catch (Throwable $e) {
                ob_end_clean();
            }
        }

        $html = ink_admin_style()
            . '<div class="ink-set-wrap" id="ink-set-root"><div class="ink-set">'
            . $hero . $now . $body . $foot
            . '</div></div>'
            . '<div id="ink-cfg-forms" class="ink-cfg-forms">' . $hidden . '</div>'
            . ink_admin_script();

        $form->html($html);
        return;
    }

    /* 兜底：万一调用的不是标准 Form（某些分支版本），至少把内容打印出来 */
    echo ink_admin_style()
        . '<div class="ink-set">' . $hero . $now . $body . $foot . '</div>';
}

/** 按字段定义造一个 Typecho 表单元素（供自定义排版复用） */
function ink_make_element($field)
{
    $type = $field[0];
    $name = $field[1];
    $label = $field[3];
    $desc  = isset($field[4]) ? $field[4] : '';

    if ($type === 'Select') {
        $choices = isset($field[5]) && is_array($field[5]) ? $field[5] : array();
        // Select 的构造签名是 (name, options, value, label, description)
        $args = array($name, $choices, $field[2], $label, $desc);
    } else {
        // 其余元素是 (name, options, value, label, description)，options 恒为 null
        $args = array($name, null, $field[2], $label, $desc);
    }

    $element = ink_form_element($type, $args);

    /*
     * 建好元素就顺手灌上当前值。
     *
     * 平时 Config::config() 会替我们 value() 一遍，但我们拿元素经常是在
     * 那个循环之后（比如自定义排版里重新造元素），不补这一下就会显示默认值。
     */
    if ($element !== null) {
        // 老版本里 Widget_Options 可能还没进栈，取不到就当没存过
        $options = Typecho_Widget::widget('Widget_Options');
        $saved = $options ? $options->{$name} : null;
        if ($saved !== null && $saved !== false && $saved !== '') {
            $element->value($saved);
        }
    }

    return $element;
}

/**
 * 注册设置
 *
 * 保留 $form->addInput()，保存逻辑和升级兼容都靠它；
 * themeConfigHtml() 只负责把页面画好看。
 */
function themeConfig($form)
{
    foreach (ink_setting_fields() as $field) {
        $element = ink_make_element($field);
        if ($element) {
            $form->addInput($element);
        }
    }
}

/* ---------------------------------------------------------
 * 工具函数
 * ------------------------------------------------------- */

/** 读取主题选项，空值回落到默认值 */
function ink_opt($key, $default = '')
{
    $options = Typecho_Widget::widget('Widget_Options');
    $value = $options->{$key};
    return ($value === null || $value === false || $value === '') ? $default : $value;
}

/** 校验后台填写的强调色，非法返回空串 */
function ink_accent()
{
    $accent = ink_opt('accent', '');
    return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $accent)
        ? $accent
        : '';
}

/** 实际生效的强调色：只有选了「自定义」且色值合法才覆盖 */
function ink_custom_accent()
{
    if (ink_opt('accentMode', 'palette') !== 'custom') {
        return '';
    }
    return ink_accent();
}

/**
 * 顶部那行站点标题
 *
 * 后台「标题强调词」里填的几个字会被包成 <span class="site-em">，用配色的强调色画成渐变。
 * 之所以不让人在「站点标题」里直接写 <em>：那个值还要进 <title>、og:site_name 和 RSS，
 * 带标签会把它们弄坏。所以标题照样输出纯文本，只在渲染这一处插一层 span。
 */
function ink_site_word()
{
    $options = Typecho_Widget::widget('Widget_Options');
    $title = htmlspecialchars((string) $options->title, ENT_QUOTES, 'UTF-8');

    $word = trim((string) ink_opt('siteEm', ''));
    if ($word === '') {
        return $title;
    }

    /* 标题已经转义过了，强调词也按转义后的形态去找，& " < 这类字符才对得上 */
    $needle = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');
    if (strpos($title, $needle) === false) {
        return $title;
    }

    return str_replace($needle, '<span class="site-em">' . $needle . '</span>', $title);
}

/** 字体预设：返回 array(标题栈, 正文栈)，空串表示用 style.css 里的默认 */
function ink_font_presets()
{
    return array(
        'serif' => array('', ''),
        'sans'  => array(
            '-apple-system, BlinkMacSystemFont, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "Noto Sans SC", system-ui, sans-serif',
            '',
        ),
        'book'  => array(
            '"Noto Serif SC", "Source Han Serif SC", "Songti SC", "STSong", "SimSun", Georgia, serif',
            '"Noto Serif SC", "Source Han Serif SC", "Songti SC", "STSong", "SimSun", Georgia, serif',
        ),
        'mono'  => array(
            'ui-monospace, "SF Mono", "Cascadia Code", "JetBrains Mono", Consolas, Menlo, monospace',
            '',
        ),
    );
}

/** 只保留安全的 font-family 字符，防止把 CSS 写坏 */
function ink_safe_font($value)
{
    $value = trim((string) $value);
    if ($value === '' || strlen($value) > 200) {
        return '';
    }
    return preg_match('~^[A-Za-z0-9\s,"\'\-\_\.]+$~', $value) ? $value : '';
}

/** 取一个正整数设置（带上下限），没填返回 0 表示不覆盖 */
function ink_px_opt($key, $min, $max)
{
    $raw = trim((string) ink_opt($key, ''));
    if ($raw === '' || !is_numeric($raw)) {
        return 0;
    }
    $n = (int) round((float) $raw);
    if ($n < $min || $n > $max) {
        return 0;
    }
    return $n;
}

/**
 * 后台设置 → CSS 变量
 *
 * 字体、字号、行高、纸宽这些都做成变量覆盖，前台只在 <head> 里追加几条
 * :root 规则，不给样式表加分支。没填的项直接跳过，保持 style.css 的默认。
 */
function ink_css_vars()
{
    $out = '';

    $presets = ink_font_presets();
    $preset = ink_opt('fontPreset', 'serif');
    if (isset($presets[$preset])) {
        if ($presets[$preset][0] !== '') {
            $out .= '  --serif: ' . $presets[$preset][0] . ";\n";
        }
        if ($presets[$preset][1] !== '') {
            $out .= '  --sans: ' . $presets[$preset][1] . ";\n";
        }
    }

    $serif = ink_safe_font(ink_opt('fontSerif', ''));
    if ($serif !== '') {
        $out .= '  --serif: ' . $serif . ";\n";
    }
    $body = ink_safe_font(ink_opt('fontBody', ''));
    if ($body !== '') {
        $out .= '  --sans: ' . $body . ";\n";
    }

    $size = ink_px_opt('fontSize', 13, 24);
    if ($size > 0) {
        $out .= '  --fs-body: ' . $size . "px;\n";
    }

    $lh = trim((string) ink_opt('lineHeight', ''));
    if ($lh !== '' && is_numeric($lh)) {
        $lh = (float) $lh;
        if ($lh >= 1.3 && $lh <= 2.6) {
            $out .= '  --lh-body: ' . rtrim(rtrim(sprintf('%.2f', $lh), '0'), '.') . ";\n";
        }
    }

    $w = ink_px_opt('sheetWidth', 560, 1280);
    if ($w > 0) {
        $out .= '  --sheet-w: ' . $w . "px;\n";
    }

    $pad = ink_px_opt('sheetPad', 16, 120);
    if ($pad > 0) {
        $out .= '  --sheet-pad: ' . $pad . "px;\n";
    }

    return $out;
}

/** 可用的配色方案：键为 data-palette 值，值为前台显示名与两个色点 */
function ink_palettes()
{
    /* 顺序就是前台面板与后台下拉里的顺序，默认色排第一 */
    return array(
        'graphite' => array(_t('石墨蓝'), '#4b5c6c', '#5e86a1'),
        'rose'     => array(_t('玫瑰紫'), '#9c3a5e', '#cb526e'),
        'coral'    => array(_t('珊瑚橙'), '#cf5b30', '#e39b63'),
        'peacock'  => array(_t('孔雀青'), '#21757d', '#27bebc'),
        'abyss'    => array(_t('深海蓝'), '#23487b', '#297fbc'),
        'indigo'   => array(_t('靛蓝紫'), '#49418b', '#7f56bd'),
        'violet'   => array(_t('罗兰紫'), '#7d4c9a', '#b46bc2'),
    );
}

/** 默认配色：空白页（无 cookie）时使用 */
function ink_default_palette()
{
    return 'graphite';
}

/** 后台设定的配色：auto 表示跟随访客 cookie */
function ink_palette()
{
    $all = ink_palettes();
    $name = ink_opt('palette', 'graphite');

    if ($name === 'auto' || !isset($all[$name])) {
        return 'auto';
    }

    return $name;
}

/**
 * 访客当前配色
 *
 * 优先级：后台锁定的配色 > cookie 里访客上次的选择 > ink_default_palette()
 * cookie 名 ink_palette，由 ink.js 在切换时写入，有效期一年。
 */
function ink_current_palette()
{
    $all = ink_palettes();
    $fixed = ink_palette();

    if ($fixed !== 'auto') {
        return $fixed;
    }

    if (!empty($_COOKIE['ink_palette'])) {
        $picked = $_COOKIE['ink_palette'];
        if (isset($all[$picked])) {
            return $picked;
        }
    }

    return ink_default_palette();
}

/**
 * 导航右侧的自定义图标
 *
 * 后台最多填 5 组（图标名 / 链接 / 文字 / 是否新窗口）。
 * 链接为空即视为没设置，整项不渲染；图标名为空时按域名猜一个。
 * 图标名来自草莓图标库（Strawberry Icon），对应 <i class="czs-名字">。
 */
function ink_ivicons()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $list = array();

    for ($i = 1; $i <= 5; $i++) {
        $url = trim((string) ink_opt('iconUrl' . $i, ''));
        $raw = trim((string) ink_opt('icon' . $i, ''));

        if ($url === '') {
            continue;
        }

        // 只放行 http(s) / mailto / 站内相对地址
        if (!preg_match('~^(https?://|mailto:|/)~i', $url)) {
            continue;
        }

        $name = ink_icon_name($raw, $url);

        if ($name === '') {
            continue;
        }

        $label = trim((string) ink_opt('iconLabel' . $i, ''));

        $list[] = array(
            'name'  => $name,
            'url'   => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            'label' => htmlspecialchars($label !== '' ? $label : $name, ENT_QUOTES, 'UTF-8'),
            'blank' => ink_opt('iconBlank' . $i, '0') === '1',
        );
    }

    $cache = $list;
    return $cache;
}

/** 把后台写的图标名规整成 czs- 后面的部分；空的话按链接猜 */
function ink_icon_name($raw, $url)
{
    $raw = strtolower(trim($raw));

    if ($raw !== '') {
        // 允许直接填 czs-github 或 e60c 之类，统一去掉前缀
        $raw = preg_replace('~^\.?czs-~', '', $raw);
        if (preg_match('~^[a-z0-9-]+$~', $raw)) {
            return $raw;
        }
        return '';
    }

    $guess = array(
        'github.com'   => 'github',
        'weibo.com'    => 'weibo',
        'weibo.cn'     => 'weibo',
        'zhihu.com'    => 'zhihu',
        'bilibili.com' => 'bilibili',
        'twitter.com'  => 'twitter',
        'x.com'        => 'twitter',
        'youtube.com'  => 'youtube',
        'telegram'     => 'telegram',
        't.me'         => 'telegram',
        'qq.com'       => 'qq',
        'weixin'       => 'weixin',
        'wechat'       => 'weixin',
        'douban'       => 'read',
        'rss'          => 'rss',
        'feed'         => 'rss',
        'mailto:'      => 'paper-plane',
        'mail'         => 'paper-plane',
        'steam'        => 'steam',
        'wordpress'    => 'wordpress',
        'instagram'    => 'camera',
        'dribbble'     => 'dribbble',
        'pinterest'    => 'pinterest',
        'paypal'       => 'paypal',
        'alipay'       => 'alipay',
    );

    $lower = strtolower($url);
    foreach ($guess as $needle => $name) {
        if (strpos($lower, $needle) !== false) {
            return $name;
        }
    }

    return 'link-l';
}

/** 十六进制转 rgba() */function ink_hex_rgba($hex, $alpha = 0.08)
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) < 6) {
        return 'rgba(178,58,46,' . $alpha . ')';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
}

/** SEO 描述：文章取摘要，其它页取站点自述 */
function ink_meta_desc($archive)
{
    if ($archive->is('single')) {
        $text = ink_capture(function () use ($archive) {
            $archive->excerpt(150, ' ...');
        });
    } else {
        $text = ink_opt('heroIntro', '');
        if ($text === '') {
            $options = Typecho_Widget::widget('Widget_Options');
            $text = ink_capture(function () use ($options) {
                $options->description();
            });
        }
    }

    $text = strip_tags($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim($text);

    if (function_exists('mb_substr') && mb_strlen($text, 'UTF-8') > 160) {
        $text = mb_substr($text, 0, 160, 'UTF-8');
    }

    return $text;
}

/** 把元素的方法输出捕获成字符串（Typecho 的 permalink()/title() 都是直接 echo 的） */
function ink_capture($callback)
{
    ob_start();
    call_user_func($callback);
    return trim(ob_get_clean());
}

/**
 * 给正文中的 h2 / h3 补上稳定的 id，方便分享具体段落
 *
 * @return string
 */
function ink_build_toc($content)
{
    $index = 0;

    return preg_replace_callback(
        '#<h([23])([^>]*)>(.*?)</h\1>#is',
        function ($m) use (&$index) {
            $level = (int) $m[1];
            $attrs = $m[2];
            $inner = $m[3];
            $text  = trim(strip_tags($inner));

            if ($text === '') {
                return $m[0];
            }

            if (preg_match('#\bid\s*=\s*(["\'])([^"\']+)\1#i', $attrs, $idm)) {
                $id = $idm[2];
                $attrs = preg_replace('#\s*\bid\s*=\s*(["\'])[^"\']*\1#i', '', $attrs);
            } else {
                $index++;
                $id = 'sec-' . $index;
            }

            return '<h' . $level . ' id="' . $id . '"' . $attrs . '>' . $inner . '</h' . $level . '>';
        },
        $content
    );
}

/** 正文图片：补懒加载属性 */
function ink_lazy_images($content)
{
    return preg_replace_callback('#<img\b([^>]*?)/?>#i', function ($m) {
        $attrs = $m[1];
        if (stripos($attrs, 'loading=') === false) {
            $attrs .= ' loading="lazy"';
        }
        if (stripos($attrs, 'decoding=') === false) {
            $attrs .= ' decoding="async"';
        }
        return '<img' . $attrs . '>';
    }, $content);
}

/**
 * 渲染文章正文
 *
 * @return string
 */
function ink_post_body($archive)
{
    $raw = ink_capture(function () use ($archive) {
        $archive->content();
    });

    return ink_lazy_images(ink_build_toc($raw));
}

/**
 * 文章字数：中文按字计，连续的西文按一个词计
 *
 * 只数正文，不含标签与代码以外的空白。返回如「约 1,240 字」。
 */
function ink_word_count($archive)
{
    $html = ink_capture(function () use ($archive) {
        $archive->content();
    });

    $text = preg_replace('~<(script|style|pre|code)\b[^>]*>.*?</\1>~is', ' ', $html);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('~\s+~u', ' ', $text);

    preg_match_all('~[\x{4e00}-\x{9fa5}]~u', $text, $han);
    preg_match_all('~[A-Za-z0-9]+(?:[\'’\-][A-Za-z0-9]+)*~', $text, $word);

    $n = count($han[0]) + count($word[0]);

    if ($n < 1) {
        return '';
    }

    return '约 ' . number_format($n) . ' 字';
}

/** 首页大标题：自定义 > 站点描述 > 站点标题 */function ink_hero_title($options)
{
    $custom = ink_opt('heroTitle');
    if ($custom !== '') {
        return $custom;
    }

    $desc = ink_capture(function () use ($options) {
        $options->description();
    });
    $desc = trim($desc);

    if ($desc !== '') {
        return htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
    }

    return htmlspecialchars($options->title, ENT_QUOTES, 'UTF-8');
}

/** 站点文章总数与最后更新时间（首页 hero 用） */
function ink_stats()
{
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();

    $count = 0;
    $latest = 0;

    try {
        $row = $db->fetchRow($db->select(array('COUNT(cid)' => 'num', 'MAX(created)' => 'last'))
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->where('created < ?', time()));
        if ($row) {
            $count  = (int) $row['num'];
            $latest = (int) $row['last'];
        }
    } catch (Exception $e) {
        // 表结构异常时静默降级
    }

    return array('count' => $count, 'latest' => $latest);
}
