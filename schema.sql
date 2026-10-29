-- ===================================================
-- Expense Tracker Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ===================================================

CREATE DATABASE IF NOT EXISTS `expense_tracker_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `expense_tracker_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
    `currency` VARCHAR(10) DEFAULT 'INR',
    `currency_symbol` VARCHAR(10) DEFAULT '₹',
    `date_format` VARCHAR(20) DEFAULT 'd M Y',
    `theme` VARCHAR(20) DEFAULT 'light',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL, -- NULL means system default category
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('expense', 'income') NOT NULL,
    `icon` VARCHAR(100) NOT NULL DEFAULT 'fa-tags',
    `color` VARCHAR(20) NOT NULL DEFAULT '#FF9F43',
    `bg_color` VARCHAR(20) NOT NULL DEFAULT '#FFF3E0',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_type` (`user_id`, `type`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. EMIs Table (Loans & Installments)
CREATE TABLE IF NOT EXISTS `emis` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `loan_name` VARCHAR(150) NOT NULL,
    `lender` VARCHAR(150) NOT NULL,
    `principal_amount` DECIMAL(12, 2) NOT NULL,
    `interest_rate` DECIMAL(5, 2) NOT NULL, -- Annual percentage rate
    `emi_amount` DECIMAL(12, 2) NOT NULL, -- Monthly EMI
    `tenure_months` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `due_day` INT NOT NULL, -- 1-31 day of month
    `outstanding_balance` DECIMAL(12, 2) NOT NULL,
    `total_interest` DECIMAL(12, 2) NOT NULL,
    `status` ENUM('active', 'completed', 'closed') DEFAULT 'active',
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_status` (`user_id`, `status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Transactions Table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NULL,
    `type` ENUM('expense', 'income') NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME DEFAULT '12:00:00',
    `payment_method` VARCHAR(50) DEFAULT 'UPI',
    `notes` TEXT NULL,
    `receipt_file` VARCHAR(255) NULL,
    `emi_id` INT NULL,
    `recurring_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_date` (`user_id`, `date`),
    INDEX `idx_user_type` (`user_id`, `type`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`emi_id`) REFERENCES `emis`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. EMI Payments Table
CREATE TABLE IF NOT EXISTS `emi_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `emi_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `installment_number` INT NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount_paid` DECIMAL(12, 2) NOT NULL,
    `principal_component` DECIMAL(12, 2) DEFAULT 0.00,
    `interest_component` DECIMAL(12, 2) DEFAULT 0.00,
    `payment_method` VARCHAR(50) DEFAULT 'Bank Transfer',
    `transaction_id` INT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_emi_installment` (`emi_id`, `installment_number`),
    INDEX `idx_user_emi` (`user_id`, `emi_id`),
    FOREIGN KEY (`emi_id`) REFERENCES `emis`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Budgets Table
CREATE TABLE IF NOT EXISTS `budgets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NULL, -- NULL means overall monthly budget
    `month` INT NOT NULL, -- 1-12
    `year` INT NOT NULL, -- e.g. 2026
    `amount` DECIMAL(12, 2) NOT NULL,
    `alert_threshold` INT DEFAULT 80, -- Percent threshold e.g. 80%
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_budget` (`user_id`, `category_id`, `month`, `year`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Recurring Transactions Table
CREATE TABLE IF NOT EXISTS `recurring_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `type` ENUM('expense', 'income') NOT NULL,
    `category_id` INT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `frequency` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
    `start_date` DATE NOT NULL,
    `next_due_date` DATE NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'UPI',
    `is_active` TINYINT(1) DEFAULT 1,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_status` (`user_id`, `is_active`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Savings Goals Table
CREATE TABLE IF NOT EXISTS `savings_goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `target_amount` DECIMAL(12, 2) NOT NULL,
    `current_amount` DECIMAL(12, 2) DEFAULT 0.00,
    `target_date` DATE NOT NULL,
    `category_icon` VARCHAR(50) DEFAULT 'fa-bullseye',
    `color` VARCHAR(20) DEFAULT '#10B981',
    `status` ENUM('active', 'achieved', 'cancelled') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Savings Contributions Table
CREATE TABLE IF NOT EXISTS `savings_contributions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `goal_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `type` ENUM('deposit', 'withdraw') DEFAULT 'deposit',
    `date` DATE NOT NULL,
    `notes` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`goal_id`) REFERENCES `savings_goals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info',
    `is_read` TINYINT(1) DEFAULT 0,
    `link` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Categories (Global, user_id = NULL)
INSERT IGNORE INTO `categories` (`id`, `user_id`, `name`, `type`, `icon`, `color`, `bg_color`) VALUES
(1, NULL, 'Food & Dining', 'expense', 'fa-utensils', '#FF8A65', '#FFF3E0'),
(2, NULL, 'Transport', 'expense', 'fa-car-side', '#4FC3F7', '#E1F5FE'),
(3, NULL, 'Shopping', 'expense', 'fa-bag-shopping', '#F06292', '#FCE4EC'),
(4, NULL, 'Entertainment', 'expense', 'fa-film', '#BA68C8', '#F3E5F5'),
(5, NULL, 'Grocery', 'expense', 'fa-basket-shopping', '#81C784', '#E8F8F0'),
(6, NULL, 'Bills & Utilities', 'expense', 'fa-receipt', '#FFB74D', '#FFF8E1'),
(7, NULL, 'Healthcare', 'expense', 'fa-heart-pulse', '#E57373', '#FFEBEE'),
(8, NULL, 'Education', 'expense', 'fa-graduation-cap', '#64B5F6', '#E3F2FD'),
(9, NULL, 'EMI & Loans', 'expense', 'fa-landmark', '#9575CD', '#EDE7F6'),
(10, NULL, 'Personal Care', 'expense', 'fa-spa', '#F48FB1', '#FCE4EC'),
(11, NULL, 'Others', 'expense', 'fa-shapes', '#90A4AE', '#ECEFF1'),

(12, NULL, 'Salary', 'income', 'fa-money-bill-wave', '#4CAF50', '#E8F5E9'),
(13, NULL, 'Freelance', 'income', 'fa-laptop-code', '#26A69A', '#E0F2F1'),
(14, NULL, 'Investments', 'income', 'fa-chart-line', '#42A5F5', '#E3F2FD'),
(15, NULL, 'Rental Income', 'income', 'fa-house-chimney', '#FFA726', '#FFF3E0'),
(16, NULL, 'Business', 'income', 'fa-briefcase', '#AB47BC', '#F3E5F5'),
(17, NULL, 'Gift & Cashback', 'income', 'fa-gift', '#EC407A', '#FCE4EC'),
(18, NULL, 'Other Income', 'income', 'fa-wallet', '#78909C', '#ECEFF1');

