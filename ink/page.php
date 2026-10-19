<?php
/**
 * 独立页面
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
      <h1 class="post-title"><?php $this->title(); ?></h1>
    </header>

    <div class="post-content content">
      <?php echo $body; ?>
    </div>
  </article>
</div>

<?php $this->need('footer.php'); ?>
