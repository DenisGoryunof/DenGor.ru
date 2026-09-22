<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$st = db()->prepare("SELECT * FROM masters WHERE slug=? AND is_active=1");
$st->execute([$slug]); $m = $st->fetch();
if (!$m) { http_response_code(404); exit('Мастер не найден'); }

$works = db()->prepare("SELECT w.*, m.name AS master_name, m.slug AS master_slug FROM works w
                        LEFT JOIN masters m ON m.id=w.master_id
                        WHERE w.master_id=? AND w.status='published' ORDER BY w.sort, w.id DESC");
$works->execute([$m['id']]); $works = $works->fetchAll();

$pageTitle = $m['name'] . ' — ' . setting('site_name');
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container master-hero">
    <div class="master-photo big">
      <?php if ($m['photo']): ?><img src="<?= e(img_url($m['photo'])) ?>" alt="<?= e($m['name']) ?>">
      <?php else: ?><span class="photo-placeholder"><?= e(mb_substr($m['name'],0,1)) ?></span><?php endif; ?>
    </div>
    <div>
      <h1><?= e($m['name']) ?></h1>
      <p class="lead"><?= e($m['tagline']) ?></p>
      <div class="rich"><?= nl2br(e($m['bio'])) ?></div>
      <?php if ($m['style']): ?>
        <p class="style-note"><b>Стиль:</b> <?= e($m['style']) ?></p>
      <?php endif; ?>
      <p class="socials">
        <?php if ($m['instagram']): ?><a href="<?= e($m['instagram']) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
        <?php if ($m['telegram']): ?><a href="<?= e($m['telegram']) ?>" target="_blank" rel="noopener">Telegram</a><?php endif; ?>
      </p>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <h2>Работы <?= e($m['name']) ?></h2>
    <?php if (!$works): ?>
      <p class="muted">Пока нет опубликованных работ.</p>
    <?php else: ?>
      <div class="works-grid">
        <?php foreach ($works as $w): include __DIR__ . '/includes/_work_card.php'; endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>