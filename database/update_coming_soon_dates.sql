-- Add specific shows on 14/11/2025 for Coming Soon movies
-- Run this after base seed to clearly separate Now Showing vs Coming Soon

USE cinema_portal;

INSERT INTO shows (movie_id, screen_id, start_at, base_price)
SELECT m.id,
       sc.id,
       CONCAT('2025-11-14 ', t.clock) AS start_at,
       CASE WHEN sc.name = 'IMAX 1' THEN 22.00 ELSE 18.00 END AS base_price
FROM movies m
JOIN (
  SELECT MIN(id) AS id, name FROM screens WHERE name IN ('Screen 1','Screen 2','IMAX 1') GROUP BY name
) sc ON 1=1
JOIN (
  SELECT '12:00:00' AS clock UNION ALL
  SELECT '18:00:00'
) t ON 1=1
LEFT JOIN shows s
  ON s.movie_id = m.id
 AND s.screen_id = sc.id
 AND s.start_at = CONCAT('2025-11-14 ', t.clock)
WHERE m.status = 'soon'
  AND s.id IS NULL;

