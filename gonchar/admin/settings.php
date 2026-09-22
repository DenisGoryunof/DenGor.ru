<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$fields = [
    'Основное' => [
        'site_name'    => ['Название сайта', 'text'],
        'site_tagline' => ['Подзаголовок (в шапке)', 'text'],
    ],
    'Главный экран' => [
        'hero_title'    => ['Заголовок', 'text'],
        'hero_subtitle' => ['Описание', 'textarea'],
        'hero_button'   => ['Текст кнопки', 'text'],
        'hero_image'    => ['Фоновое изображение', 'image'],
    ],
    'Блок «О мастерской»' => [
        'about_title' => ['Заголовок', 'text'],
        'about_text'  => ['Текст', 'textarea'],
        'about_image' => ['Изображение', 'image'],
    ],
    'Блок «Мастера»' => [
        'masters_title'    => ['Заголовок', 'text'],
        'masters_subtitle' => ['Описание', 'text'],
    ],
    'Блок «Работы»' => [
        'works_title'    => ['Заголовок', 'text'],
        'works_subtitle' => ['Описание', 'text'],
    ],
    'Контакты' => [
        'contact_title' => ['Заголовок блока', 'text'],
        'phone'         => ['Телефон', 'text'],
        'email'         => ['Email', 'text'],
        'address'       => ['Адрес', 'text'],
        'work_hours'    => ['Часы работы', 'text'],
        'instagram'     => ['Instagram', 'text'],
        'telegram'      => ['Telegram', 'text'],
        'whatsapp'      => ['WhatsApp', 'text'],
    ],
    'Подвал' => [
        'footer_text' => ['Текст в подвале', 'text'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $upd = db()->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?)
                          ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
    foreach ($fields as $group) {
        foreach ($group as $key => [$label, $type]) {
            $val = $_POST[$key] ?? '';
            if ($type === 'image' && !empty($_FILES[$key]['name'])) {
                $u = upload_image($_FILES[$key]);
                if ($u) $val = $u;
                else $val = setting($key);
            }
            $upd->execute([$key, $val]);
        }
    }
    flash('Настройки сохранены');
    header('Location: settings.php'); exit;
}

$title = 'Тексты сайта';
require __DIR__ . '/_header.php';

$cur = settings();
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="settings-grid">
  <?php foreach ($fields as $groupName => $group): ?>
    <div class="card">
      <h2><?= e($groupName) ?></h2>
      <?php foreach ($group as $key => [$label, $type]):
        $val = $cur[$key] ?? ''; ?>
        <label class="field">
          <span><?= e($label) ?></span>
          <?php if ($type === 'textarea'): ?>
            <textarea name="<?= $key ?>" rows="4"><?= e($val) ?></textarea>
          <?php elseif ($type === 'image'): ?>
            <div class="image-picker small">
              <div class="image-preview" id="prev_<?= $key ?>">
                <?php if ($val): ?><img src="<?= e(img_url($val)) ?>"><?php else: ?><span class="no-img">🖼</span><?php endif; ?>
              </div>
              <div class="image-picker-side">
                <input type="file" name="<?= $key ?>" accept="image/*">
                <p class="hint">Текущий: <code><?= e($val ?: '—') ?></code></p>
              </div>
            </div>
          <?php else: ?>
            <input type="text" name="<?= $key ?>" value="<?= e($val) ?>">
          <?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="sticky-save">
    <button class="btn-primary">Сохранить все настройки</button>
  </div>
</form>

<?php require __DIR__ . '/_footer.php'; ?>