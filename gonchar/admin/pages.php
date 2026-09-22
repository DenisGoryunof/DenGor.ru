<?php
$title = 'Страницы';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'delete') {
    csrf_check();
    db()->prepare("DELETE FROM pages WHERE id=?")->execute([(int)$_POST['id']]);
    flash('Страница удалена'); header('Location: pages.php'); exit;
}
require __DIR__ . '/_header.php';

$rows = db()->query("SELECT * FROM pages ORDER BY sort, id")->fetchAll();
$actions = '<a href="page_edit.php" class="btn-sm btn-primary">+ Новая страница</a>';
?>

<div class="card">
<table class="table">
  <thead><tr><th>Название</th><th>URL</th><th>В меню</th><th>Статус</th><th>Обновлено</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="page_edit.php?id=<?= $r['id'] ?>" class="strong"><?= e($r['title']) ?></a></td>
      <td class="muted">/page.php?slug=<?= e($r['slug']) ?></td>
      <td><?= $r['show_in_menu'] ? '✓' : '—' ?></td>
      <td><span class="pill pill-<?= $r['is_published']?'published':'draft' ?>"><?= $r['is_published']?'видна':'скрыта' ?></span></td>
      <td class="muted"><?= e($r['updated_at']) ?></td>
      <td class="actions">
        <a href="/page.php?slug=<?= e($r['slug']) ?>" target="_blank">↗</a>
        <a href="page_edit.php?id=<?= $r['id'] ?>">✎</a>
        <form method="post" onsubmit="return confirm('Удалить страницу?')">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="delete">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button class="link-danger">✕</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php require __DIR__ . '/_footer.php'; ?>