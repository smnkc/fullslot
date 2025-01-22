-- Veritabanını oluştur
DROP DATABASE IF EXISTS slot;
CREATE DATABASE slot CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE slot;

-- Kullanıcılar tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    credits DECIMAL(15,2) DEFAULT 1000.00,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- İşlemler tablosu
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Oyun geçmişi tablosu
CREATE TABLE game_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_type VARCHAR(50) NOT NULL,
    bet_amount DECIMAL(15,2) NOT NULL,
    win_amount DECIMAL(15,2) NOT NULL,
    result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Arşivlenmiş işlemler tablosu
CREATE TABLE transactions_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Arşivlenmiş oyun geçmişi tablosu
CREATE TABLE game_history_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_type VARCHAR(50) NOT NULL,
    bet_amount DECIMAL(15,2) NOT NULL,
    win_amount DECIMAL(15,2) NOT NULL,
    result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Settings tablosu (sayısal değerler için)
CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Text Settings tablosu (metin değerler için)
CREATE TABLE text_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Varsayılan sayısal ayarlar
INSERT INTO settings (setting_key, setting_value) VALUES 
('min_bet', 10.00),
('max_bet', 1000.00),
('max_win', 10000.00),
('max_daily_bet', 10000.00),
('initial_credits', 1000.00),
('maintenance_mode', 0);

-- Varsayılan metin ayarları
INSERT INTO text_settings (setting_key, setting_value) VALUES 
('site_name', 'Slot Oyunu');

-- Admin ve test hesaplarını oluştur
INSERT INTO users (username, password, email, is_admin, credits) VALUES 
('admin', '$2y$10$eRGCs3y7UeBj2UlVBfA.tOM1EUIrOcsSBasNqf0TJmCn2sThHHqwC', 'admin@localhost', TRUE, 9999999.99),
('test', '$2y$10$eRGCs3y7UeBj2UlVBfA.tOM1EUIrOcsSBasNqf0TJmCn2sThHHqwC', 'test@localhost', FALSE, 1000.00); 