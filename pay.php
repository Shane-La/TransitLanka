<?php
// Creates a payment and sends the user to PayHere (or completes it instantly in PAY_DEMO mode)
require __DIR__ . '/inc.php';
$u = user();
if (!$u || $_SERVER['REQUEST_METHOD'] !== 'POST') go('/login.php');
csrf_check();
$type = in_array($_POST['type'] ?? '', ['fee', 'ads', 'pass'], true) ? $_POST['type'] : '';
$ref = null; $cur = 'LKR'; $item = '';
if ($type === 'fee' && $u['role'] === 'owner') { $amount = (float)setting('monthly_fee'); $item = 'TransitLanka monthly listing fee'; }
elseif ($type === 'ads' && $u['role'] === 'owner') { $amount = ad_due($u['id']); $item = 'TransitLanka ad views'; }
elseif ($type === 'pass' && $u['role'] === 'tourist') {
    $q = $pdo->prepare('SELECT * FROM passes WHERE id=?'); $q->execute([(int)($_POST['pass_id'] ?? 0)]);
    $pass = $q->fetch();
    if (!$pass) go('/index.php#tourist-pass');
    $amount = (float)$pass['price_usd']; $cur = 'USD'; $ref = $pass['id']; $item = $pass['name'];
} else go(dash_path($u['role']));
if ($amount <= 0) go(dash_path($u['role']));

$order = 'TL' . time() . random_int(100, 999);
$pdo->prepare("INSERT INTO payments(order_id,user_id,type,ref_id,amount,currency,status) VALUES(?,?,?,?,?,?,'pending')")->execute([$order, $u['id'], $type, $ref, $amount, $cur]);

if (PAY_DEMO) { apply_payment($order); go(dash_path($u['role']) . '?paid=1'); }

$us = $pdo->prepare('SELECT name,email FROM users WHERE id=?'); $us->execute([$u['id']]);
$row = $us->fetch();
$parts = explode(' ', $row['name'], 2);
$amt = number_format($amount, 2, '.', '');
$hash = strtoupper(md5(PH_MERCHANT_ID . $order . $amt . $cur . strtoupper(md5(PH_SECRET))));
$action = PH_SANDBOX ? 'https://sandbox.payhere.lk/pay/checkout' : 'https://www.payhere.lk/pay/checkout';
$f = ['merchant_id' => PH_MERCHANT_ID, 'return_url' => BASE_URL . dash_path($u['role']) . '?paid=1', 'cancel_url' => BASE_URL . dash_path($u['role']),
      'notify_url' => BASE_URL . '/payhere_notify.php', 'order_id' => $order, 'items' => $item, 'currency' => $cur, 'amount' => $amt,
      'first_name' => $parts[0], 'last_name' => $parts[1] ?? '-', 'email' => $row['email'], 'phone' => '0770000000',
      'address' => 'Colombo', 'city' => 'Colombo', 'country' => 'Sri Lanka', 'hash' => $hash];
?>
<!DOCTYPE html><html><body onload="document.forms[0].submit()"><p>Redirecting to PayHere…</p>
<form method="post" action="<?= $action ?>">
<?php foreach ($f as $k => $v): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endforeach; ?>
<noscript><button>Continue to PayHere</button></noscript></form></body></html>
