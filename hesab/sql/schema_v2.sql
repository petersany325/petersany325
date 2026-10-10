SET NAMES utf8mb4;

-- Users & permissions
CREATE TABLE IF NOT EXISTS permissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(80) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
  role VARCHAR(40) NOT NULL,
  permission_code VARCHAR(80) NOT NULL,
  PRIMARY KEY (role, permission_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_permissions (
  user_id INT UNSIGNED NOT NULL,
  permission_code VARCHAR(80) NOT NULL,
  allowed TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (user_id, permission_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity VARCHAR(80) NULL,
  entity_id INT UNSIGNED NULL,
  detail TEXT NULL,
  ip VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_user (user_id),
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Floating tafsili (تفصیلی شناور)
CREATE TABLE IF NOT EXISTS tafsili_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tafsili_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type_id INT UNSIGNED NOT NULL,
  code VARCHAR(60) NOT NULL,
  title VARCHAR(190) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  meta_json JSON NULL,
  UNIQUE KEY uq_tafsili (type_id, code),
  CONSTRAINT fk_ti_type FOREIGN KEY (type_id) REFERENCES tafsili_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chart of accounts (unlimited tree) + legacy bridge fields on moein
CREATE TABLE IF NOT EXISTS accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NULL,
  code VARCHAR(40) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  account_type ENUM('group','kol','moein','other') NOT NULL DEFAULT 'other',
  nature ENUM('debit','credit','neutral') NOT NULL DEFAULT 'neutral',
  allow_debit TINYINT(1) NOT NULL DEFAULT 1,
  allow_credit TINYINT(1) NOT NULL DEFAULT 1,
  require_balance_check TINYINT(1) NOT NULL DEFAULT 1,
  is_leaf TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  legacy_moein_id INT UNSIGNED NULL,
  KEY idx_acc_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moein_tafsili_map (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  moein_id INT UNSIGNED NOT NULL,
  level TINYINT UNSIGNED NOT NULL,
  tafsili_type_id INT UNSIGNED NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_moein_level (moein_id, level),
  CONSTRAINT fk_mtm_type FOREIGN KEY (tafsili_type_id) REFERENCES tafsili_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhance moein controls
ALTER TABLE accounts_moein
  ADD COLUMN IF NOT EXISTS nature ENUM('debit','credit','neutral') NOT NULL DEFAULT 'neutral',
  ADD COLUMN IF NOT EXISTS allow_debit TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS allow_credit TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS control_flags VARCHAR(255) NULL;

-- Voucher types
CREATE TABLE IF NOT EXISTS voucher_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  description_template VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upgrade vouchers
ALTER TABLE vouchers
  ADD COLUMN IF NOT EXISTS voucher_type_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS reviewed_by INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS locked_by INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS locked_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS source_module VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS source_id INT UNSIGNED NULL;

-- Expand status via new column if enum update is hard
ALTER TABLE vouchers
  MODIFY COLUMN status ENUM('draft','operational','reviewed','locked','void','posted') NOT NULL DEFAULT 'draft';

ALTER TABLE voucher_lines
  ADD COLUMN IF NOT EXISTS tafsili1_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS tafsili2_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS tafsili3_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS project_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS cost_center_id INT UNSIGNED NULL;

CREATE TABLE IF NOT EXISTS fiscal_periods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year_id INT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  is_closed TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_fp_year FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE fiscal_years
  ADD COLUMN IF NOT EXISTS is_closed TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS closed_at DATETIME NULL;

-- Treasury
CREATE TABLE IF NOT EXISTS bank_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  bank_name VARCHAR(120) NULL,
  account_no VARCHAR(80) NULL,
  moein_id INT UNSIGNED NULL,
  tafsili_id INT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checkbooks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_account_id INT UNSIGNED NOT NULL,
  series VARCHAR(80) NOT NULL,
  from_no INT UNSIGNED NOT NULL,
  to_no INT UNSIGNED NOT NULL,
  next_no INT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_cb_bank FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  checkbook_id INT UNSIGNED NULL,
  bank_account_id INT UNSIGNED NULL,
  check_no VARCHAR(40) NOT NULL,
  check_date DATE NOT NULL,
  due_date DATE NULL,
  amount DECIMAL(18,0) NOT NULL,
  payee VARCHAR(190) NULL,
  status ENUM('blank','issued','paid','returned','cancelled','in_hand','deposited') NOT NULL DEFAULT 'blank',
  direction ENUM('payable','receivable') NOT NULL DEFAULT 'payable',
  voucher_id INT UNSIGNED NULL,
  description VARCHAR(500) NULL,
  UNIQUE KEY uq_check (bank_account_id, check_no, direction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_patterns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  voucher_type_id INT UNSIGNED NULL,
  payload_json JSON NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS treasury_docs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doc_type ENUM('receive','pay') NOT NULL,
  number INT UNSIGNED NOT NULL,
  doc_date DATE NOT NULL,
  party_tafsili_id INT UNSIGNED NULL,
  bank_account_id INT UNSIGNED NULL,
  amount DECIMAL(18,0) NOT NULL DEFAULT 0,
  method ENUM('cash','bank','check') NOT NULL DEFAULT 'cash',
  check_id INT UNSIGNED NULL,
  description VARCHAR(500) NULL,
  voucher_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS print_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  entity VARCHAR(60) NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moadian_settings (
  id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
  economic_code VARCHAR(40) NULL,
  private_key TEXT NULL,
  memory_id VARCHAR(80) NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 0,
  last_sync_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moadian_invoices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NULL,
  tax_id VARCHAR(80) NULL,
  status ENUM('pending','sent','accepted','rejected') NOT NULL DEFAULT 'pending',
  payload_json JSON NULL,
  response_json JSON NULL,
  sent_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_reconciliations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank_account_id INT UNSIGNED NOT NULL,
  statement_date DATE NOT NULL,
  statement_balance DECIMAL(18,0) NOT NULL DEFAULT 0,
  book_balance DECIMAL(18,0) NOT NULL DEFAULT 0,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
