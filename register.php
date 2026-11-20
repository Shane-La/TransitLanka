<?php
require __DIR__ . '/inc.php';
$role = ($_GET['role'] ?? $_POST['role'] ?? 'tourist') === 'owner' ? 'owner' : 'tourist';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 6) $err = 'Enter your name, a valid email and a password (6+ characters).';
    else {
        $c = $pdo->prepare('SELECT id FROM users WHERE email=?'); $c->execute([$email]);
        if ($c->fetch()) $err = 'That email is already registered.';
        else {
            $pdo->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)')->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $pdo->lastInsertId(), 'name' => $name, 'role' => $role];
            go(dash_path($role));
        }
    }
}
page_head('Register'); ?>
<h1>Register as a <?= $role === 'owner' ? 'place owner' : 'tourist' ?></h1>
<div class="card narrow">
<form method="post"><?= csrf_field() ?><input type="hidden" name="role" value="<?= e($role) ?>">
<p class="err"><?= e($err) ?></p>
<label><?= $role === 'owner' ? 'Your / business name' : 'Full name' ?></label><input name="name" required>
<label>Email</label><input name="email" type="email" required>
<label>Password</label><input name="password" type="password" required>
<div class="acts"><button class="pri">Create account</button></div></form>
<p class="note"><?= $role === 'owner' ? '<a href="register.php?role=tourist">I am a tourist</a>' : '<a href="register.php?role=owner">I own a hotel / restaurant / mall</a>' ?></p></div>
<?php page_foot();
