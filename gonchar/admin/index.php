<?php
$title = 'Дашборд';
require __DIR__ . '/_header.php';

$counts = [
    'works'   => db()->query("SELECT COUNT(*) FROM works")->fetchColumn(),
    'pub'     => db()->query("SELECT COUNT(*) FROM works WHERE status='published'")->fetchColumn(),
    'draft'   => db()->query("SELECT COUNT(*) FROM works WHERE status='draft'")->fetchColumn(),
    'masters' => db()->query("SELECT COUNT(*) FROM masters")->fetchColumn(),
    'pages'   => db()->query("SELECT COUNT(*) FROM pages")->fetchColumn(),
    'media'   => db()->query("SELECT COUNT(*) FROM media")->fetchColumn(),
];

$recent = db()->query("SELECT w.id,w.title,w.image,w.status,w.created_at,m.name AS master
                       FROM works w LEFT JOIN masters m ON m.id=w.master_id
                       ORDER BY w.id DESC LIMIT 8")->fetchAll();

$perMaster = db()->query("SELECT m.name, COUNT(w.id) AS cnt FROM masters m
                          LEFT JOIN works w ON w.master_id=m.id GROUP BY m.id ORDER BY m.sort")->fetchAll();
?>

<div class="stats">
  <a class="stat" href="works.php"><span class="stat-num"><?= $counts['pub'] ?></span><span class="stat-label">Опубликовано работ</span></a>
  <a class="stat" href="works.php?status=draft"><span class="stat-num"><?= $counts['draft'] ?></span><span class="stat-label">Черновиков</span></a>
  <a class="stat" href="masters.php"><span class="stat-num"><?= $counts['masters'] ?></span><span class="stat-label">Мастера</span></a>
  <a class="stat" href="pages.php"><span class="stat-num"><?= $counts['pages'] ?></span><span class="stat-label">Страницы</span></a>
  <a class="stat" href="media.php"><span class="stat-num"><?= $counts['media'] ?></span><span class="stat-label">Фото в медиа</span></a>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><h2>Последние работы</h2><a href="work_edit.php" class="btn-sm btn-primary">+ Добавить</a></div>
    <table class="table">
      <thead><tr><th></th><th>Название</th><th>Мастер</th><th>Статус</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td class="thumb-cell">
            <?php if ($r['image']): ?><img src="<?= e(img_url($r['image'])) ?>" alt="">
            <?php else: ?><span class="no-img">🏺</span><?php endif; ?>
          </td>
          <td><?= e($r['title']) ?></td>
          <td class="muted"><?= e($r['master'] ?: '—') ?></td>
          <td><span class="pill pill-<?= $r['status'] ?>"><?= $r['status']==='published'?'опубл.':'черновик' ?></span></td>
          <td><a href="work_edit.php?id=<?= (int)$r['id'] ?>" class="btn-sm">Изменить</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-head"><h2>Работы по мастерам</h2></div>
    <ul class="bar-list">
      <?php $max = max(1, max(array_column($perMaster ?: [['cnt'=>1]], 'cnt'))); ?>
      <?php foreach ($perMaster as $p): ?>
        <li>
          <span><?= e($p['name']) ?></span>
          <div class="bar"><i style="width:<?= round($p['cnt']/$max*100) ?>%"></i></div>
          <b><?= (int)$p['cnt'] ?></b>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="card-head" style="margin-top:28px"><h2>Быстрые ссылки</h2></div>
    <div class="quick">
      <a href="work_edit.php">+ Новая работа</a>
      <a href="page_edit.php">+ Новая страница</a>
      <a href="settings.php">✎ Тексты главной</a>
      <a href="media.php">🖼 Загрузить фото</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>