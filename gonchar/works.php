<?php
require_once __DIR__ . '/includes/bootstrap.php';

$masterSlug = $_GET['master'] ?? '';
$catSlug    = $_GET['cat'] ?? '';

$where = ["w.status='published'"]; $params = [];
$join  = "LEFT JOIN masters m ON m.id=w.master_id LEFT JOIN categories c ON c.id=w.category_id";

if ($masterSlug) { $where[] = "m.slug = ?"; $params[] = $masterSlug; }
if ($catSlug)    { $where[] = "c.slug = ?"; $params[] = $catSlug; }

$sql = "SELECT w.*, m.name AS master_name, m.slug AS master_slug, c.name AS cat_name
        FROM works w $join WHERE " . implode(' AND ', $where) . "
        ORDER BY w.sort, w.id DESC";
$st = db()->prepare($sql); $st->execute($params);
$works = $st->fetchAll();

$masters    = db()->query("SELECT * FROM masters WHERE is_active=1 ORDER BY sort")->fetchAll();
$categories = db()->query("SELECT * FROM categories ORDER BY sort")->fetchAll();

$pageTitle = 'Работы — ' . setting('site_name');
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <h1><?= e(setting('works_title', 'Работы')) ?></h1>
    <p class="lead muted"><?= e(setting('works_subtitle')) ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="filters">
      <a href="/works.php" class="chip <?= !$masterSlug && !$catSlug ? 'active':'' ?>">Все</a>
      <?php foreach ($masters as $m): ?>
        <a href="?master=<?= e($m['slug']) ?>" class="chip <?= $masterSlug===$m['slug']?'active':'' ?>"><?= e($m['name']) ?></a>
      <?php endforeach; ?>
      <span class="chip-sep"></span>
      <?php foreach ($categories as $c): ?>
        <a href="?cat=<?= e($c['slug']) ?>" class="chip <?= $catSlug===$c['slug']?'active':'' ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$works): ?>
      <p class="muted center">Ничего не найдено.</p>
    <?php else: ?>
      <div class="works-grid">
        <?php foreach ($works as $w): include __DIR__ . '/includes/_work_card.php'; endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>