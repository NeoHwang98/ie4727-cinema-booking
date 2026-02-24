-- Minimal, clean timeslots for quick end‑to‑end testing
-- 1 session per movie, rotated across available screens and spread across dates
-- Ensures no two movies share the same (screen_id, start_at)

USE cinema_portal;

-- Start fresh
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE shows;
SET FOREIGN_KEY_CHECKS=1;

-- Cache one id per screen name (single-location install)
SET @scr1 := (SELECT MIN(id) FROM screens WHERE name = 'Screen 1');
SET @scr2 := (SELECT MIN(id) FROM screens WHERE name = 'Screen 2');
SET @imax := (SELECT MIN(id) FROM screens WHERE name = 'IMAX 1');

-- NOW SHOWING: one session per movie
-- Date spread over tomorrow and the day after, times 12:00 and 15:00
INSERT INTO shows (movie_id, screen_id, start_at, base_price)
SELECT x.id,
       CASE MOD(x.rn, 3)
         WHEN 0 THEN @scr1
         WHEN 1 THEN @scr2
         ELSE @imax
       END AS screen_id,
       CASE MOD(x.rn, 2)
         WHEN 0 THEN CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 12:00:00')
         ELSE CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 15:00:00')
       END AS start_at,
       CASE MOD(x.rn, 3)
         WHEN 2 THEN 22.00 ELSE 18.00
       END AS base_price
FROM (
  SELECT m.id, @r1:=@r1+1 AS rn
  FROM movies m
  JOIN (SELECT @r1 := -1) r
  WHERE m.status = 'now'
  ORDER BY m.id
) AS x;

-- COMING SOON: one session per movie, clear future date
-- Uses 2025‑11‑14 at either 12:00 or 18:00 and rotates screens
INSERT INTO shows (movie_id, screen_id, start_at, base_price)
SELECT y.id,
       CASE MOD(y.rn, 3)
         WHEN 0 THEN @scr1
         WHEN 1 THEN @scr2
         ELSE @imax
       END AS screen_id,
       CONCAT('2025-11-14 ', CASE MOD(y.rn, 2) WHEN 0 THEN '12:00:00' ELSE '18:00:00' END) AS start_at,
       CASE MOD(y.rn, 3)
         WHEN 2 THEN 22.00 ELSE 18.00
       END AS base_price
FROM (
  SELECT m.id, @r2:=@r2+1 AS rn
  FROM movies m
  JOIN (SELECT @r2 := -1) r
  WHERE m.status = 'soon'
  ORDER BY m.id
) AS y;

-- Optional: prevent accidental duplicates if this file is applied twice
-- (No unique key on shows by default; rely on TRUNCATE above to reset.)

