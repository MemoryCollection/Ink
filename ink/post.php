<?php
/**
 * 文章页
 *
 * @package 墨 Ink
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');

$body = ink_post_body($this);
?>

<div class="wrap">
  <article class="post">
    <header class="post-head">
      <div class="post-titlerow">
        <h1 class="post-title"><?php $this->title(); ?></h1>
        <div class="post-meta">
          <time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y.m.d'); ?></time>
          <span class="dot" aria-hidden="true"></span>
          <span class="post-cat"><?php $this->category(' / ', true); ?></span>
          <span class="dot" aria-hidden="true"></span>
          <span class="post-wordcount"><?php echo ink_word_count($this); ?></span>
        </div>
      </div>
    </header>

    <div class="post-content content">
      <?php echo $body; ?>
    </div>

    <nav class="post-nav" aria-label="文章导航">
      <div class="nav-prev">
        <?php $this->thePrev(
          '<span class="dir">上一篇</span><span class="label">%s</span>',
          '<span class="dir">上一篇</span><span class="label muted">已经是第一篇了</span>'
        ); ?>
      </div>
      <div class="nav-next">
        <?php $this->theNext(
          '<span class="dir">下一篇</span><span class="label">%s</span>',
          '<span class="dir">下一篇</span><span class="label muted">已经是最后一篇了</span>'
        ); ?>
      </div>
    </nav>
  </article>
</div>

<?php $this->need('footer.php'); ?>
