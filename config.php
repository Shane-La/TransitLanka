<?php
// ---------- Database ----------
const DB_HOST = 'localhost';
const DB_NAME = 'transitlanka';
const DB_USER = 'root';
const DB_PASS = '';

// ---------- Site ----------
const BASE_URL = 'http://localhost/transitlanka';   // change if your folder name / domain differs (no trailing slash)

// ---------- Google Maps (Maps JavaScript API key) ----------
const GMAPS_KEY = 'YOUR_GOOGLE_MAPS_API_KEY';

// ---------- PayHere (https://www.payhere.lk) ----------
const PH_MERCHANT_ID = 'YOUR_MERCHANT_ID';
const PH_SECRET      = 'YOUR_MERCHANT_SECRET';
const PH_SANDBOX     = true;   // true = sandbox testing, false = live
const PAY_DEMO       = true;   // true = skip PayHere and mark payments paid instantly (for demo on localhost)

// ---------- Sample bus timetable (minutes after midnight) ----------
const FIRST_BUS = 330;   // 5:30 AM
const LAST_BUS  = 1260;  // 9:00 PM
const GAP       = 30;    // every 30 minutes
