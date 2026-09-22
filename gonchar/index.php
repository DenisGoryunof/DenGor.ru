<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = setting('site_name');
$metaDesc  = setting('hero_subtitle');

$masters = db()->query("SELECT * FROM masters WHERE is_active=1 ORDER BY sort,id")->fetchAll();
$featured = db()->query("
    SELECT w.*, m.name AS master_name, m.slug AS master_slug
    FROM works w LEFT JOIN masters m ON m.id = w.master_id
    WHERE w.status='published' AND w.is_featured=1
    ORDER BY w.sort, w.id DESC LIMIT 6
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container hero-inner">
    <div class="hero-text">
      <span class="eyebrow"><?= e(setting('site_tagline')) ?></span>
      <h1><?= e(setting('hero_title')) ?></h1>
      <p class="lead"><?= e(setting('hero_subtitle')) ?></p>
      <a href="/works.php" class="btn btn-primary"><?= e(setting('hero_button', 'Смотреть работы')) ?></a>
    </div>
    <div class="hero-image">
      <?php if (setting('hero_image')): ?>
        <img src="<?= e(img_url(setting('hero_image'))) ?>" alt="">
      <?php else: ?>
        <div class="hero-placeholder">🏺</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <header class="section-head">
      <h2><?= e(setting('masters_title')) ?></h2>
      <p class="muted"><?= e(setting('masters_subtitle')) ?></p>
    </header>
    <div class="masters-grid">
      <?php foreach ($masters as $m): ?>
        <a class="master-card" href="/master.php?slug=<?= e($m['slug']) ?>">
          <div class="master-photo">
            <?php if ($m['photo']): ?>
              <img src="<?= e(img_url($m['photo'])) ?>" alt="<?= e($m['name']) ?>">
            <?php else: ?>
              <span class="photo-placeholder"><?= e(mb_substr($m['name'],0,1)) ?></span>
            <?php endif; ?>
          </div>
          <div class="master-body">
            <h3><?= e($m['name']) ?></h3>
            <p class="muted small"><?= e($m['tagline']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section section-alt">
  <div class="container">
    <header class="section-head">
      <h2><?= e(setting('works_title')) ?></h2>
      <p class="muted"><?= e(setting('works_subtitle')) ?></p>
    </header>
    <div class="works-grid">
      <?php foreach ($featured as $w): ?>
        <?php include __DIR__ . '/includes/_work_card.php'; ?>
      <?php endforeach; ?>
    </div>
    <div class="center"><a href="/works.php" class="btn btn-ghost">Все работы →</a></div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container about-split">
    <div class="about-image">
      <?php if (setting('about_image')): ?>
        <img src="<?= e(img_url(setting('about_image'))) ?>" alt="">
      <?php else: ?><div class="hero-placeholder small">🤲</div><?php endif; ?>
    </div>
    <div>
      <h2><?= e(setting('about_title')) ?></h2>
      <p><?= nl2br(e(setting('about_text'))) ?></p>
      <a href="/page.php?slug=about" class="link-arrow">Подробнее о мастерской →</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>