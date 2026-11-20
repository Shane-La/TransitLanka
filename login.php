<?php
require __DIR__ . '/inc.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = $pdo->prepare('SELECT * FROM users WHERE email=?');
    $s->execute([strtolower(trim($_POST['email'] ?? ''))]);
    $u = $s->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']];
        go(dash_path($u['role']));
    }
    $err = 'Wrong email or password.';
}
page_head('Log in'); ?>
<h1>Log in</h1>
<div class="card narrow">
<form method="post"><?= csrf_field() ?>
<p class="err"><?= e($err) ?></p>
<label>Email</label><input name="email" type="email" required>
<label>Password</label><input name="password" type="password" required>
<div class="acts"><button class="pri">Log in</button></div></form>
<p class="note">No account? <a href="register.php?role=tourist">Register as a tourist</a> · <a href="register.php?role=owner">Register as a place owner</a></p></div>
<?php page_foot();
