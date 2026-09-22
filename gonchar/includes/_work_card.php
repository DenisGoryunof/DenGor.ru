<article class="work-card">
  <a href="/work.php?id=<?= (int)$w['id'] ?>" class="work-image">
    <?php if ($w['image']): ?>
      <img src="<?= e(img_url($w['image'])) ?>" alt="<?= e($w['title']) ?>" loading="lazy">
    <?php else: ?><span class="photo-placeholder">🏺</span><?php endif; ?>
    <?php if ($w['is_sold']): ?><span class="badge badge-sold">Продано</span><?php endif; ?>
  </a>
  <div class="work-body">
    <h3><a href="/work.php?id=<?= (int)$w['id'] ?>"><?= e($w['title']) ?></a></h3>
    <p class="work-meta">
      <?php if (!empty($w['master_name'])): ?>
        <a href="/master.php?slug=<?= e($w['master_slug']) ?>"><?= e($w['master_name']) ?></a>
      <?php endif; ?>
    </p>
    <?php if ($w['price']): ?>
      <p class="work-price"><?= number_format((float)$w['price'], 0, '.', ' ') ?> ₽</p>
    <?php endif; ?>
  </div>
</article>