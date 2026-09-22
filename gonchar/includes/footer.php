</main>

<footer class="site-footer" id="contact">
  <div class="container footer-grid">
    <div>
      <h3><?= e(setting('contact_title', 'Связаться с нами')) ?></h3>
      <p class="muted"><?= e(setting('address')) ?></p>
      <p class="muted"><?= e(setting('work_hours')) ?></p>
    </div>
    <div>
      <h4>Контакты</h4>
      <?php if (setting('phone')): ?><p><a href="tel:<?= e(preg_replace('~\D~','',setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p><?php endif; ?>
      <?php if (setting('email')): ?><p><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p><?php endif; ?>
      <p class="socials">
        <?php if (setting('instagram')): ?><a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
        <?php if (setting('telegram')): ?><a href="<?= e(setting('telegram')) ?>" target="_blank" rel="noopener">Telegram</a><?php endif; ?>
        <?php if (setting('whatsapp')): ?><a href="<?= e(setting('whatsapp')) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
      </p>
    </div>
    <div>
      <h4>Разделы</h4>
      <p><a href="/works.php">Все работы</a></p>
      <?php foreach (menu_pages() as $p): ?>
        <p><a href="/page.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></p>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="container footer-bottom"><?= e(setting('footer_text')) ?></div>
</footer>

<script src="/assets/js/admin.js"></script>
</body>
</html>