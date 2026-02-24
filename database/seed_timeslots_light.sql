-- Minimal timeslots to populate the site without overwhelming volume
-- Optional: run `TRUNCATE TABLE shows;` first if you want to start fresh

USE cinema_portal;

-- Add 2 times per day for next 2 days for all movies
INSERT INTO shows (movie_id, screen_id, start_at, base_price)
SELECT m.id,
       sc.id,
       CONCAT(DATE_ADD(CURDATE(), INTERVAL d.day_off DAY), ' ', t.clock) AS start_at,
       CASE WHEN sc.name = 'IMAX 1' THEN 22.00 ELSE 18.00 END AS base_price
FROM movies m
JOIN (
  SELECT MIN(id) AS id, name FROM screens WHERE name IN ('Screen 1','Screen 2','IMAX 1') GROUP BY name
) sc ON 1=1
JOIN (SELECT 1 AS day_off UNION ALL SELECT 2) d ON 1=1
JOIN (SELECT '12:00:00' AS clock UNION ALL SELECT '18:00:00') t ON 1=1
LEFT JOIN shows s
  ON s.movie_id = m.id
 AND s.screen_id = sc.id
 AND s.start_at = CONCAT(DATE_ADD(CURDATE(), INTERVAL d.day_off DAY), ' ', t.clock)
WHERE m.status IN ('now','soon')
  AND s.id IS NULL;

-- Ensure Coming Soon also has a clear future date (14 Nov 2025 sample)
-- You can comment this out outside that date range
INSERT INTO shows (movie_id, screen_id, start_at, base_price)
SELECT m.id,
       sc.id,
       CONCAT('2025-11-14 ', t.clock) AS start_at,
       CASE WHEN sc.name = 'IMAX 1' THEN 22.00 ELSE 18.00 END AS base_price
FROM movies m
JOIN (
  SELECT MIN(id) AS id, name FROM screens WHERE name IN ('Screen 1','Screen 2','IMAX 1') GROUP BY name
) sc ON 1=1
JOIN (SELECT '12:00:00' AS clock UNION ALL SELECT '18:00:00') t ON 1=1
LEFT JOIN shows s
  ON s.movie_id = m.id
 AND s.screen_id = sc.id
 AND s.start_at = CONCAT('2025-11-14 ', t.clock)
WHERE m.status = 'soon'
  AND s.id IS NULL;

