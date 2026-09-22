<?php
$title = 'Категории';
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['do'] ?? '') === 'delete') {
        db()->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Категория удалена');
    } else {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            db()->prepare("INSERT INTO categories (name,slug,sort) VALUES (?,?,?)")
               ->execute([$name, unique_slug('categories', slugify($name)), (int)($_POST['sort'] ?? 0)]);
            flash('Категория добавлена');
        }
    }
    header('Location: categories.php'); exit;
}

require __DIR__ . '/_header.php';
$rows = db()->query("SELECT c.*, (SELECT COUNT(*) FROM works w WHERE w.category_id=c.id) AS cnt
                     FROM categories c ORDER BY c.sort, c.id")->fetchAll();
?>

<div class="grid-2">
  <div class="card">
    <h2>Список категорий</h2>
    <table class="table">
      <thead><tr><th>Название</th><th>Работ</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td class="muted"><?= (int)$r['cnt'] ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Удалить?')">
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
  <div class="card">
    <h2>Добавить категорию</h2>
    <form method="post">
      <?= csrf_field() ?>
      <label class="field"><span>Название</span><input type="text" name="name" required></label>
      <label class="field"><span>Сортировка</span><input type="number" name="sort" value="0"></label>
      <button class="btn-primary">Добавить</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>