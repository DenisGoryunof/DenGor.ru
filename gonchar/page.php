<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$st = db()->prepare("SELECT * FROM pages WHERE slug=? AND is_published=1");
$st->execute([$slug]); $page = $st->fetch();
if (!$page) { http_response_code(404); exit('Страница не найдена'); }

$pageTitle = ($page['meta_title'] ?: $page['title']) . ' — ' . setting('site_name');
$metaDesc  = $page['meta_description'] ?: '';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container"><h1><?= e($page['title']) ?></h1></div>
</section>
<section class="section">
  <div class="container narrow rich"><?= $page['content'] /* доверенный HTML из админки */ ?></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>