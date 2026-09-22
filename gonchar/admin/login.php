<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (current_user()) { header('Location: index.php'); exit; }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $st = db()->prepare("SELECT * FROM users WHERE login = ?");
    $st->execute([$login]);
    $u = $st->fetch();
    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['user'] = ['id' => $u['id'], 'login' => $u['login'], 'name' => $u['name'], 'role' => $u['role']];
        header('Location: index.php'); exit;
    }
    $error = 'Неверный логин или пароль';
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Вход</title><link rel="stylesheet" href="/assets/css/admin.css"></head>
<body class="login-body">
<form method="post" class="login-card">
  <div class="login-brand"><span>◐</span></div>
  <h1>Гончарная мастерская</h1>
  <p class="muted">Вход в панель управления</p>
  <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <label>Логин<input type="text" name="login" required autofocus value="admin"></label>
  <label>Пароль<input type="password" name="password" required></label>
  <button class="btn-primary" type="submit">Войти</button>
</form>
</body></html>