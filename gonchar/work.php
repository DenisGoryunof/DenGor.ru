<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT w.*, m.name AS master_name, m.slug AS master_slug, m.tagline AS master_tagline,
                     c.name AS cat_name FROM works w
                     LEFT JOIN masters m ON m.id=w.master_id
                     LEFT JOIN categories c ON c.id=w.category_id
                     WHERE w.id=? AND w.status='published'");
$st->execute([$id]);
$w = $st->fetch();
if (!$w) { http_response_code(404); exit('Работа не найдена'); }

$photos = db()->prepare("SELECT * FROM work_photos WHERE work_id=? ORDER BY sort,id");
$photos->execute([$id]); $photos = $photos->fetchAll();

$others = db()->prepare("SELECT w.*, m.name AS master_name, m.slug AS master_slug FROM works w
                         LEFT JOIN masters m ON m.id=w.master_id
                         WHERE w.status='published' AND w.id<>? AND (w.master_id=? OR w.category_id=?)
                         ORDER BY RAND() LIMIT 3");
$others->execute([$id, $w['master_id'], $w['category_id']]);
$others = $others->fetchAll();

$pageTitle = $w['title'] . ' — ' . setting('site_name');
$metaDesc  = mb_substr(strip_tags($w['description'] ?? ''), 0, 160);
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container work-detail">
    <div class="work-gallery">
      <div class="main-photo">
        <?php if ($w['image']): ?><img id="mainPhoto" src="<?= e(img_url($w['image'])) ?>" alt="<?= e($w['title']) ?>">
        <?php else: ?><span class="photo-placeholder">🏺</span><?php endif; ?>
      </div>
      <?php if ($photos): ?>
        <div class="thumbs">
          <?php if ($w['image']): ?>
            <img src="<?= e(img_url($w['image'])) ?>" data-full="<?= e(img_url($w['image'])) ?>" class="thumb active">
          <?php endif; ?>
          <?php foreach ($photos as $p): ?>
            <img src="<?= e(img_url($p['path'])) ?>" data-full="<?= e(img_url($p['path'])) ?>" class="thumb">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="work-info">
      <?php if ($w['cat_name']): ?><span class="eyebrow"><?= e($w['cat_name']) ?></span><?php endif; ?>
      <h1><?= e($w['title']) ?></h1>
      <?php if ($w['master_name']): ?>
        <p class="work-author">Автор — <a href="/master.php?slug=<?= e($w['master_slug']) ?>"><?= e($w['master_name']) ?></a></p>
      <?php endif; ?>
      <div class="work-desc"><?= nl2br(e($w['description'])) ?></div>

      <dl class="specs">
        <?php if ($w['size']): ?><dt>Размер</dt><dd><?= e($w['size']) ?></dd><?php endif; ?>
        <?php if ($w['material']): ?><dt>Материал</dt><dd><?= e($w['material']) ?></dd><?php endif; ?>
      </dl>

      <?php if ($w['price']): ?>
        <p class="price-big"><?= number_format((float)$w['price'], 0, '.', ' ') ?> ₽</p>
      <?php endif; ?>

      <?php if ($w['is_sold']): ?>
        <p class="sold-note">Эта работа уже нашла хозяина. Можно заказать похожую.</p>
      <?php endif; ?>
      <a href="mailto:<?= e(setting('email')) ?>?subject=<?= rawurlencode('Заказ: '.$w['title']) ?>" class="btn btn-primary">Заказать</a>
    </div>
  </div>
</section>

<?php if ($others): ?>
<section class="section section-alt">
  <div class="container">
    <h2>Смотрите также</h2>
    <div class="works-grid">
      <?php foreach ($others as $w): include __DIR__ . '/includes/_work_card.php'; endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
document.querySelectorAll('.thumb').forEach(t => t.addEventListener('click', () => {
  document.getElementById('mainPhoto').src = t.dataset.full;
  document.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
  t.classList.add('active');
}));
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>