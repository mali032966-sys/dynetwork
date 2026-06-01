-- =====================================================================
-- DYNOVA NETWORK – Complete SQL Schema (MySQL 5.7+ / MariaDB 10.3+)
-- Currency: PKR.  Engine: InnoDB.  Charset: utf8mb4.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS task_completions;
DROP TABLE IF EXISTS withdrawals;
DROP TABLE IF EXISTS deposits;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS salaries;
DROP TABLE IF EXISTS salary_ranks;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS admin_settings;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------ users
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL DEFAULT '',
  whatsapp VARCHAR(32) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  referral_code VARCHAR(32) NOT NULL UNIQUE,
  referred_by INT UNSIGNED NULL,
  balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  task_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  referral_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  salary_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  deposit_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  rank_name VARCHAR(40) NOT NULL DEFAULT '',
  is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  remember_token VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_referred_by (referred_by),
  CONSTRAINT fk_users_ref FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ admins
CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Administrator',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ admin_settings (key/value)
CREATE TABLE admin_settings (
  setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ payment_methods
CREATE TABLE payment_methods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,             -- "JazzCash", "EasyPesa"
  account_title VARCHAR(120) NOT NULL,
  account_number VARCHAR(60) NOT NULL,
  instructions TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ tasks (video rating)
CREATE TABLE tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  video_url VARCHAR(500) NOT NULL,
  description TEXT NULL,
  reward DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ task_completions
CREATE TABLE task_completions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  task_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  reward DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_task (task_id),
  INDEX idx_user_day (user_id, created_at),
  CONSTRAINT fk_tc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_tc_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ deposits
CREATE TABLE deposits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method VARCHAR(40) NOT NULL,          -- JazzCash / EasyPesa
  transaction_id VARCHAR(120) NOT NULL,
  sender_account VARCHAR(60) NULL,
  screenshot VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME NULL,
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  CONSTRAINT fk_dep_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ withdrawals
CREATE TABLE withdrawals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method VARCHAR(40) NOT NULL,
  account_number VARCHAR(60) NOT NULL,
  account_title VARCHAR(120) NOT NULL,
  status ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME NULL,
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  CONSTRAINT fk_wd_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ referrals (commission ledger)
CREATE TABLE referrals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,         -- referrer who earned bonus
  source_user_id INT UNSIGNED NOT NULL,  -- user whose task triggered it
  level TINYINT NOT NULL,                -- 1 / 2 / 3
  source_completion_id INT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  percent DECIMAL(5,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_source (source_user_id),
  CONSTRAINT fk_ref_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ref_src  FOREIGN KEY (source_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ salary_ranks
CREATE TABLE salary_ranks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL,
  emoji VARCHAR(10) NOT NULL DEFAULT '',
  min_referrals INT UNSIGNED NOT NULL DEFAULT 0,
  min_business DECIMAL(14,2) NOT NULL DEFAULT 0,
  weekly_salary DECIMAL(10,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ salaries (paid ledger)
CREATE TABLE salaries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  rank_name VARCHAR(40) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  week_ending DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  UNIQUE KEY uniq_user_week (user_id, week_ending),
  CONSTRAINT fk_sal_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------ transactions (full ledger)
CREATE TABLE transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('deposit','task','referral','salary','withdrawal','admin_adjust') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,         -- positive=credit, negative=debit
  meta VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_type (type),
  CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Admin (email: admin@dynova.com, password: password)
INSERT INTO admins (email, password_hash, name) VALUES
  ('admin@dynova.com', '$2y$10$0Pxa5xTE/cdZSJ./WvsGjeyrg2t1.UmRsEzwlh70KtHR8gO9ERgmC', 'Administrator');

-- Settings
INSERT INTO admin_settings (setting_key, setting_value) VALUES
  ('referral_l1', '10'),
  ('referral_l2', '5'),
  ('referral_l3', '2.5'),
  ('daily_task_limit', '25'),
  ('min_withdrawal', '500'),
  ('site_name', 'DYNOVA NETWORK'),
  ('site_tagline', 'Rate. Earn. Refer.');

-- Payment methods
INSERT INTO payment_methods (name, account_title, account_number, instructions, is_active) VALUES
  ('JazzCash', 'Dynova Network', '03001234567', 'Send the exact amount and copy the Transaction ID (TID) from the SMS.', 1),
  ('EasyPesa', 'Dynova Network', '03451234567', 'Send the exact amount and paste the EasyPesa TID below.', 1);

-- Tasks (sample)
INSERT INTO tasks (title, video_url, description, reward, is_active) VALUES
  ('iPhone 15 Pro – Product Review', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Watch the review and rate the production quality.', 50.00, 1),
  ('Samsung Galaxy S24 – First Look',  'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Rate honestly from 1 to 5 stars.', 50.00, 1),
  ('Best Budget Phones 2026',          'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Rate content usefulness.', 75.00, 1),
  ('Top 10 Tech Gadgets',              'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Rate the presentation.', 60.00, 1),
  ('Smart Home Setup Guide',           'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Rate the tutorial quality.', 80.00, 1);

-- Salary ranks (weekly)
INSERT INTO salary_ranks (name, emoji, min_referrals, min_business, weekly_salary, sort_order) VALUES
  ('Bronze', '🥉', 5,  10000.00,   500.00, 1),
  ('Silver', '🥈', 20, 50000.00,  2000.00, 2),
  ('Gold',   '🥇', 50, 200000.00, 5000.00, 3),
  ('Diamond','💎', 100,500000.00, 12000.00, 4);
