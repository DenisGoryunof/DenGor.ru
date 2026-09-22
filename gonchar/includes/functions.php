<?php

function db(): PDO {
    global $CONFIG;
    static $pdo = null;
    if ($pdo === null) {
        $d = $CONFIG['db'];
        $pdo = new PDO(
            "mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}",
            $d['user'], $d['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function slugify(string $text): string {
    $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z',
        'и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
        'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch',
        'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',' '=>'-','_'=>'-'];
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\-]+/u', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-') ?: 'item-' . random_int(100, 999);
}

function unique_slug(string $table, string $slug, ?int $ignoreId = null): string {
    $base = $slug; $i = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId) { $sql .= " AND id <> ?"; $params[] = $ignoreId; }
        $st = db()->prepare($sql); $st->execute($params);
        if (!$st->fetchColumn()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

/* ---------- Настройки ---------- */
function settings(): array {
    static $s = null;
    if ($s === null) {
        $s = [];
        foreach (db()->query("SELECT `key`,`value` FROM settings") as $r) $s[$r['key']] = $r['value'];
    }
    return $s;
}
function setting(string $key, string $default = ''): string {
    $s = settings();
    return $s[$key] ?? $default;
}

/* ---------- Авторизация ---------- */
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void {
    if (!current_user()) { header('Location: login.php'); exit; }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(419); exit('Ошибка проверки CSRF-токена');
    }
}

/* ---------- Flash ---------- */
function flash(?string $msg = null, string $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

/* ---------- Загрузка изображений ---------- */
function upload_image(array $file): ?string {
    if (empty($file['tmp_name']) || ($file['error'] ?? 1) !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 10 * 1024 * 1024) return null;
    $mime = mime_content_type($file['tmp_name']) ?: '';
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png',
                'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) return null;

    $sub = date('Y/m');
    $dir = UPLOADS_PATH . '/' . $sub;
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $rel  = $sub . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], UPLOADS_PATH . '/' . $rel)) return null;

    db()->prepare("INSERT INTO media (path, filename, created_at) VALUES (?,?,NOW())")
       ->execute([$rel, $file['name']]);
    return $rel;
}

function img_url(?string $path, string $fallback = '/assets/img/placeholder.svg'): string {
    if (!$path) return $fallback;
    if (preg_match('~^https?://~', $path)) return $path;
    return UPLOADS_URL . '/' . $path;
}

/* ---------- Меню ---------- */
function menu_pages(): array {
    return db()->query("SELECT slug,title FROM pages WHERE is_published=1 AND show_in_menu=1 ORDER BY sort,id")->fetchAll();
}