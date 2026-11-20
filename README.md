# TransitLanka – Colombo Bus Guide (HTML + CSS + JS + PHP + MySQL + Google Maps + PayHere)

## 1. Run it locally (XAMPP / WAMP / Laragon)
1. Copy the `transitlanka` folder into `htdocs` (XAMPP) or `www` (WAMP).
2. Start Apache + MySQL. Open phpMyAdmin → **Import** → choose `database.sql`.
3. Open `config.php` and check DB_USER / DB_PASS / BASE_URL.
4. Visit http://localhost/transitlanka

## 2. Demo logins
| Role | Email | Password |
|---|---|---|
| Admin | admin@transitlanka.lk | admin123 |
| Place owner | owner@transitlanka.lk | owner123 |
| Tourist | register from the site | – |
**Change these passwords before going live.**

## 3. Google Maps
Create a key at https://console.cloud.google.com (enable *Maps JavaScript API*) and put it in `GMAPS_KEY` in `config.php`.

## 4. PayHere payments
* `PAY_DEMO = true` completes payments instantly (good for presentation).
* For real/sandbox payments: register at https://www.payhere.lk, set `PH_MERCHANT_ID`, `PH_SECRET`, `PAY_DEMO = false`. Add your domain in the PayHere merchant settings. `payhere_notify.php` must be publicly reachable (live hosting or ngrok), otherwise payments will not be activated.

## 5. How it works
* **Tourist**: searches From → To (no login). Sees route, timetable, map, attractions + hotels/restaurants/malls along the route. Registers to buy 3-day / 5-day passes (USD) and gets an e-ticket code.
* **Place owner**: registers, adds a place (needs admin approval), pays the **monthly fee** (LKR 5,000) to be visible, and can run a **promotion ad** that appears as “Sponsored”. Every time the ad is shown = 1 view, billed at **LKR 2 per view** (pay under “Pay ad charges”).
* **Admin**: approves/rejects places, sets fee and per-view price, sees revenue and payments.

## 6. Notes
* Routes, stops, coordinates and timetable are **sample data** from the prototype. Replace them in `database.sql` / phpMyAdmin with real Lanka Metro Bus data.
* Before going live: use HTTPS, change admin password, set `PAY_DEMO=false`.
