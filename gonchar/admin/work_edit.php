<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$work = ['title'=>'','slug'=>'','master_id'=>'','category_id'=>'','description'=>'','price'=>'',
         'size'=>'','material'=>'','image'=>'','status'=>'published','is_featured'=>0,
         'is_sold'=>0,'sort'=>0];

if ($id) {
    $st = db()->prepare("SELECT * FROM works WHERE id=?"); $st->execute([$id]);
    $work = $st->fetch() ?: $work;
    if (!$work['id']) { http_response_code(404); exit('Не найдено'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $data = [
        'title'       => trim($_POST['title'] ?? ''),
        'master_id'   => (int)($_POST['master_id'] ?? 0) ?: null,
        'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
        'description' => $_POST['description'] ?? '',
        'price'       => $_POST['price'] !== '' ? (float)$_POST['price'] : null,
        'size'        => trim($_POST['size'] ?? ''),
        'material'    => trim($_POST['material'] ?? ''),
        'status'      => $_POST['status'] === 'draft' ? 'draft' : 'published',
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_sold'     => isset($_POST['is_sold']) ? 1 : 0,
        'sort'        => (int)($_POST['sort'] ?? 0),
    ];

    if ($data['title'] === '') {
        flash('Укажите название', 'error');
    } else {
        // главное фото
        if (!empty($_FILES['image']['name'])) {
            $up = upload_image($_FILES['image']);
            if ($up) $data['image'] = $up;
        }
        if (!empty($_POST['image_from_media'])) $data['image'] = $_POST['image_from_media'];

        // галерея
        $gallery = $_POST['gallery'] ?? [];   // массив путей из медиа
        if (!empty($_FILES['gallery']['name'][0])) {
            foreach ($_FILES['gallery']['name'] as $i => $n) {
                if (!$n) continue;
                $file = ['name'=>$n,'tmp_name'=>$_FILES['gallery']['tmp_name'][$i],
                         'error'=>$_FILES['gallery']['error'][$i],'size'=>$_FILES['gallery']['size'][$i]];
                $u = upload_image($file);
                if ($u) $gallery[] = $u;
            }
        }

        if ($id) {
            $sql = "UPDATE works SET title=:title, master_id=:master_id, category_id=:category_id,
                    description=:description, price=:price, size=:size, material=:material,
                    status=:status, is_featured=:is_featured, is_sold=:is_sold, sort=:sort"
                    . (isset($data['image']) ? ", image=:image" : "")
                    . " WHERE id=:id";
            $data['id'] = $id;
            db()->prepare($sql)->execute($data);
        } else {
            $data['slug'] = unique_slug('works', slugify($data['title']));
            $cols = array_keys($data);
            $sql = "INSERT INTO works (`" . implode('`,`', $cols) . "`) VALUES (:" . implode(',:', $cols) . ")";
            db()->prepare($sql)->execute($data);
            $id = (int)db()->lastInsertId();
        }

        // обновляем галерею
        db()->prepare("DELETE FROM work_photos WHERE work_id=?")->execute([$id]);
        $ins = db()->prepare("INSERT INTO work_photos (work_id,path,sort) VALUES (?,?,?)");
        foreach (array_values($gallery) as $i => $p) {
            if ($p) $ins->execute([$id, $p, $i]);
        }

        flash('Сохранено');
        header("Location: work_edit.php?id=$id"); exit;
    }
}

$masters    = db()->query("SELECT id,name FROM masters ORDER BY sort")->fetchAll();
$categories = db()->query("SELECT id,name FROM categories ORDER BY sort")->fetchAll();
$gallery    = $id ? db()->query("SELECT * FROM work_photos WHERE work_id=$id ORDER BY sort,id")->fetchAll() : [];
$media      = db()->query("SELECT * FROM media ORDER BY id DESC LIMIT 40")->fetchAll();

$title   = $id ? 'Редактирование работы' : 'Новая работа';
$actions = $id ? '<a href="/work.php?id='.$id.'" target="_blank" class="btn-sm">Открыть на сайте ↗</a>' : '';
require __DIR__ . '/_header.php';
?>

<form method="post" enctype="multipart/form-data" class="edit-grid">
  <?= csrf_field() ?>

  <div class="col-main">

    <div class="card">
      <label class="field">
        <span>Название *</span>
        <input type="text" name="title" value="<?= e($work['title']) ?>" required>
      </label>

      <label class="field">
        <span>Описание</span>
        <textarea name="description" rows="6"><?= e($work['description']) ?></textarea>
      </label>

      <div class="row-2">
        <label class="field">
          <span>Размер</span>
          <input type="text" name="size" value="<?= e($work['size']) ?>" placeholder="напр. h 28 см / 350 мл">
        </label>
        <label class="field">
          <span>Материал</span>
          <input type="text" name="material" value="<?= e($work['material']) ?>" placeholder="напр. шамот, глазурь">
        </label>
      </div>
    </div>

    <div class="card">
      <h2>Главное фото</h2>
      <div class="image-picker">
        <div class="image-preview" id="mainPreview">
          <?php if ($work['image']): ?>
            <img src="<?= e(img_url($work['image'])) ?>" alt="">
          <?php else: ?>
            <span class="no-img big">🏺</span>
          <?php endif; ?>
        </div>
        <div class="image-picker-side">
          <input type="hidden" name="image_from_media" id="imageFromMedia" value="">
          <label class="file-btn">Загрузить файл
            <input type="file" name="image" accept="image/*" data-preview="#mainPreview">
          </label>
          <button type="button" class="btn-sm" onclick="openMedia('main')">Выбрать из медиа</button>
          <p class="hint">JPG, PNG, WEBP, до 10 МБ</p>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Галерея</h2>
      <div class="gallery-list" id="galleryList">
        <?php foreach ($gallery as $g): ?>
          <div class="gallery-item">
            <img src="<?= e(img_url($g['path'])) ?>" alt="">
            <input type="hidden" name="gallery[]" value="<?= e($g['path']) ?>">
            <button type="button" class="remove" onclick="this.parentNode.remove()">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="row-2" style="margin-top:14px">
        <label class="file-btn">Загрузить несколько
          <input type="file" name="gallery[]" accept="image/*" multiple id="galleryInput">
        </label>
        <button type="button" class="btn-sm" onclick="openMedia('gallery')">Добавить из медиа</button>
      </div>
      <p class="hint">Фото можно перетаскивать за пределы — порядок сохранится как в списке.</p>
    </div>

  </div>

  <div class="col-side">
    <div class="card">
      <h2>Публикация</h2>
      <label class="field">
        <span>Статус</span>
        <select name="status">
          <option value="published" <?= $work['status']==='published'?'selected':'' ?>>Опубликовано</option>
          <option value="draft" <?= $work['status']==='draft'?'selected':'' ?>>Черновик</option>
        </select>
      </label>
      <label class="check"><input type="checkbox" name="is_featured" <?= $work['is_featured']?'checked':'' ?>> Показывать на главной</label>
      <label class="check"><input type="checkbox" name="is_sold" <?= $work['is_sold']?'checked':'' ?>> Продано</label>
      <label class="field"><span>Сортировка</span>
        <input type="number" name="sort" value="<?= (int)$work['sort'] ?>">
      </label>
      <button type="submit" class="btn-primary btn-block">Сохранить</button>
    </div>

    <div class="card">
      <h2>Принадлежность</h2>
      <label class="field">
        <span>Мастер</span>
        <select name="master_id">
          <option value="">— не указан —</option>
          <?php foreach ($masters as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $work['master_id']==$m['id']?'selected':'' ?>><?= e($m['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Категория</span>
        <select name="category_id">
          <option value="">— не указана —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $work['category_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Цена, ₽</span>
        <input type="number" step="1" name="price" value="<?= e($work['price']) ?>">
      </label>
    </div>
  </div>
</form>

<!-- Модалка медиабиблиотеки -->
<div class="modal" id="mediaModal">
  <div class="modal-box">
    <div class="modal-head">
      <h2>Медиабиблиотека</h2>
      <button type="button" onclick="closeMedia()">✕</button>
    </div>
    <div class="modal-body">
      <div class="media-grid">
        <?php foreach ($media as $m): ?>
          <img src="<?= e(img_url($m['path'])) ?>" data-path="<?= e($m['path']) ?>" class="media-pick">
        <?php endforeach; ?>
      </div>
      <p class="hint">Кликните по фото, чтобы выбрать. Загрузить новые можно в разделе «Медиа».</p>
    </div>
  </div>
</div>

<script>
let mediaTarget = 'main';

function openMedia(target) {
  mediaTarget = target;
  document.getElementById('mediaModal').classList.add('open');
}
function closeMedia() { document.getElementById('mediaModal').classList.remove('open'); }

document.querySelectorAll('.media-pick').forEach(img => {
  img.addEventListener('click', () => {
    const path = img.dataset.path;
    if (mediaTarget === 'main') {
      document.getElementById('mainPreview').innerHTML = '<img src="/uploads/' + path + '">';
      document.getElementById('imageFromMedia').value = path;
    } else {
      const wrap = document.createElement('div');
      wrap.className = 'gallery-item';
      wrap.innerHTML = '<img src="/uploads/' + path + '"><input type="hidden" name="gallery[]" value="' + path + '"><button type="button" class="remove" onclick="this.parentNode.remove()">✕</button>';
      document.getElementById('galleryList').appendChild(wrap);
    }
    closeMedia();
  });
});

// превью загрузки файла
document.querySelectorAll('input[type=file][data-preview]').forEach(inp => {
  inp.addEventListener('change', () => {
    const f = inp.files[0]; if (!f) return;
    const url = URL.createObjectURL(f);
    document.querySelector(inp.dataset.preview).innerHTML = '<img src="' + url + '">';
    document.getElementById('imageFromMedia').value = '';
  });
});

// предпросмотр галереи
document.getElementById('galleryInput').addEventListener('change', e => {
  [...e.target.files].forEach(f => {
    const url = URL.createObjectURL(f);
    const wrap = document.createElement('div');
    wrap.className = 'gallery-item';
    wrap.innerHTML = '<img src="' + url + '"><span class="pending">будет загружено</span>';
    document.getElementById('galleryList').appendChild(wrap);
  });
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>