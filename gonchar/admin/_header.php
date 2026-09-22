<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
$user = current_user();
$current = basename($_SERVER['PHP_SELF']);

$nav = [
  'index.php'      => ['Дашборд', '▦'],
  'works.php'      => ['Работы', '🏺'],
  'masters.php'    => ['Мастера', '👤'],
  'categories.php' => ['Категории', '🏷'],
  'pages.php'      => ['Страницы', '📄'],
  'media.php'      => ['Медиа', '🖼'],
  'settings.php'   => ['Тексты сайта', '✎'],
];
$f = flash();
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Админка') ?> — <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="layout">

  <aside class="sidebar">
    <div class="brand"><span>◐</span> Мастерская</div>
    <nav>
      <?php foreach ($nav as $file => [$label, $icon]): ?>
        <a href="<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>">
          <span class="ico"><?= $icon ?></span><?= $label ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
      <a href="/" target="_blank"><span class="ico">↗</span>Открыть сайт</a>
      <a href="logout.php"><span class="ico">⎋</span>Выйти (<?= e($user['login']) ?>)</a>
    </div>
  </aside>

  <div class="content">
    <header class="topbar">
      <h1><?= e($title ?? '') ?></h1>
      <div class="topbar-actions"><?= $actions ?? '' ?></div>
    </header>

    <?php if ($f): ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>

    <div class="page">