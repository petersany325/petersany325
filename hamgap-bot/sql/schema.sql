CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  telegram_id BIGINT NOT NULL,
  username VARCHAR(64) NULL,
  first_name VARCHAR(128) NULL,
  gender ENUM('male','female','shemale') NULL,
  age TINYINT UNSIGNED NULL,
  city VARCHAR(64) NULL,
  province VARCHAR(64) NULL,
  coins INT NOT NULL DEFAULT 35,
  status ENUM('idle','searching','chatting','banned') NOT NULL DEFAULT 'idle',
  search_pref VARCHAR(32) NULL,
  flow VARCHAR(64) NULL,
  ui_messages TEXT NULL,
  partner_id BIGINT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_telegram (telegram_id),
  KEY idx_status_pref (status, search_pref, gender),
  KEY idx_partner (partner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chats (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_a BIGINT NOT NULL,
  user_b BIGINT NOT NULL,
  match_type VARCHAR(32) NOT NULL DEFAULT 'any',
  status ENUM('active','ended') NOT NULL DEFAULT 'active',
  started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ended_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_active (status, user_a, user_b)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reporter_id BIGINT NOT NULL,
  reported_id BIGINT NOT NULL,
  chat_id BIGINT NULL,
  reason VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coin_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  amount INT NOT NULL,
  reason VARCHAR(64) NOT NULL,
  meta VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
