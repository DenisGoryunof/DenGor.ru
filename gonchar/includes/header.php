<?php
$siteName = setting('site_name', 'Мастерская');
$pageTitle = $pageTitle ?? $siteName;
$metaDesc  = $metaDesc  ?? setting('site_tagline');
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="/" class="logo">
      <span class="logo-mark">◐</span>
      <span class="logo-text"><?= e($siteName) ?></span>
    </a>
    <button class="nav-toggle" aria-label="Меню">☰</button>
    <nav class="main-nav">
      <a href="/works.php">Работы</a>
      <?php foreach (menu_pages() as $p): ?>
        <a href="/page.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a>
      <?php endforeach; ?>
      <a href="/#contact">Контакты</a>
    </nav>
  </div>
</header>
<main>