<?php
require __DIR__ . '/config.php';
session_start();
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function user() { return $_SESSION['user'] ?? null; }
function go($path) { header('Location: ' . BASE_URL . $path); exit; }
function dash_path($role) { return ['admin' => '/admin/dashboard.php', 'owner' => '/owner/dashboard.php', 'tourist' => '/tourist/dashboard.php'][$role]; }
function require_role($role) { if (!user() || user()['role'] !== $role) go('/login.php'); }
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check() { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Invalid request'); }
function money($n, $cur = 'LKR') { return $cur . ' ' . number_format((float)$n, 2); }

function setting($k) {
    global $pdo;
    $s = $pdo->prepare('SELECT v FROM settings WHERE k=?'); $s->execute([$k]);
    return $s->fetchColumn();
}
function ad_views_unbilled($ownerId) {
    global $pdo;
    $s = $pdo->prepare('SELECT COUNT(*) FROM ad_views v JOIN places p ON p.id=v.place_id WHERE p.owner_id=? AND v.billed=0');
    $s->execute([$ownerId]);
    return (int)$s->fetchColumn();
}
function ad_due($ownerId) { return ad_views_unbilled($ownerId) * (float)setting('ad_rate'); }

// Runs after a successful payment (PayHere notify or demo mode)
function apply_payment($orderId) {
    global $pdo;
    $s = $pdo->prepare("SELECT * FROM payments WHERE order_id=? AND status='pending'");
    $s->execute([$orderId]);
    $p = $s->fetch();
    if (!$p) return;
    $pdo->prepare("UPDATE payments SET status='paid' WHERE id=?")->execute([$p['id']]);
    if ($p['type'] === 'fee') {
        $pdo->prepare('UPDATE users SET paid_until = DATE_ADD(GREATEST(COALESCE(paid_until, CURDATE()), CURDATE()), INTERVAL 30 DAY) WHERE id=?')->execute([$p['user_id']]);
    } elseif ($p['type'] === 'ads') {
        $pdo->prepare('UPDATE ad_views v JOIN places pl ON pl.id=v.place_id SET v.billed=1 WHERE pl.owner_id=?')->execute([$p['user_id']]);
    } else { // tourist pass -> create e-ticket
        $q = $pdo->prepare('SELECT days FROM passes WHERE id=?'); $q->execute([$p['ref_id']]);
        $days = (int)$q->fetchColumn();
        $code = 'TL-' . strtoupper(bin2hex(random_bytes(4)));
        $pdo->prepare('INSERT INTO tourist_passes(user_id,pass_id,code,valid_from,valid_until) VALUES(?,?,?,CURDATE(),DATE_ADD(CURDATE(),INTERVAL ? DAY))')
            ->execute([$p['user_id'], $p['ref_id'], $code, $days - 1]);
    }
}

// Route data for the tourist search / route page. $from,$to = stop ids (null = whole route, forward)
function build_route($rid, $from = null, $to = null) {
    global $pdo;
    $r = $pdo->prepare('SELECT * FROM routes WHERE id=?'); $r->execute([$rid]);
    $route = $r->fetch();
    if (!$route) return null;
    $st = $pdo->prepare('SELECT s.id,s.name,s.lat,s.lng,rs.offset_min FROM route_stops rs JOIN stops s ON s.id=rs.stop_id WHERE rs.route_id=? ORDER BY rs.seq');
    $st->execute([$rid]);
    $all = $st->fetchAll();
    $ids = array_column($all, 'id');
    $total = (int)end($all)['offset_min'];
    $a = $from ? array_search($from, $ids) : 0;
    $b = $to ? array_search($to, $ids) : count($all) - 1;
    if ($a === false || $b === false || $a === $b) return null;
    if ($a < $b) { $seg = array_slice($all, $a, $b - $a + 1); }
    else {
        $seg = array_reverse(array_slice($all, $b, $a - $b + 1));
        foreach ($seg as &$s) $s['offset_min'] = $total - $s['offset_min'];
        unset($s);
    }
    $base = (int)$seg[0]['offset_min'];
    foreach ($seg as &$s) {
        $s['after'] = (int)$s['offset_min'] - $base;
        $s['lat'] = (float)$s['lat']; $s['lng'] = (float)$s['lng'];
        unset($s['offset_min']);
    }
    unset($s);
    $deps = [];
    for ($t = FIRST_BUS; $t <= LAST_BUS; $t += GAP) $deps[] = $t + $base;
    $sid = implode(',', array_map('intval', array_column($seg, 'id')));
    $places = $pdo->query("SELECT p.id,p.stop_id,p.name,p.type,p.description,p.phone,p.ad_on,p.ad_text FROM places p JOIN users u ON u.id=p.owner_id
        WHERE p.status='approved' AND u.paid_until>=CURDATE() AND p.stop_id IN ($sid) ORDER BY p.ad_on DESC,p.name")->fetchAll();
    $att = $pdo->query("SELECT id,stop_id,name,category,description FROM attractions WHERE stop_id IN ($sid)")->fetchAll();
    $ins = $pdo->prepare('INSERT INTO ad_views(place_id) VALUES(?)');   // 1 sponsored view = 1 billable impression
    foreach ($places as &$p) { $p['ad_on'] = (int)$p['ad_on']; if ($p['ad_on']) $ins->execute([$p['id']]); }
    unset($p);
    return ['route' => ['id' => (int)$route['id'], 'code' => $route['code'], 'name' => $route['name'], 'summary' => $route['summary'], 'duration' => $route['duration_text']],
            'stops' => $seg, 'deps' => $deps, 'duration_min' => end($seg)['after'], 'places' => $places, 'attractions' => $att];
}

function page_head($title, $extraHead = '') {
    $u = user(); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | TransitLanka</title>
<link rel="icon" href="<?= BASE_URL ?>/assets/logo-mark.png">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
<?= $extraHead ?></head><body>
<header><div class="bar">
<a class="brand" href="<?= BASE_URL ?>/index.php"><img src="<?= BASE_URL ?>/assets/logo-mark.png" alt="TransitLanka logo"><span>Transit<b>Lanka</b></span></a>
<nav>
<a href="<?= BASE_URL ?>/index.php#routes">Find a Route</a>
<a href="<?= BASE_URL ?>/index.php#city-routes">City bus routes</a>
<a href="<?= BASE_URL ?>/index.php#tourist-pass">Tourist passes</a>
<a href="<?= BASE_URL ?>/index.php#about">About</a>
<?php if (!$u): ?><a class="btn" href="<?= BASE_URL ?>/login.php">Log in</a>
<?php else: ?><a class="btn" href="<?= BASE_URL . dash_path($u['role']) ?>">My dashboard</a><a href="<?= BASE_URL ?>/logout.php">Log out</a><?php endif; ?>
</nav></div></header>
<main>
<?php }
function page_foot() { ?>
</main>
<footer><div class="bar"><span>© 2026 TransitLanka – Student project prototype.</span><span>Emergency: Police 119 · Ambulance 1990</span></div></footer>
</body></html>
<?php }
