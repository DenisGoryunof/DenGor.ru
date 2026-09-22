<?php
$title = 'Медиабиблиотека';
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['do'] ?? '') === 'delete') {
        $id = (int)$_POST['id'];
        $st = db()->prepare("SELECT path FROM media WHERE id=?"); $st->execute([$id]);
        if ($p = $st->fetchColumn()) @unlink(UPLOADS_PATH . '/' . $p);
        db()->prepare("DELETE FROM media WHERE id=?")->execute([$id]);
        flash('Файл удалён');
    } elseif (!empty($_FILES['files']['name'][0])) {
        $n = 0;
        foreach ($_FILES['files']['name'] as $i => $name) {
            if (!$name) continue;
            $f = ['name'=>$name,'tmp_name'=>$_FILES['files']['tmp_name'][$i],
                  'error'=>$_FILES['files']['error'][$i],'size'=>$_FILES['files']['size'][$i]];
            if (upload_image($f)) $n++;
        }
        flash("Загружено файлов: $n");
    }
    header('Location: media.php'); exit;
}

require __DIR__ . '/_header.php';
$items = db()->query("SELECT * FROM media ORDER BY id DESC")->fetchAll();
?>

<form method="post" enctype="multipart/form-data" class="card upload-drop">
  <?= csrf_field() ?>
  <label class="file-btn big">📤 Загрузить изображения
    <input type="file" name="files[]" accept="image/*" multiple onchange="this.form.submit()">
  </label>
  <p class="hint">Можно выбрать несколько сразу. JPG, PNG, WEBP, GIF до 10 МБ.</p>
</form>

<div class="media-library">
  <?php foreach ($items as $m): ?>
    <div class="media-card">
      <img src="<?= e(img_url($m['path'])) ?>" alt="">
      <div class="media-info">
        <code>/uploads/<?= e($m['path']) ?></code>
        <button class="btn-sm copy" data-copy="/uploads/<?= e($m['path']) ?>">Копировать путь</button>
        <form method="post" onsubmit="return confirm('Удалить файл?')">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="delete">
          <input type="hidden" name="id" value="<?= $m['id'] ?>">
          <button class="link-danger">Удалить</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$items): ?><p class="muted">Пока нет загруженных файлов.</p><?php endif; ?>
</div>

<script>
document.querySelectorAll('.copy').forEach(b => b.addEventListener('click', () => {
  navigator.clipboard.writeText(location.origin + b.dataset.copy);
  b.textContent = 'Скопировано ✓';
  setTimeout(() => b.textContent = 'Копировать путь', 1500);
}));
</script>

<?php require __DIR__ . '/_footer.php'; ?>