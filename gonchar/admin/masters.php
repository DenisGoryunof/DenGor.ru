<?php
$title = 'Мастера';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'delete') {
    csrf_check();
    db()->prepare("DELETE FROM masters WHERE id=?")->execute([(int)$_POST['id']]);
    flash('Мастер удалён'); header('Location: masters.php'); exit;
}
require __DIR__ . '/_header.php';

$rows = db()->query("SELECT m.*, (SELECT COUNT(*) FROM works w WHERE w.master_id=m.id) AS works_count
                     FROM masters m ORDER BY m.sort, m.id")->fetchAll();
$actions = '<a href="master_edit.php" class="btn-sm btn-primary">+ Добавить мастера</a>';
?>

<div class="grid-cards">
  <?php foreach ($rows as $m): ?>
    <div class="card master-admin-card">
      <div class="ma-photo">
        <?php if ($m['photo']): ?><img src="<?= e(img_url($m['photo'])) ?>" alt="">
        <?php else: ?><span class="no-img big"><?= e(mb_substr($m['name'],0,1)) ?></span><?php endif; ?>
      </div>
      <div class="ma-body">
        <h3><?= e($m['name']) ?> <?= $m['is_active']?'':'<span class="pill pill-draft">скрыт</span>' ?></h3>
        <p class="muted small"><?= e($m['tagline']) ?></p>
        <p class="muted small">Работ: <b><?= (int)$m['works_count'] ?></b></p>
        <div class="actions">
          <a href="master_edit.php?id=<?= $m['id'] ?>" class="btn-sm">Изменить</a>
          <a href="/master.php?slug=<?= e($m['slug']) ?>" target="_blank" class="btn-sm">↗</a>
          <form method="post" onsubmit="return confirm('Удалить мастера?')">
            <?= csrf_field() ?>
            <input type="hidden" name="do" value="delete">
            <input type="hidden" name="id" value="<?= $m['id'] ?>">
            <button class="link-danger btn-sm">Удалить</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>