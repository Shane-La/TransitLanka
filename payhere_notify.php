<?php
// PayHere server-to-server notification (must be reachable from the internet: use your live domain or ngrok)
require __DIR__ . '/inc.php';
$mid = $_POST['merchant_id'] ?? ''; $oid = $_POST['order_id'] ?? ''; $amt = $_POST['payhere_amount'] ?? '';
$cur = $_POST['payhere_currency'] ?? ''; $sc = $_POST['status_code'] ?? ''; $sig = strtoupper($_POST['md5sig'] ?? '');
$local = strtoupper(md5($mid . $oid . $amt . $cur . $sc . strtoupper(md5(PH_SECRET))));
if (hash_equals($local, $sig) && $sc === '2' && $mid === PH_MERCHANT_ID) {
    $q = $pdo->prepare('SELECT amount,currency FROM payments WHERE order_id=?'); $q->execute([$oid]);
    $p = $q->fetch();
    if ($p && number_format($p['amount'], 2, '.', '') === number_format((float)$amt, 2, '.', '') && $p['currency'] === $cur) apply_payment($oid);
}
http_response_code(200);
