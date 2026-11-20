<?php
require __DIR__ . '/inc.php';
$id = (int)($_GET['id'] ?? 0);
$mapKey = GMAPS_KEY !== 'YOUR_GOOGLE_MAPS_API_KEY';
$head = $mapKey ? '<script src="https://maps.googleapis.com/maps/api/js?key=' . e(GMAPS_KEY) . '&callback=initMap" async defer></script>' : '';
page_head('Route', $head); ?>
<p><a href="index.php#city-routes">← All routes</a></p>
<div id="results" data-route="<?= $id ?>"></div>
<div id="map"><?= $mapKey ? '' : 'Add your Google Maps API key in config.php to see the route map.' ?></div>
<script src="assets/app.js"></script>
<?php page_foot();
