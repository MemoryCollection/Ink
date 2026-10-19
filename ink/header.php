<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$inkPalette = ink_current_palette();
$inkDefault = ink_default_palette();
$inkPal = ink_palettes();
$inkDot = isset($inkPal[$inkPalette]) ? $inkPal[$inkPalette] : $inkPal[$inkDefault];
$inkAlign = ink_opt('align', 'left');
/* favicon 跟着主题配色走：同一个圆章，用当前配色的两个色值现画一份 */
$inkFavicon = 'data:image/svg+xml,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">'
    . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
    . '<stop offset="0" stop-color="' . $inkDot[1] . '"/><stop offset="1" stop-color="' . $inkDot[2] . '"/>'
    . '</linearGradient></defs>'
    . '<circle cx="100" cy="100" r="74" fill="none" stroke="url(#g)" stroke-width="9"/>'
    . '<path d="M62 34c-9 14-15 24-15 32a15 15 0 0 0 30 0c0-8-6-18-15-32z" fill="url(#g)"/>'
    . '<g fill="none" stroke="url(#g)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round">'
    . '<path d="M100 68h36"/><path d="M118 68v66"/><path d="M100 98h36"/><path d="M100 132h36"/></g>'
    . '<circle cx="62" cy="150" r="6.5" fill="url(#g)"/></svg>'
);
?>
<!DOCTYPE html>
<html lang="zh-CN"<?php if ($inkPalette !== $inkDefault): ?> data-palette="<?php echo $inkPalette; ?>"<?php endif; ?><?php if ($inkAlign === 'right' || $inkAlign === 'right-block'): ?> data-align="<?php echo $inkAlign; ?>"<?php endif; ?>>
<head>
<meta charset="<?php $this->options->charset(); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#f7faf9" id="themeColor">
<title><?php
if ($this->is('single')) {
    $this->title();
    echo ' · ';
    $this->options->title();
} elseif ($this->is('index')) {
    $this->options->title();
    $desc = ink_meta_desc($this);
    echo $desc !== '' ? ' · ' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') : '';
} else {
    $this->archiveTitle(array(
        'category' => '分类 %s',
        'search'   => '搜索 %s',
        'tag'      => '标签 %s',
        'author'   => '%s 的文章'
    ), '', ' · ');
    echo ' · ';
    $this->options->title();
}
?></title>
<meta name="description" content="<?php echo htmlspecialchars(ink_meta_desc($this), ENT_QUOTES, 'UTF-8'); ?>">
<?php if ($this->is('single')): ?>
<meta property="og:type" content="article">
<meta property="og:title" content="<?php $this->title(); ?>">
<meta property="og:site_name" content="<?php $this->options->title(); ?>">
<meta property="article:published_time" content="<?php echo date('c', $this->created); ?>">
<?php else: ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?php $this->options->title(); ?>">
<?php endif; ?>
<script>
(function () {
  var d = document.documentElement;
  var t;
  try {
    t = localStorage.getItem('ink-theme');
  } catch (e) {}
  if (t !== 'light' && t !== 'dark') {
    t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  d.setAttribute('data-theme', t);
})();
</script>
<link rel="stylesheet" href="<?php echo ink_asset('style.css'); ?>">
<?php
/*
 * 后台设置必须在 style.css 之后注入。
 *
 * 两处都写 :root { --fs-body: … } 时优先级相同，后出现的赢。
 * 之前这段 <style> 在 <link> 前面，于是后台改的字体、字号、行高、
 * 纸宽全被 style.css 里的默认值盖掉 —— 设置项看起来"没生效"。
 */
?>
<style>
<?php
/* 后台设置转成 CSS 变量：只输出真正被改动过的项，没动过就用 style.css 里的默认值 */
$inkVars = ink_css_vars();
echo $inkVars !== '' ? ":root {\n" . $inkVars . "}\n" : '';

/* 自定义强调色：覆盖配色方案里的 --accent，暗色下用同一个色值稍亮一点 */
$inkAccent = ink_custom_accent();
if ($inkAccent !== ''):
?>
:root {
  --accent: <?php echo $inkAccent; ?>;
  --accent-soft: <?php echo ink_hex_rgba($inkAccent, 0.09); ?>;
}
[data-theme="dark"] {
  --accent: <?php echo ink_hex_rgba($inkAccent, 1); ?>;
  --accent-soft: <?php echo ink_hex_rgba($inkAccent, 0.14); ?>;
}
<?php endif; ?>
</style>
<?php if (ink_ivicons()): ?>
<link rel="stylesheet" href="<?php echo ink_asset('assets/css/icons.css'); ?>">
<?php endif; ?>
<link rel="icon" href="<?php echo $inkFavicon; ?>" type="image/svg+xml">
<?php $this->header(); ?>
</head>
<body class="<?php echo $this->is('single') ? 'is-single' : ''; ?>">

<div class="sheet">
<div class="progress" aria-hidden="true"><span id="progressBar"></span></div>

<header class="site-head" id="siteHead">
  <div class="wrap head-inner">
    <a class="site-name" href="<?php $this->options->siteUrl(); ?>">
      <?php $this->need('assets/img/mark.php'); ?>
      <span class="site-word"><?php $this->options->title(); ?></span>
    </a>

    <nav class="site-nav" aria-label="主导航">
      <?php if (ink_opt('navCats', '1') === '1'): ?>
        <?php $this->widget('Widget_Metas_Category_List')->to($cats); ?>
        <?php while ($cats->next()): ?>
          <a href="<?php $cats->permalink(); ?>"<?php if ($this->is('category', $cats->slug)): ?> class="is-current" aria-current="page"<?php endif; ?>><?php $cats->name(); ?></a>
        <?php endwhile; ?>
      <?php endif; ?>
    </nav>

    <div class="head-tools">
      <?php $inkIcons = ink_ivicons(); ?>
      <?php if ($inkIcons): ?>
        <div class="head-icons">
          <?php foreach ($inkIcons as $icon): ?>
            <a class="head-icon" href="<?php echo $icon['url']; ?>" title="<?php echo $icon['label']; ?>" aria-label="<?php echo $icon['label']; ?>"<?php if (!empty($icon['blank'])): ?> target="_blank" rel="noopener nofollow"<?php endif; ?>><i class="czs-<?php echo $icon['name']; ?>" aria-hidden="true"></i></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="swatches" role="group" aria-label="<?php _e('外观'); ?>">
        <button class="swatch-pick" id="palettePick" type="button" aria-expanded="false" aria-label="<?php _e('配色与明暗'); ?>" title="<?php _e('配色与明暗'); ?>" data-default="<?php echo $inkDefault; ?>">
          <span class="sp-a" aria-hidden="true"></span><span class="sp-b" aria-hidden="true"></span>
        </button>
        <div class="swatch-panel" id="palettePanel">
          <?php foreach (ink_palettes() as $key => $info): ?>
            <button class="swatch" type="button" data-palette-set="<?php echo $key; ?>"<?php if ($key === $inkPalette): ?> aria-pressed="true"<?php endif; ?>>
              <span class="swatch-dot" style="background:linear-gradient(135deg,<?php echo $info[1]; ?>,<?php echo $info[2]; ?>)"></span>
              <span class="swatch-name"><?php echo $info[0]; ?></span>
            </button>
          <?php endforeach; ?>

          <div class="swatch-sep" aria-hidden="true"></div>
          <div class="mode-row" role="group" aria-label="<?php _e('明暗模式'); ?>">
            <button class="mode-btn" type="button" data-mode-set="light">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.6v2.2M12 19.2v2.2M2.6 12h2.2M19.2 12h2.2M5.3 5.3l1.6 1.6M17.1 17.1l1.6 1.6M18.7 5.3l-1.6 1.6M6.9 17.1l-1.6 1.6"/></svg>
              <span><?php _e('浅色'); ?></span>
            </button>
            <button class="mode-btn" type="button" data-mode-set="dark">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 14.6A8.6 8.6 0 0 1 9.4 3.5a8.6 8.6 0 1 0 11.1 11.1Z"/></svg>
              <span><?php _e('深色'); ?></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
