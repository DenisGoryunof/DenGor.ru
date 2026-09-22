<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$p = ['slug'=>'','title'=>'','content'=>'','meta_title'=>'','meta_description'=>'',
      'is_published'=>1,'show_in_menu'=>0,'sort'=>0];

if ($id) {
    $st = db()->prepare("SELECT * FROM pages WHERE id=?"); $st->execute([$id]);
    $p = $st->fetch() ?: $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'title'            => trim($_POST['title'] ?? ''),
        'content'          => $_POST['content'] ?? '',
        'meta_title'       => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'is_published'     => isset($_POST['is_published']) ? 1 : 0,
        'show_in_menu'     => isset($_POST['show_in_menu']) ? 1 : 0,
        'sort'             => (int)($_POST['sort'] ?? 0),
    ];
    if ($data['title'] === '') { flash('Укажите заголовок', 'error'); }
    else {
        if ($id) {
            $data['id'] = $id;
            db()->prepare("UPDATE pages SET title=:title, content=:content, meta_title=:meta_title,
                meta_description=:meta_description, is_published=:is_published,
                show_in_menu=:show_in_menu, sort=:sort WHERE id=:id")->execute($data);
        } else {
            $data['slug'] = unique_slug('pages', slugify($data['title']));
            $cols = array_keys($data);
            db()->prepare("INSERT INTO pages (`" . implode('`,`',$cols) . "`) VALUES (:" . implode(',:',$cols) . ")")
               ->execute($data);
            $id = (int)db()->lastInsertId();
        }
        flash('Сохранено');
        header("Location: page_edit.php?id=$id"); exit;
    }
}

$title = $id ? 'Страница: ' . $p['title'] : 'Новая страница';
$actions = $id ? '<a href="/page.php?slug='.e($p['slug']).'" target="_blank" class="btn-sm">Открыть ↗</a>' : '';
require __DIR__ . '/_header.php';
?>

<form method="post" class="edit-grid">
  <?= csrf_field() ?>
  <div class="col-main">
    <div class="card">
      <label class="field"><span>Заголовок *</span>
        <input type="text" name="title" value="<?= e($p['title']) ?>" required></label>
      <label class="field"><span>Содержимое (можно HTML)</span>
        <textarea name="content" rows="18" class="mono"><?= e($p['content']) ?></textarea></label>
      <p class="hint">Используйте &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;, &lt;img&gt; — стили применятся автоматически.</p>
    </div>
  </div>
  <div class="col-side">
    <div class="card">
      <h2>Публикация</h2>
      <label class="check"><input type="checkbox" name="is_published" <?= $p['is_published']?'checked':'' ?>> Страница видна</label>
      <label class="check"><input type="checkbox" name="show_in_menu" <?= $p['show_in_menu']?'checked':'' ?>> Показывать в меню</label>
      <label class="field"><span>Сортировка</span><input type="number" name="sort" value="<?= (int)$p['sort'] ?>"></label>
      <button class="btn-primary btn-block">Сохранить</button>
    </div>
    <div class="card">
      <h2>SEO</h2>
      <label class="field"><span>Meta title</span><input type="text" name="meta_title" value="<?= e($p['meta_title']) ?>"></label>
      <label class="field"><span>Meta description</span>
        <textarea name="meta_description" rows="3"><?= e($p['meta_description']) ?></textarea></label>
    </div>
  </div>
</form>

<?php require __DIR__ . '/_footer.php'; ?>