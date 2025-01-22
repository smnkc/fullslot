-- Tablo oluşturma komutları
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Veritabanını seç
USE osmanak1_slot;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `credits` DECIMAL(15,2) DEFAULT 1000.00,
    `is_admin` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- İşlemler tablosu
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Oyun geçmişi tablosu
CREATE TABLE IF NOT EXISTS `game_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `game_type` VARCHAR(50) NOT NULL,
    `bet_amount` DECIMAL(15,2) NOT NULL,
    `win_amount` DECIMAL(15,2) NOT NULL,
    `result` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Arşivlenmiş işlemler tablosu
CREATE TABLE IF NOT EXISTS `transactions_archive` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Arşivlenmiş oyun geçmişi tablosu
CREATE TABLE IF NOT EXISTS `game_history_archive` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `game_type` VARCHAR(50) NOT NULL,
    `bet_amount` DECIMAL(15,2) NOT NULL,
    `win_amount` DECIMAL(15,2) NOT NULL,
    `result` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Settings tablosu
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` DECIMAL(15,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Text Settings tablosu
CREATE TABLE IF NOT EXISTS `text_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Varsayılan ayarları ekle
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
('min_bet', 10.00),
('max_bet', 1000.00),
('max_win', 10000.00),
('max_daily_bet', 10000.00),
('initial_credits', 1000.00),
('maintenance_mode', 0);

-- Varsayılan metin ayarları
INSERT INTO `text_settings` (`setting_key`, `setting_value`) VALUES 
('site_name', 'Slot Oyunu');

-- Admin hesabını oluştur
INSERT INTO `users` (`username`, `password`, `email`, `is_admin`, `credits`) VALUES 
('admin', '$2y$10$eRGCs3y7UeBj2UlVBfA.tOM1EUIrOcsSBasNqf0TJmCn2sThHHqwC', 'admin@osmanakca.org', TRUE, 9999999.99);