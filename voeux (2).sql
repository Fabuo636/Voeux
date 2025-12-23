-- --------------------------------------------------------
-- Hôte:                         37.187.28.103
-- Version du serveur:           10.5.29-MariaDB - MariaDB Server
-- SE du serveur:                Linux
-- HeidiSQL Version:             12.12.0.7122
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Listage de la structure de la base pour carte_veux
CREATE DATABASE IF NOT EXISTS `carte_veux` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci */;
USE `carte_veux`;

-- Listage de la structure de table carte_veux. messages
CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) NOT NULL,
  `chosen_candidate_id` bigint(20) DEFAULT NULL,
  `final_content_fr` text DEFAULT NULL,
  `final_content_en` text DEFAULT NULL,
  `public_code` varchar(32) NOT NULL,
  `background` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_code` (`public_code`),
  KEY `idx_messages_request` (`request_id`),
  KEY `idx_messages_expires` (`expires_at`),
  KEY `fk_messages_candidate` (`chosen_candidate_id`),
  CONSTRAINT `fk_messages_candidate` FOREIGN KEY (`chosen_candidate_id`) REFERENCES `message_candidates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_messages_request` FOREIGN KEY (`request_id`) REFERENCES `message_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

-- Listage de la structure de table carte_veux. message_access_logs
CREATE TABLE IF NOT EXISTS `message_access_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) NOT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `channel` varchar(20) DEFAULT NULL,
  `ip_hash` varchar(128) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_access_logs_message` (`message_id`),
  CONSTRAINT `fk_access_logs_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

-- Listage de la structure de table carte_veux. message_candidates
CREATE TABLE IF NOT EXISTS `message_candidates` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) NOT NULL,
  `variant_index` int(11) NOT NULL,
  `content_fr` text DEFAULT NULL,
  `content_en` text DEFAULT NULL,
  `model` varchar(80) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_candidate_variant` (`request_id`,`variant_index`),
  KEY `idx_candidates_request` (`request_id`),
  CONSTRAINT `fk_candidates_request` FOREIGN KEY (`request_id`) REFERENCES `message_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

-- Listage de la structure de table carte_veux. message_requests
CREATE TABLE IF NOT EXISTS `message_requests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `recipient_id` bigint(20) NOT NULL,
  `occasion_id` bigint(20) DEFAULT NULL,
  `relationship_id` bigint(20) DEFAULT NULL,
  `message_type_id` bigint(20) DEFAULT NULL,
  `sender_gender` varchar(20) DEFAULT NULL,
  `recipient_gender` varchar(20) DEFAULT NULL,
  `tone` varchar(80) DEFAULT NULL,
  `constraints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`constraints`)),
  `output_lang` varchar(5) NOT NULL DEFAULT 'fr',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_requests_user` (`user_id`),
  KEY `idx_requests_recipient` (`recipient_id`),
  KEY `idx_requests_status` (`status`),
  KEY `idx_requests_output_lang` (`output_lang`),
  KEY `fk_requests_occasion` (`occasion_id`),
  KEY `fk_requests_relationship` (`relationship_id`),
  KEY `fk_requests_message_type` (`message_type_id`),
  CONSTRAINT `fk_requests_message_type` FOREIGN KEY (`message_type_id`) REFERENCES `message_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_requests_occasion` FOREIGN KEY (`occasion_id`) REFERENCES `occasions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_requests_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `recipients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_requests_relationship` FOREIGN KEY (`relationship_id`) REFERENCES `relationships` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

-- Listage de la structure de table carte_veux. message_types
CREATE TABLE IF NOT EXISTS `message_types` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `label_fr` varchar(120) NOT NULL,
  `label_en` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

-- Listage de la structure de table carte_veux. occasions
CREATE TABLE IF NOT EXISTS `occasions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `label_fr` varchar(120) NOT NULL,
  `label_en` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

-- Listage de la structure de table carte_veux. recipients
CREATE TABLE IF NOT EXISTS `recipients` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `owner_user_id` bigint(20) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recipients_phone` (`phone`),
  KEY `idx_recipients_owner_user` (`owner_user_id`),
  CONSTRAINT `fk_recipients_owner_user` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

-- Listage de la structure de table carte_veux. relationships
CREATE TABLE IF NOT EXISTS `relationships` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `label_fr` varchar(120) NOT NULL,
  `label_en` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

-- Listage de la structure de table carte_veux. users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `Tel` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `Tel` (`Tel`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Les données exportées n'étaient pas sélectionnées.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
