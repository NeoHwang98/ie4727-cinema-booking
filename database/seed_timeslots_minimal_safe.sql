-- Minimal and safe seed for `shows` when FKs block TRUNCATE
-- This script clears dependent rows, then inserts a tiny, conflict‑free dataset.

USE cinema_portal;

-- Safely clear dependent booking rows and shows in one session
SET @OLD_FK := @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM booking_items;  -- remove rows referencing shows
DELETE FROM shows;          -- shows can now be cleared
SET FOREIGN_KEY_CHECKS = @OLD_FK;

-- Resolve one ID per screen name
SET @scr1 := (SELECT MIN(id) FROM screens WHERE name = 'Screen 1');
SET @scr2 := (SELECT MIN(id) FROM screens WHERE name = 'Screen 2');
SET @imax := (SELECT MIN(id) FROM screens WHERE name = 'IMAX 1');

-- NOW SHOWING — one session per movie, rotated across screens and days
INSERT INTO shows (movie_id, screen_id, start_at, base_price)
SELECT t.id,
       CASE MOD(t.rn, 3)
         WHEN 0 THEN @scr1
         WHEN 1 THEN @scr2
         ELSE @imax
       END AS screen_id,
       CASE MOD(t.rn, 3)
         WHEN 0 THEN CONCAT(DATE_ADD(CURDATE(), INTERVAL FLOOR(t.rn/3)+1 DAY), ' 12:00:00')
         WHEN 1 THEN CONCAT(DATE_ADD(CURDATE(), INTERVAL FLOOR(t.rn/3)+1 DAY), ' 15:00:00')
         ELSE      CONCAT(DATE_ADD(CURDATE(), INTERVAL FLOOR(t.rn/3)+1 DAY), ' 18:00:00')
       END AS start_at,
       CASE MOD(t.rn, 3)
         WHEN 2 THEN 22.00  -- IMAX higher price
         ELSE 18.00
       END AS base_price
FROM (
  SELECT m.id, (@r1:=@r1+1) AS rn
  FROM (SELECT @r1 := -1) r, movies m
  WHERE m.status = 'now'
  ORDER BY m.id
) AS t;

-- COMING SOON — one session per movie on a clear future date
INSERT INTO shows (movie_id, screen_id, start_at, base_price)
SELECT t.id,
       CASE MOD(t.rn, 3)
         WHEN 0 THEN @scr1
         WHEN 1 THEN @scr2
         ELSE @imax
       END AS screen_id,
       CONCAT('2025-11-14 ', CASE MOD(t.rn, 3)
                               WHEN 0 THEN '12:00:00'
                               WHEN 1 THEN '15:00:00'
                               ELSE      '18:00:00'
                             END) AS start_at,
       CASE MOD(t.rn, 3)
         WHEN 2 THEN 22.00
         ELSE 18.00
       END AS base_price
FROM (
  SELECT m.id, (@r2:=@r2+1) AS rn
  FROM (SELECT @r2 := -1) r, movies m
  WHERE m.status = 'soon'
  ORDER BY m.id
) AS t;

