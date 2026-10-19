<?php
/**
 * 分类 / 标签 / 搜索 / 作者归档
 *
 * @package 墨 Ink
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');

$descText = '';
if (method_exists($this, 'getDescription')) {
    ob_start();
    $this->getDescription();
    $descText = trim(strip_tags(ob_get_clean()));
}
?>

<div class="wrap">
  <header class="idx-head">
    <h1 class="idx-title">
      <?php $this->archiveTitle(array(
          'category' => '<span class="section-tag">分类</span>%s',
          'search'   => '<span class="section-tag">搜索</span>%s',
          'tag'      => '<span class="section-tag">标签</span>%s',
          'author'   => '%s 的文章'
      ), '', ''); ?>
    </h1>
    <p class="idx-meta">
      共 <b><?php echo $this->getTotal(); ?></b> 篇
      <?php if ($descText !== ''): ?>
        &nbsp;·&nbsp; <?php echo htmlspecialchars($descText, ENT_QUOTES, 'UTF-8'); ?>
      <?php endif; ?>
    </p>
  </header>

  <?php if ($this->have()): ?>
    <ul class="archive-list">
      <?php while ($this->next()): ?>
        <li>
          <a class="archive-item" href="<?php $this->permalink(); ?>">
            <span class="day"><?php $this->date('Y.m.d'); ?></span>
            <span class="ttl"><?php $this->title(); ?></span>
          </a>
        </li>
      <?php endwhile; ?>
    </ul>

    <?php $this->pageNav('上一页', '下一页', 1, '…'); ?>
  <?php else: ?>
    <p class="empty">这里什么都没有。</p>
  <?php endif; ?>
</div>

<?php $this->need('footer.php'); ?>
