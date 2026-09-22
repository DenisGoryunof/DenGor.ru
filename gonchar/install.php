<?php
require_once __DIR__ . '/includes/bootstrap.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(64) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(128) DEFAULT '',
  role ENUM('admin','editor') DEFAULT 'admin',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS masters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  slug VARCHAR(128) UNIQUE NOT NULL,
  tagline VARCHAR(255) DEFAULT '',
  bio TEXT,
  style TEXT,
  photo VARCHAR(255) DEFAULT '',
  phone VARCHAR(64) DEFAULT '',
  email VARCHAR(128) DEFAULT '',
  instagram VARCHAR(255) DEFAULT '',
  telegram VARCHAR(255) DEFAULT '',
  sort INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  slug VARCHAR(128) UNIQUE NOT NULL,
  sort INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS works (
  id INT AUTO_INCREMENT PRIMARY KEY,
  master_id INT NULL,
  category_id INT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) DEFAULT '',
  description TEXT,
  price DECIMAL(10,2) NULL,
  size VARCHAR(128) DEFAULT '',
  material VARCHAR(128) DEFAULT '',
  image VARCHAR(255) DEFAULT '',
  status ENUM('draft','published') DEFAULT 'published',
  is_featured TINYINT(1) DEFAULT 0,
  is_sold TINYINT(1) DEFAULT 0,
  sort INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (master_id) REFERENCES masters(id) ON DELETE SET NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS work_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  work_id INT NOT NULL,
  path VARCHAR(255) NOT NULL,
  sort INT DEFAULT 0,
  FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(128) UNIQUE NOT NULL,
  title VARCHAR(255) NOT NULL,
  content MEDIUMTEXT,
  meta_title VARCHAR(255) DEFAULT '',
  meta_description VARCHAR(255) DEFAULT '',
  is_published TINYINT(1) DEFAULT 1,
  show_in_menu TINYINT(1) DEFAULT 0,
  sort INT DEFAULT 0,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) PRIMARY KEY,
  `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(255) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  alt VARCHAR(255) DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
    db()->exec($q);
}

/* --- пользователь --- */
$st = db()->prepare("SELECT COUNT(*) FROM users");
$st->execute();
if (!$st->fetchColumn()) {
    db()->prepare("INSERT INTO users (login,password_hash,name,role) VALUES (?,?,?,?)")
        ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Администратор', 'admin']);
}

/* --- мастера --- */
if (!db()->query("SELECT COUNT(*) FROM masters")->fetchColumn()) {
    $m = db()->prepare("INSERT INTO masters (name,slug,tagline,bio,style,instagram,sort) VALUES (?,?,?,?,?,?,?)");
    $m->execute(['София', 'sofia', 'Геометричные формы и глубокие глазури',
        "София работает с глиной более 8 лет. Её почерк — строгие линии, ритм и архитектурность. Любит экспериментировать с восстановительным обжигом и японскими глазурями.",
        "Минимализм, тёмные глазури, обжиг раку, фактурные поверхности",
        'https://instagram.com/', 1]);
    $m->execute(['Наташа', 'natasha', 'Тёплая керамика ручной лепки',
        "Наташа лепит вручную — без гончарного круга. Её работы живые, немного неровные и от этого особенно тёплые. Любит натуральные глины и молочный обжиг.",
        "Ручная лепка, светлые глины, мягкие матовые покрытия, природные мотивы",
        'https://instagram.com/', 2]);
}

/* --- категории --- */
if (!db()->query("SELECT COUNT(*) FROM categories")->fetchColumn()) {
    $c = db()->prepare("INSERT INTO categories (name,slug,sort) VALUES (?,?,?)");
    foreach ([['Посуда','dishes',1],['Вазы','vases',2],['Декор','decor',3],['Кружки','mugs',4]] as $x)
        $c->execute($x);
}

/* --- работы --- */
if (!db()->query("SELECT COUNT(*) FROM works")->fetchColumn()) {
    $w = db()->prepare("INSERT INTO works (master_id,category_id,title,slug,description,price,size,material,is_featured,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $w->execute([1,1,'Чайный набор «Тень»','chaynyy-nabor-ten','Комплект из чайника и двух чашек. Тёмная глазурь с эффектом металла.',8900,'Чашки 200 мл','Шамотная глина, глазурь тёмный тенмоку',1,'published']);
    $w->execute([1,2,'Ваза «Колонна»','vaza-kolonna','Высокая ваза с геометричным ритмом. Отлично смотрится с сухоцветами.',12400,'h 38 см','Керамика, матовая глазурь',1,'published']);
    $w->execute([1,4,'Кружка «Графит»','kruzhka-grafit','Плотная кружка с толстыми стенками, долго держит тепло.',2400,'350 мл','Керамика, восстановительный обжиг',0,'published']);
    $w->execute([2,1,'Тарелка «Луговое»','tarelka-lugovoe','Тарелка ручной лепки с неровным краем и светлой глазурью.',3200,'d 24 см','Красная глина, молочный обжиг',1,'published']);
    $w->execute([2,3,'Панно «Волны»','panno-volny','Настенное панно из отдельных керамических пластин.',6800,'40×30 см','Керамика, ручная лепка',0,'published']);
    $w->execute([2,4,'Кружка «Ромашка»','kruzhka-romashka','Тёплая кружка с рельефным цветочным узором.',1900,'300 мл','Керамика, глазурь',0,'published']);
}

/* --- страницы --- */
if (!db()->query("SELECT COUNT(*) FROM pages")->fetchColumn()) {
    $p = db()->prepare("INSERT INTO pages (slug,title,content,show_in_menu,meta_title,meta_description,sort) VALUES (?,?,?,?,?,?,?)");
    $p->execute(['about','О мастерской',
        "<p>Наша мастерская — это небольшое пространство, где глина превращается в предметы для everyday-жизни. Здесь работают два мастера — София и Наташа. У каждой свой стиль, но обе делают посуду, которой приятно пользоваться каждый день.</p><p>Мы обжигаем работы в электропечи до 1240 °C. Вся посуда покрыта пищевой глазурью и подходит для микроволновки и посудомоечной машины.</p>",
        1, 'О мастерской', 'История и подход гончарной мастерской', 1]);
    $p->execute(['delivery','Доставка и оплата',
        "<p>Отправляем заказы по всей России и в страны СНГ — СДЭК или Почтой России. По Москве возможен самовывоз из мастерской.</p><h3>Оплата</h3><p>Перевод на карту, СБП или наличные при самовывозе. После оплаты отправляем фото упаковки.</p>",
        1, 'Доставка и оплата', 'Условия доставки керамики', 2]);
    $p->execute(['care','Уход за керамикой',
        "<p>Керамика любит бережное обращение. Мыть можно в посудомоечной машине, но лучше вручную. Не ставьте работу на открытый огонь и не заливайте крутым кипятком, если изделие не термостойкое.</p>",
        0, 'Уход за керамикой', 'Как ухаживать за керамической посудой', 3]);
}

/* --- настройки --- */
$defaults = [
    'site_name'        => 'Гончарная мастерская «Две руки»',
    'site_tagline'     => 'Керамика ручной работы',
    'hero_title'       => 'Керамика, в которой живёт тепло рук',
    'hero_subtitle'    => 'Два мастера — два стиля. София и Наташа создают посуду и декор, которые хочется держать в руках каждый день.',
    'hero_button'      => 'Смотреть работы',
    'hero_image'       => '',
    'about_title'      => 'Мастерская, где глина становится своим',
    'about_text'       => "Мы работаем с глиной уже больше восьми лет. Всё делаем сами — от замеса глины до финального обжига. Поэтому у каждой чашки есть характер.",
    'about_image'      => '',
    'masters_title'    => 'Наши мастера',
    'masters_subtitle' => 'У Софии и Наташи разный почерк — и это главное богатство мастерской.',
    'works_title'      => 'Работы',
    'works_subtitle'   => 'Посуда, вазы и декор, которые есть в наличии или делаются на заказ.',
    'contact_title'    => 'Связаться с нами',
    'phone'            => '+7 (999) 000-00-00',
    'email'            => 'hello@twohands.ru',
    'address'          => 'Москва, ул. Гончарная, 12, мастерская 3',
    'work_hours'       => 'Пн–Сб, 11:00–20:00',
    'instagram'        => 'https://instagram.com/',
    'telegram'         => 'https://t.me/',
    'whatsapp'         => '',
    'footer_text'      => '© Гончарная мастерская «Две руки». Все работы созданы вручную.',
];
$s = db()->prepare("INSERT IGNORE INTO settings (`key`,`value`) VALUES (?,?)");
foreach ($defaults as $k => $v) $s->execute([$k, $v]);

if (!is_dir(UPLOADS_PATH)) mkdir(UPLOADS_PATH, 0775, true);
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Установка</title>
<style>body{font-family:system-ui;max-width:640px;margin:80px auto;padding:0 20px;line-height:1.6;color:#2b2b2b}
a{color:#b4552d}code{background:#f4f1ec;padding:2px 6px;border-radius:4px}</style></head><body>
<h1>Готово ✨</h1>
<p>Таблицы созданы, демо-данные добавлены.</p>
<p><b>Вход в админку:</b> <a href="/admin/login.php">/admin/login.php</a><br>
Логин: <code>admin</code>, пароль: <code>admin123</code></p>
<p style="color:#a33"><b>Важно:</b> удалите файл <code>install.php</code> после установки.</p>
</body></html>