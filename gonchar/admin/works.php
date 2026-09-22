<?php
$title = 'Работы';

// Удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)$_POST['id'];
    $st = db()->prepare("SELECT image FROM works WHERE id=?"); $st->execute([$id]);
    $img = $st->fetchColumn();
    db()->prepare("DELETE FROM works WHERE id=?")->execute([$id]);
    flash('Работа удалена');
    header('Location: works.php'); exit;
}

require __DIR__ . '/_header.php';

$status    = $_GET['status'] ?? '';
$masterId  = (int)($_GET['master'] ?? 0);
$q         = trim($_GET['q'] ?? '');

$where = ['1=1']; $params = [];
if ($status) { $where[] = "w.status = ?"; $params[] = $status; }
if ($masterId) { $where[] = "w.master_id = ?"; $params[] = $masterId; }
if ($q !== '') { $where[] = "w.title LIKE ?"; $params[] = "%$q%"; }

$st = db()->prepare("SELECT w.*, m.name AS master_name, c.name AS cat_name
                     FROM works w
                     LEFT JOIN masters m ON m.id=w.master_id
                     LEFT JOIN categories c ON c.id=w.category_id
                     WHERE " . implode(' AND ', $where) . "
                     ORDER BY w.sort, w.id DESC");
$st->execute($params);
$works = $st->fetchAll();

$masters = db()->query("SELECT id,name FROM masters ORDER BY sort")->fetchAll();
$actions = '<a href="work_edit.php" class="btn-sm btn-primary">+ Новая работа</a>';
$title = 'Работы';
?>

<form class="toolbar" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Поиск по названию…">
  <select name="master" onchange="this.form.submit()">
    <option value="">Все мастера</option>
    <?php foreach ($masters as $m): ?>
      <option value="<?= $m['id'] ?>" <?= $masterId==$m['id']?'selected':'' ?>><?= e($m['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status" onchange="this.form.submit()">
    <option value="">Все статусы</option>
    <option value="published" <?= $status==='published'?'selected':'' ?>>Опубликованные</option>
    <option value="draft" <?= $status==='draft'?'selected':'' ?>>Черновики</option>
  </select>
  <button class="btn-sm">Применить</button>
</form>

<div class="card">
<table class="table table-works">
  <thead>
    <tr><th></th><th>Название</th><th>Мастер</th><th>Категория</th><th>Цена</th><th>Статус</th><th>Действия</th></tr>
  </thead>
  <tbody>
  <?php foreach ($works as $w): ?>
    <tr>
      <td class="thumb-cell">
        <?php if ($w['image']): ?><img src="<?= e(img_url($w['image'])) ?>" alt="">
        <?php else: ?><span class="no-img">🏺</span><?php endif; ?>
      </td>
      <td>
        <a href="work_edit.php?id=<?= $w['id'] ?>" class="strong"><?= e($w['title']) ?></a>
        <?php if ($w['is_featured']): ?><span class="pill pill-feat">на главной</span><?php endif; ?>
        <?php if ($w['is_sold']): ?><span class="pill pill-sold">продано</span><?php endif; ?>
      </td>
      <td class="muted"><?= e($w['master_name'] ?: '—') ?></td>
      <td class="muted"><?= e($w['cat_name'] ?: '—') ?></td>
      <td><?= $w['price'] ? number_format((float)$w['price'],0,'.',' ').' ₽' : '—' ?></td>
      <td><span class="pill pill-<?= $w['status'] ?>"><?= $w['status']==='published'?'опубл.':'черновик' ?></span></td>
      <td class="actions">
        <a href="/work.php?id=<?= $w['id'] ?>" target="_blank" title="Открыть">↗</a>
        <a href="work_edit.php?id=<?= $w['id'] ?>" title="Изменить">✎</a>
        <form method="post" onsubmit="return confirm('Удалить работу?')">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="delete">
          <input type="hidden" name="id" value="<?= $w['id'] ?>">
          <button type="submit" class="link-danger" title="Удалить">✕</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$works): ?>
    <tr><td colspan="7" class="muted center" style="padding:30px">Ничего не найдено</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php require __DIR__ . '/_footer.php'; ?>