<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$m = ['name'=>'','slug'=>'','tagline'=>'','bio'=>'','style'=>'','photo'=>'','phone'=>'',
      'email'=>'','instagram'=>'','telegram'=>'','sort'=>0,'is_active'=>1];

if ($id) {
    $st = db()->prepare("SELECT * FROM masters WHERE id=?"); $st->execute([$id]);
    $m = $st->fetch() ?: $m;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'name'      => trim($_POST['name'] ?? ''),
        'tagline'   => trim($_POST['tagline'] ?? ''),
        'bio'       => $_POST['bio'] ?? '',
        'style'     => trim($_POST['style'] ?? ''),
        'phone'     => trim($_POST['phone'] ?? ''),
        'email'     => trim($_POST['email'] ?? ''),
        'instagram' => trim($_POST['instagram'] ?? ''),
        'telegram'  => trim($_POST['telegram'] ?? ''),
        'sort'      => (int)($_POST['sort'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($data['name'] === '') { flash('Укажите имя', 'error'); }
    else {
        if (!empty($_FILES['photo']['name'])) {
            $up = upload_image($_FILES['photo']);
            if ($up) $data['photo'] = $up;
        }
        if (!empty($_POST['photo_from_media'])) $data['photo'] = $_POST['photo_from_media'];

        if ($id) {
            $sql = "UPDATE masters SET name=:name, tagline=:tagline, bio=:bio, style=:style,
                    phone=:phone, email=:email, instagram=:instagram, telegram=:telegram,
                    sort=:sort, is_active=:is_active"
                    . (isset($data['photo']) ? ", photo=:photo" : "") . " WHERE id=:id";
            $data['id'] = $id;
            db()->prepare($sql)->execute($data);
        } else {
            $data['slug'] = unique_slug('masters', slugify($data['name']));
            $cols = array_keys($data);
            db()->prepare("INSERT INTO masters (`" . implode('`,`',$cols) . "`) VALUES (:" . implode(',:',$cols) . ")")
               ->execute($data);
            $id = (int)db()->lastInsertId();
        }
        flash('Сохранено');
        header("Location: master_edit.php?id=$id"); exit;
    }
}

$media = db()->query("SELECT * FROM media ORDER BY id DESC LIMIT 40")->fetchAll();
$title = $id ? 'Мастер: ' . $m['name'] : 'Новый мастер';
require __DIR__ . '/_header.php';
?>

<form method="post" enctype="multipart/form-data" class="edit-grid">
  <?= csrf_field() ?>
  <div class="col-main">
    <div class="card">
      <div class="row-2">
        <label class="field"><span>Имя *</span>
          <input type="text" name="name" value="<?= e($m['name']) ?>" required></label>
        <label class="field"><span>Короткий слоган</span>
          <input type="text" name="tagline" value="<?= e($m['tagline']) ?>"></label>
      </div>
      <label class="field"><span>О мастере</span>
        <textarea name="bio" rows="7"><?= e($m['bio']) ?></textarea></label>
      <label class="field"><span>Стиль / техника</span>
        <textarea name="style" rows="2"><?= e($m['style']) ?></textarea></label>
    </div>

    <div class="card">
      <h2>Фотография</h2>
      <div class="image-picker">
        <div class="image-preview round" id="photoPreview">
          <?php if ($m['photo']): ?><img src="<?= e(img_url($m['photo'])) ?>" alt="">
          <?php else: ?><span class="no-img big">👤</span><?php endif; ?>
        </div>
        <div class="image-picker-side">
          <input type="hidden" name="photo_from_media" id="photoFromMedia" value="">
          <label class="file-btn">Загрузить фото
            <input type="file" name="photo" accept="image/*" data-preview="#photoPreview">
          </label>
          <button type="button" class="btn-sm" onclick="openMedia()">Из медиабиблиотеки</button>
        </div>
      </div>
    </div>
  </div>

  <div class="col-side">
    <div class="card">
      <h2>Публикация</h2>
      <label class="check"><input type="checkbox" name="is_active" <?= $m['is_active']?'checked':'' ?>> Показывать на сайте</label>
      <label class="field"><span>Сортировка</span>
        <input type="number" name="sort" value="<?= (int)$m['sort'] ?>"></label>
      <button class="btn-primary btn-block" type="submit">Сохранить</button>
    </div>
    <div class="card">
      <h2>Контакты мастера</h2>
      <label class="field"><span>Телефон</span><input type="text" name="phone" value="<?= e($m['phone']) ?>"></label>
      <label class="field"><span>Email</span><input type="text" name="email" value="<?= e($m['email']) ?>"></label>
      <label class="field"><span>Instagram</span><input type="text" name="instagram" value="<?= e($m['instagram']) ?>"></label>
      <label class="field"><span>Telegram</span><input type="text" name="telegram" value="<?= e($m['telegram']) ?>"></label>
    </div>
  </div>
</form>

<div class="modal" id="mediaModal">
  <div class="modal-box">
    <div class="modal-head"><h2>Выберите фото</h2><button type="button" onclick="closeMedia()">✕</button></div>
    <div class="modal-body">
      <div class="media-grid">
        <?php foreach ($media as $mm): ?>
          <img src="<?= e(img_url($mm['path'])) ?>" data-path="<?= e($mm['path']) ?>" class="media-pick">
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
function openMedia(){document.getElementById('mediaModal').classList.add('open')}
function closeMedia(){document.getElementById('mediaModal').classList.remove('open')}
document.querySelectorAll('.media-pick').forEach(img=>img.addEventListener('click',()=>{
  document.getElementById('photoPreview').innerHTML='<img src="/uploads/'+img.dataset.path+'">';
  document.getElementById('photoFromMedia').value=img.dataset.path;
  closeMedia();
}));
document.querySelectorAll('input[type=file][data-preview]').forEach(inp=>{
  inp.addEventListener('change',()=>{
    const f=inp.files[0]; if(!f) return;
    document.querySelector(inp.dataset.preview).innerHTML='<img src="'+URL.createObjectURL(f)+'">';
    document.getElementById('photoFromMedia').value='';
  });
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>