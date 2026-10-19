<?php
/**
 * 404
 *
 * @package 墨 Ink
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>

<main>
  <div class="wrap">
    <div class="narrow err-wrap">
      <p class="err-code">4<span>0</span>4</p>
      <p class="err-text">这一页不在了。可能是链接写错，也可能它从来没存在过。<br>好在这里还有别的东西可看。</p>
      <a class="err-back" href="<?php $this->options->siteUrl(); ?>">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11.5 18.5 5 12l6.5-6.5"/></svg>
        <?php _e('回到首页'); ?>
      </a>
    </div>
  </div>
</main>

<?php $this->need('footer.php'); ?>
