USE cinema_portal;

-- Optional: replace current movies with the requested set
DELETE FROM movies;

INSERT INTO movies (title, rating, duration_min, genre, subs, release_date, synopsis, status, poster_path) VALUES
('F1', 'PG', 120, 'Sport', 'ENG', '2025-06-26', 'High-speed drama following drivers and teams at the pinnacle of motorsport.', 'now', '/cinema-test/assets/posters/f1.jpg'),
('TRON: Ares', 'PG-13', 130, 'Sci-Fi', 'ENG', '2025-10-09', 'A new chapter in the TRON universe where the digital and physical worlds collide.', 'now', '/cinema-test/assets/posters/tron-ares.jpg'),
('Hamilton', 'PG-13', 160, 'Musical', 'ENG', '2025-09-05', 'The story of American founding father Alexander Hamilton told through a groundbreaking stage musical.', 'now', '/cinema-test/assets/posters/hamilton.jpg'),
('Wicked', 'PG', 150, 'Fantasy', 'ENG', '2025-11-20', 'The untold story of the witches of Oz and an unlikely friendship that changes everything.', 'soon', '/cinema-test/assets/posters/wicked.jpg'),
('Now You See Me', 'PG-13', 125, 'Thriller', 'ENG', '2025-11-13', 'Illusionists pull off daring heists during their performances while staying one step ahead of the law.', 'now', '/cinema-test/assets/posters/now-you-see-me.jpg'),
('Zootopia 2', 'PG', 105, 'Animation', 'ENG', '2025-11-27', 'Judy Hopps and Nick Wilde return for another adventure in the bustling city of Zootopia.', 'soon', '/cinema-test/assets/posters/zootopia-2.jpg'),
('Jujutsu Kaisen', 'M', 112, 'Anime', 'ENG/JPN', '2025-11-13', 'A dark fantasy set in a world where sorcerers fight curses born of human malice.', 'now', '/cinema-test/assets/posters/jujutsu-kaisen.jpg');

