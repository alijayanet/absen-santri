CREATE DATABASE IF NOT EXISTS absen_santri_db;
USE absen_santri_db;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT '',
  `role` varchar(20) DEFAULT 'admin',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password for admin is 'admin' (hashed)
INSERT INTO `users` (`username`, `password`, `name`, `phone`, `role`) VALUES
('admin', '$2y$10$D5LCWF84a5ngXgAmQfQTXe6FDtCG0DVjOma4tn/9buxIGRcBQdRaS', 'Administrator', '', 'admin');

CREATE TABLE IF NOT EXISTS `santri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `gender` enum('L','P') NOT NULL,
  `parent_phone` varchar(20) NOT NULL,
  `photo` varchar(255) DEFAULT '',
  `teacher_id` int(11) DEFAULT NULL,
  `qrcode_hash` varchar(100) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nis` (`nis`),
  UNIQUE KEY `qrcode_hash` (`qrcode_hash`),
  KEY `idx_teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `santri_id` int(11) NOT NULL,
  `scan_date` date NOT NULL,
  `scan_time` time NOT NULL,
  `status` varchar(20) DEFAULT 'Hadir',
  PRIMARY KEY (`id`),
  KEY `santri_id` (`santri_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`santri_id`) REFERENCES `santri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(100) DEFAULT 'Absen Santri Digital',
  `mpwa_url` varchar(255) DEFAULT '',
  `mpwa_token` varchar(255) DEFAULT '',
  `mpwa_sender` varchar(50) DEFAULT '',
  `admin_phone` varchar(20) DEFAULT '',
  `institution_type` varchar(50) DEFAULT 'pesantren',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`app_name`, `institution_type`) VALUES ('Absen Santri Digital', 'pesantren');
