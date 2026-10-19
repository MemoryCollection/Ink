<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<footer class="site-foot">
  <div class="wrap foot-inner">
    <div class="foot-tip">
      <?php
      $footerText = ink_opt('footerText', '');
      if ($footerText !== '') {
          echo $footerText;
      } else {
          echo '&copy; ' . date('Y') . ' <a href="';
          $this->options->siteUrl();
          echo '">';
          $this->options->title();
          echo '</a>';
      }
      ?>
    </div>
    <?php $icp = ink_opt('icp', ''); ?>
    <?php if ($icp !== ''): ?>
      <div class="foot-links">
        <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow"><?php echo htmlspecialchars($icp, ENT_QUOTES, 'UTF-8'); ?></a>
      </div>
    <?php endif; ?>
  </div>
</footer>
</div><!-- /.sheet -->

<button class="to-top" id="toTop" type="button" aria-label="<?php _e('返回顶部'); ?>">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5.5 11.5 12 5l6.5 6.5"/></svg>
</button>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="<?php _e('图片预览'); ?>">
  <button class="lb-close" id="lbClose" type="button" aria-label="<?php _e('关闭'); ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
  </button>
  <img id="lbImage" alt="">
  <p class="lb-hint">ESC / 点击空白处关闭</p>
</div>

<script src="<?php echo ink_asset('assets/js/ink-highlight.js'); ?>"></script>
<script src="<?php echo ink_asset('assets/js/ink.js'); ?>"></script>
<!-- 墨 Ink 1.4.2 -->
<?php $this->footer(); ?>
</body>
</html>
