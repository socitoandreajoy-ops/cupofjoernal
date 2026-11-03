-- database/update_v2.sql
-- Safe to run more than once. Run it in phpMyAdmin (select the `cupofjoernal` database -> Import) if you already imported the old
-- cupofjoernal.sql and do not want to lose your data. Fresh installs do not need it (the main file has it all).
USE `cupofjoernal`;

--
-- Table structure for table `bad_words` (managed by Admin > Bad Words)
--
CREATE TABLE IF NOT EXISTS `bad_words` (
  `word_id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`word_id`),
  UNIQUE KEY `uq_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `bad_words` (`word`) VALUES
('fuck'),
('shit'),
('bitch'),
('asshole'),
('ass'),
('bastard'),
('damn'),
('crap'),
('dick'),
('piss'),
('gago'),
('tangina'),
('putangina'),
('bobo'),
('tanga'),
('ulol'),
('pakyu'),
('pakshet'),
('leche'),
('lintik'),
('tarantado'),
('buwisit'),
('siraulo');

--
-- Table structure for table `password_resets` (forgot-password links sent by email)
--
CREATE TABLE IF NOT EXISTS `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `uq_token` (`token_hash`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `remember_tokens` ("Keep me logged in" cookie)
--
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` char(24) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `uq_selector` (`selector`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
