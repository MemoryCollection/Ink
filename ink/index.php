<?php
/**
 * 首页：全站文章目录
 *
 * @package 墨 Ink
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');

$stats  = ink_stats();
$recent = $this->widget('Widget_Contents_Post_Recent', 'pageSize=10000');
?>

<div class="wrap">
  <header class="idx-head">
    <h1 class="idx-title"><?php echo ink_hero_title($this->options); ?></h1>
    <p class="idx-meta">
      <?php if ($stats['count'] > 0): ?>
        <b><?php echo $stats['count']; ?></b> 篇 &nbsp;·&nbsp; 最近更新 <b><?php echo date('Y.m.d', $stats['latest']); ?></b>
      <?php else: ?>
        还没有文章
      <?php endif; ?>
    </p>
  </header>

  <?php if ($recent->next()): ?>
    <ul class="archive-list">
      <?php do { ?>
        <li>
          <a class="archive-item" href="<?php $recent->permalink(); ?>">
            <span class="day"><?php $recent->date('Y.m.d'); ?></span>
            <span class="ttl"><?php $recent->title(); ?></span>
          </a>
        </li>
      <?php } while ($recent->next()); ?>
    </ul>
  <?php else: ?>
    <p class="empty">这里还没有文章。</p>
  <?php endif; ?>
</div>

<?php $this->need('footer.php'); ?>
