-- Seed additional timeslots for the next 3 days for all Now Showing movies
-- Usage: Import in phpMyAdmin or MySQL CLI after schema/seed
-- Ensures no duplicate timeslots are created (same movie, screen, datetime)

USE cinema_portal;

INSERT INTO shows (movie_id, screen_id, start_at, base_price)
SELECT m.id,
       sc.id,
       ts.start_at,
       CASE WHEN sc.name = 'IMAX 1' THEN 22.00 ELSE 18.00 END AS price
FROM movies m
JOIN (
  SELECT MIN(id) AS id, name FROM screens WHERE name IN ('Screen 1','Screen 2','IMAX 1') GROUP BY name
) sc ON 1=1
JOIN (
  -- Day 1 (tomorrow)
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 12:00:00') AS start_at UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 15:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 18:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 21:00:00') UNION ALL
  -- Day 2
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 12:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 15:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 18:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 21:00:00') UNION ALL
  -- Day 3
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 12:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 15:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 18:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 21:00:00') UNION ALL
  -- Day 7 (extra for Coming Soon pre-sales)
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), ' 12:00:00') UNION ALL
  SELECT CONCAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), ' 18:00:00')
) ts ON 1=1
LEFT JOIN shows s
  ON s.movie_id = m.id
 AND s.screen_id = sc.id
 AND s.start_at = ts.start_at
WHERE m.status IN ('now','soon')
  AND s.id IS NULL;
