-- LivestockHub Phase 1-4 Migration
-- Run in phpMyAdmin on database `livestuchub_db`

ALTER TABLE `user`
  MODIFY `user_status` ENUM('Pending','Active','Suspended','Inactive','Banned') NOT NULL DEFAULT 'Active';

ALTER TABLE `user`
  ADD COLUMN `suspension_ends_at` DATETIME NULL DEFAULT NULL AFTER `user_status`,
  ADD COLUMN `ban_reason` TEXT NULL DEFAULT NULL AFTER `suspension_ends_at`,
  ADD COLUMN `ban_type` ENUM('none','temporary','permanent') NOT NULL DEFAULT 'none' AFTER `ban_reason`;

CREATE TABLE IF NOT EXISTS `farmer_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `valid_id_path` varchar(255) NOT NULL,
  `birth_cert_path` varchar(255) NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `farmer_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_user_id` int(11) NOT NULL,
  `reported_user_id` int(11) NOT NULL,
  `reported_role` enum('Farmer','Buyer') NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `reporter_user_id` (`reporter_user_id`),
  KEY `reported_user_id` (`reported_user_id`),
  CONSTRAINT `reports_reporter_fk` FOREIGN KEY (`reporter_user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `reports_reported_fk` FOREIGN KEY (`reported_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_bans` (
  `ban_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `ban_type` enum('temporary','permanent') NOT NULL,
  `hours` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ban_id`),
  KEY `user_id` (`user_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `user_bans_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `user_bans_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

UPDATE `user` SET `user_status` = 'Active' WHERE `user_role` IN ('Farmer','Buyer','Admin');
