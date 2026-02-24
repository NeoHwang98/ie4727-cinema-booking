USE cinema_portal;

-- Movies
INSERT INTO movies (title, rating, duration_min, genre, subs, release_date, synopsis, status) VALUES
('The Red Horizon', 'M', 124, 'Adventure', 'ENG', DATE_ADD(DATE(NOW()), INTERVAL -15 DAY), 'A gripping tale of survival and hope across uncharted lands.', 'now'),
('Neon Nights', 'PG', 102, 'Drama', 'ENG', DATE_ADD(DATE(NOW()), INTERVAL -5 DAY), 'A coming-of-age story in a city that never sleeps.', 'now'),
('Quantum Thief', 'MA15+', 113, 'Sci-Fi', 'ENG/CHI', DATE_ADD(DATE(NOW()), INTERVAL 2 DAY), 'A mastermind pulls off heists by bending time itself.', 'now'),
('Sundown Serenade', 'PG', 95, 'Music', 'ENG', DATE_ADD(DATE(NOW()), INTERVAL 20 DAY), 'A heartwarming journey of a musician rediscovering purpose.', 'soon'),
('Voyagers', 'PG', 110, 'Sci-Fi', 'ENG/IND', DATE_ADD(DATE(NOW()), INTERVAL 35 DAY), 'Explorers set out to find a new home among the stars.', 'soon');

-- Cinemas
INSERT INTO cinemas (name, suburb) VALUES
('NECT Central', 'Sydney CBD'),
('NECT Riverside', 'Parramatta');

-- Screens
INSERT INTO screens (cinema_id, name, capacity) VALUES
(1, 'Screen 1', 120),
(1, 'Screen 2', 90),
(1, 'Screen 3', 70),
(2, 'Screen 1', 100),
(2, 'Screen 2', 80),
(2, 'Screen 3', 60);

-- Shows (next 3 days, multiple sessions)
INSERT INTO shows (movie_id, screen_id, start_at, base_price, seats_sold)
VALUES
-- Day 1
(1, 1, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 11 HOUR, 18.00, 0),
(2, 2, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 14 HOUR, 17.50, 0),
(3, 3, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 19 HOUR, 19.50, 0),
(1, 4, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 16 HOUR, 18.50, 0),
(2, 5, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 20 HOUR, 17.00, 0),

-- Day 2
(1, 2, DATE_ADD(DATE(NOW()), INTERVAL 2 DAY) + INTERVAL 13 HOUR, 18.00, 0),
(2, 1, DATE_ADD(DATE(NOW()), INTERVAL 2 DAY) + INTERVAL 18 HOUR, 17.50, 0),
(3, 6, DATE_ADD(DATE(NOW()), INTERVAL 2 DAY) + INTERVAL 19 HOUR, 19.50, 0),
(1, 5, DATE_ADD(DATE(NOW()), INTERVAL 2 DAY) + INTERVAL 15 HOUR, 18.50, 0),
(2, 4, DATE_ADD(DATE(NOW()), INTERVAL 2 DAY) + INTERVAL 20 HOUR, 17.00, 0),

-- Day 3
(3, 1, DATE_ADD(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 12 HOUR, 19.50, 0),
(2, 2, DATE_ADD(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 15 HOUR, 17.50, 0),
(1, 3, DATE_ADD(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 19 HOUR, 18.50, 0),
(3, 4, DATE_ADD(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 20 HOUR, 19.50, 0);

-- Users
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@example.com', SHA2('Admin1234!', 256), 'admin'),
('Jane Doe', 'jane@example.com', SHA2('Password123!', 256), 'user');

-- Coupons
INSERT INTO coupons (code, description, discount_type, value, active, min_total, expires_at) VALUES
('WELCOME10', '10% off for new users', 'percent', 10.00, 1, 0.00, DATE_ADD(DATE(NOW()), INTERVAL 60 DAY)),
('FIVEOFF', '$5 off orders over $20', 'amount', 5.00, 1, 20.00, DATE_ADD(DATE(NOW()), INTERVAL 90 DAY));
