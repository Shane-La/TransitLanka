<?php
require __DIR__ . '/inc.php';
$stops = $pdo->query('SELECT id,name FROM stops ORDER BY name')->fetchAll();
$atts = $pdo->query('SELECT id,name FROM attractions ORDER BY name')->fetchAll();
$routes = $pdo->query('SELECT * FROM routes ORDER BY code')->fetchAll();
$passes = $pdo->query('SELECT * FROM passes ORDER BY days')->fetchAll();
$u = user();
$mapKey = GMAPS_KEY !== 'YOUR_GOOGLE_MAPS_API_KEY';
$head = $mapKey ? '<script src="https://maps.googleapis.com/maps/api/js?key=' . e(GMAPS_KEY) . '&callback=initMap" async defer></script>' : '';
page_head('Explore Colombo with Ease', $head);
function options($atts, $stops) {
    $h = '<optgroup label="Popular places">';
    foreach ($atts as $a) $h .= '<option value="a' . $a['id'] . '">' . e($a['name']) . '</option>';
    $h .= '</optgroup><optgroup label="Bus stops">';
    foreach ($stops as $s) $h .= '<option value="s' . $s['id'] . '">' . e($s['name']) . '</option>';
    return $h . '</optgroup>';
}
?>
<section class="hero" id="home">
  <div><p class="kicker">Discover Colombo, one bus ride at a time</p>
  <h1>Explore Sri Lanka with ease.</h1>
  <p class="sub">Your simple travel companion for finding public bus routes, timetables and the hotels, restaurants and attractions along the way.</p>
  <a class="btn big" href="#routes">Find your bus route →</a></div>
  <img src="assets/logo-full.jpg" alt="TransitLanka – Explore our capital">
</section>

<section id="routes"><h2>Plan your journey</h2>
<div class="card"><form id="searchForm" class="search">
  <div><label for="from">Starting point</label><select id="from"><?= options($atts, $stops) ?></select></div>
  <div><label for="to">Destination</label><select id="to"><?= options($atts, $stops) ?></select></div>
  <button class="pri">Search journey</button></form></div>
<div id="results"></div>
<div id="map"><?= $mapKey ? '' : 'Add your Google Maps API key in config.php to see the route map.' ?></div></section>

<section id="city-routes"><h2>City bus routes</h2><p class="sub">Select any route to see its stops, timetable, map and the hotels, attractions and services along it.</p>
<div class="grid">
<?php foreach ($routes as $r): ?>
<a class="card route" href="route.php?id=<?= $r['id'] ?>"><span class="chip gold"><?= e($r['code']) ?></span><h3><?= e($r['name']) ?></h3><p><?= e($r['summary']) ?></p><small><?= e($r['duration_text']) ?> ↗</small></a>
<?php endforeach; ?></div>
<p class="note">Journey times and route descriptions are prototype information. Please verify current services before travelling.</p></section>

<section id="tourist-pass"><h2>Tourist e-Travel Passes</h2><p class="sub">Choose a short-term pass and travel on the TransitLanka participating bus network during your visit.</p>
<div class="grid">
<?php foreach ($passes as $p): ?>
<div class="card pass"><span class="chip gold"><?= e($p['tagline']) ?></span><h3><?= e($p['name']) ?></h3><div class="price">US$<?= rtrim(rtrim($p['price_usd'], '0'), '.') ?></div>
<ul><?php foreach (explode('|', $p['features']) as $f) echo '<li>' . e($f) . '</li>'; ?></ul>
<?php if ($u && $u['role'] === 'tourist'): ?>
<form method="post" action="pay.php"><?= csrf_field() ?><input type="hidden" name="type" value="pass"><input type="hidden" name="pass_id" value="<?= $p['id'] ?>"><button class="pri">Buy <?= e($p['name']) ?></button></form>
<?php else: ?><a class="btn" href="register.php?role=tourist">Register to buy</a><?php endif; ?></div>
<?php endforeach; ?></div></section>

<section id="explore"><h2>Explore Colombo</h2><div class="grid">
<?php foreach ($pdo->query('SELECT a.name,a.category,a.description,s.name AS stop FROM attractions a JOIN stops s ON s.id=a.stop_id LIMIT 6') as $a): ?>
<div class="card"><span class="chip"><?= e($a['category']) ?></span><h3><?= e($a['name']) ?></h3><p><?= e($a['description']) ?></p><small>Nearest bus stop: <?= e($a['stop']) ?></small></div>
<?php endforeach; ?></div></section>

<section id="about"><h2>Making local travel easier for visitors</h2>
<div class="grid"><div class="card"><h3>Our mission</h3><p>To make public bus travel more understandable and accessible for tourists by bringing journey guidance and useful visitor information together.</p></div>
<div class="card"><h3>Own a hotel, restaurant or mall?</h3><p>List your place along a bus route and reach visitors as they travel.</p><a class="btn" href="register.php?role=owner">Register as a place owner</a></div></div>
<p class="note"><b>Prototype notice:</b> route data is sample information, not official timetables or fares.</p></section>
<script src="assets/app.js"></script>
<?php page_foot();
