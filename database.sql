CREATE DATABASE IF NOT EXISTS transitlanka CHARACTER SET utf8mb4;
USE transitlanka;

CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL, role ENUM('admin','owner','tourist') NOT NULL, paid_until DATE NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE stops(id INT PRIMARY KEY, name VARCHAR(80) NOT NULL, lat DECIMAL(9,6) NOT NULL, lng DECIMAL(9,6) NOT NULL);
CREATE TABLE routes(id INT PRIMARY KEY, code VARCHAR(10) NOT NULL, name VARCHAR(120) NOT NULL, summary VARCHAR(255), duration_text VARCHAR(40));
CREATE TABLE route_stops(route_id INT NOT NULL, seq INT NOT NULL, stop_id INT NOT NULL, offset_min INT NOT NULL, PRIMARY KEY(route_id,seq),
  FOREIGN KEY(route_id) REFERENCES routes(id), FOREIGN KEY(stop_id) REFERENCES stops(id));
CREATE TABLE attractions(id INT PRIMARY KEY, name VARCHAR(100) NOT NULL, category VARCHAR(40), description VARCHAR(255), stop_id INT NOT NULL, FOREIGN KEY(stop_id) REFERENCES stops(id));
CREATE TABLE places(id INT AUTO_INCREMENT PRIMARY KEY, owner_id INT NOT NULL, stop_id INT NOT NULL, name VARCHAR(100) NOT NULL, type VARCHAR(30) NOT NULL,
  description VARCHAR(255), phone VARCHAR(30), status ENUM('pending','approved','rejected') DEFAULT 'pending', ad_on TINYINT DEFAULT 0, ad_text VARCHAR(160) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(stop_id) REFERENCES stops(id));
CREATE TABLE ad_views(id BIGINT AUTO_INCREMENT PRIMARY KEY, place_id INT NOT NULL, viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, billed TINYINT DEFAULT 0,
  INDEX(place_id,billed), FOREIGN KEY(place_id) REFERENCES places(id) ON DELETE CASCADE);
CREATE TABLE passes(id INT PRIMARY KEY, name VARCHAR(60) NOT NULL, days INT NOT NULL, price_usd DECIMAL(8,2) NOT NULL, tagline VARCHAR(60), features VARCHAR(255));
CREATE TABLE tourist_passes(id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, pass_id INT NOT NULL, code VARCHAR(20) NOT NULL UNIQUE, valid_from DATE NOT NULL, valid_until DATE NOT NULL,
  FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(pass_id) REFERENCES passes(id));
CREATE TABLE payments(id INT AUTO_INCREMENT PRIMARY KEY, order_id VARCHAR(30) NOT NULL UNIQUE, user_id INT NOT NULL, type ENUM('fee','ads','pass') NOT NULL, ref_id INT NULL,
  amount DECIMAL(10,2) NOT NULL, currency VARCHAR(3) NOT NULL, status ENUM('pending','paid','failed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE settings(k VARCHAR(30) PRIMARY KEY, v VARCHAR(100) NOT NULL);

INSERT INTO settings VALUES('monthly_fee','5000'),('ad_rate','2');

-- demo accounts: admin@transitlanka.lk / admin123 , owner@transitlanka.lk / owner123
INSERT INTO users(id,name,email,password_hash,role,paid_until) VALUES
(1,'Admin','admin@transitlanka.lk','$6$eQcXrYr/z1Kllxto$41Hjn7ncu6lLXaE8BnVjnu8Ibx2RamzC9yCViVD4hbPslCT8k03mxC.lwULqE/GWMqgY31q3OM31wvjVH99Ah0','admin',NULL),
(2,'Demo Owner','owner@transitlanka.lk','$6$w09DbLuvZpzMuE6B$ZZD8vZ6QxBhb0gbgF5moLLZxvsOyfMzmcexAf8BaoClzou3Qcfv4AsTMDLfCz31QLf61b2.LbXX2LCUpVa65J1','owner',DATE_ADD(CURDATE(),INTERVAL 30 DAY));

-- Bus stops (coordinates are approximate, replace with surveyed data)
INSERT INTO stops VALUES
(1,'Colombo Fort',6.934400,79.842800),(2,'Pettah',6.936600,79.850500),(3,'Town Hall',6.916600,79.863600),(4,'Borella',6.914700,79.877700),
(5,'Rajagiriya',6.910500,79.893600),(6,'Battaramulla',6.900000,79.918000),(7,'Kottawa',6.841000,79.965000),(8,'Makumbura',6.844600,79.975700),
(9,'Malabe',6.906100,79.958000),(10,'Millennium City (Athurugiriya)',6.875000,79.993000),(11,'Kadawatha',7.001000,79.951000),(12,'Nawinna',6.853000,79.904000),
(13,'Narahenpita',6.897000,79.877400),(14,'Kelaniya University',6.974000,79.917000),(15,'Dematagoda',6.937000,79.878000),(16,'Panadura',6.713000,79.904000),
(17,'Kohuwala',6.864000,79.884000),(18,'Moratuwa',6.773000,79.882000),(19,'Ekala',7.095000,79.900000),(20,'Peliyagoda',6.966700,79.883300),
(21,'Wattala',6.989000,79.892000),(22,'Kollupitiya',6.911500,79.848900),(23,'Bambalapitiya',6.891900,79.855300),(24,'Wellawatta',6.874500,79.860400),
(25,'Liberty Plaza',6.911400,79.850800),(26,'Kahathuduwa',6.750000,79.980000),(27,'Kesbewa',6.798000,79.937000);

-- Routes from the TransitLanka prototype (stop order and minutes are sample data)
INSERT INTO routes VALUES
(1,'CM01','Makumbura ↔ Pettah / Colombo Fort','Kottawa · Battaramulla · Rajagiriya · Borella · Town Hall','Approx. 1 hr 15 min'),
(2,'CM02','Millennium City (Athurugiriya) ↔ Colombo Fort','Malabe · Battaramulla · Rajagiriya · Borella','Approx. 1 hr 30 min'),
(3,'CM03','Makumbura ↔ Kadawatha','Nawinna · Narahenpita · Kelaniya University','Approx. 2 hrs'),
(4,'CM04','Dematagoda ↔ Panadura','Borella · Narahenpita · Kohuwala · Moratuwa','Approx. 1 hr 45 min'),
(5,'CM05','Battaramulla ↔ Ekala','Rajagiriya · Borella · Peliyagoda · Wattala','Approx. 2 hrs'),
(6,'CM06','Kollupitiya Circular','Bambalapitiya · Wellawatta · Liberty Plaza','Approx. 45 min'),
(7,'CM08','Kahathuduwa / Kesbewa ↔ Pettah','Connects southern suburbs with central Colombo.','Journey time not provided');
INSERT INTO route_stops VALUES
(1,1,8,0),(1,2,7,8),(1,3,6,40),(1,4,5,52),(1,5,4,62),(1,6,3,66),(1,7,1,71),(1,8,2,75),
(2,1,10,0),(2,2,9,15),(2,3,6,30),(2,4,5,45),(2,5,4,60),(2,6,3,78),(2,7,1,90),
(3,1,8,0),(3,2,12,20),(3,3,13,60),(3,4,14,100),(3,5,11,120),
(4,1,15,0),(4,2,4,10),(4,3,13,25),(4,4,17,45),(4,5,18,80),(4,6,16,105),
(5,1,6,0),(5,2,5,12),(5,3,4,30),(5,4,20,55),(5,5,21,80),(5,6,19,120),
(6,1,22,0),(6,2,23,15),(6,3,24,28),(6,4,25,45),
(7,1,26,0),(7,2,27,15),(7,3,2,65);

INSERT INTO attractions VALUES
(1,'Galle Face Green','Seaside','Walk by the ocean on Colombo\'s lively waterfront.',1),
(2,'Gangaramaya Temple','Culture','One of Colombo\'s well-known Buddhist temples.',3),
(3,'Colombo National Museum','Culture','Sri Lanka\'s largest museum.',3),
(4,'Independence Square','Heritage','Landmark memorial and colonnaded hall.',3),
(5,'Viharamahadevi Park','Park','Colombo\'s largest public park.',3),
(6,'Fort Railway Station','Transport hub','Main railway hub for the city.',1),
(7,'Pettah Market','Shopping','Busy street markets and local shopping.',2);

INSERT INTO passes VALUES
(1,'3-Day Tourist Pass',3,8.00,'Short stay','Unlimited eligible rides for 3 consecutive days|Digital e-ticket for your phone|Ideal for a short Colombo visit'),
(2,'5-Day Tourist Pass',5,15.00,'Best value','Unlimited eligible rides for 5 consecutive days|Digital e-ticket for your phone|More time to explore Colombo');

-- demo places (owner 2)
INSERT INTO places(owner_id,stop_id,name,type,description,phone,status,ad_on,ad_text) VALUES
(2,1,'Fort Heritage Hotel','Hotel','Boutique hotel 5 minutes from Fort station.','011 234 5678','approved',1,'10% off for TransitLanka travellers!'),
(2,23,'Majestic City Mall','Mall','Shopping, food court and cinema.','011 258 1111','approved',0,''),
(2,4,'Borella Rice & Curry House','Restaurant','Authentic Sri Lankan lunch packets.','011 269 0000','approved',0,''),
(2,24,'Ranmal Ayurveda Spa','Ayurvedic Centre','Certified Ayurvedic treatments.','011 271 2222','approved',0,''),
(2,5,'Rajagiriya Backpackers','Hostel','Dorms from LKR 3,000.','077 123 4567','approved',0,'');
