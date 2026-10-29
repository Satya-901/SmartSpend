# SmartSpend - Modern Mobile-App Expense & EMI Tracker

A complete, fully functional, mobile-first Expense Tracker and EMI Management web application built with **PHP 8.2+**, **MySQL**, **Bootstrap 5**, and **Chart.js**. The user interface faithfully implements the warm cream pastel mobile-app aesthetic from the design reference, featuring soft cards, custom icons, interactive donut & trend charts, and a floating bottom navigation bar.

---

## 🌟 Key Features

### 1. 🔐 Authentication & Profile Management
- Secure user registration, password hashing (`PASSWORD_DEFAULT`), session fixation protection, and login.
- "Quick Demo Login" button for instant testing with seeded real-world data (`Meow`).
- Profile settings: Edit full name, upload custom profile avatars, and securely change password.

### 2. 📊 Dashboard (Home Screen - Mockup Screen 1)
- **Top Greeting**: Personalized greeting (`Hi, Meow! 👋 Let's manage your money`) with user avatar.
- **Dual Summary Hero Cards**:
  - **Expenses Card**: Light blush background (`#FFF0F0`), large bold expense amount (e.g., `₹3,580`), red indicator arrow, and "This Month" badge.
  - **Income Card**: Soft mint background (`#EDF8F2`), large bold income amount (e.g., `₹2,494`), green indicator arrow, and "This Month" badge.
- **Secondary Cards**: Active EMI commitment (`/month`) and Net Savings.
- **Spending Overview Donut Chart**: Dynamic Chart.js donut chart with total spending centered in the cutout, accompanied by a clean color-coded category legend (percentages and formatted amounts).
- **Recent Transactions**: Itemized list with squircle category icons, merchant/notes, formatted amounts, and relative day badges (*Today*, *Yesterday*).

### 3. 📜 Transactions (Mockup Screen 2)
- Filter pills: **All**, **Income**, and **Expense**.
- Real-time search by notes, merchant, category, or amount.
- Filter modal with category selector and custom date range (`From` / `To`).
- Transactions grouped by relative day (*Today*, *Yesterday*, *12 May 2024*).
- Transaction details modal with receipt viewer.

### 4. 📈 Stats & Analytics (Mockup Screen 3)
- Period filter: *This Month*, *Last Month*, *This Week*, *This Year*.
- **Total Expenses Hero Card** featuring a custom piggy-bank illustration and comparison metric (*e.g., vs last month ⬆ 12.5%*).
- **Expenses by Category** interactive donut breakdown.
- **Daily Spending Trend** smooth curved line chart with point markers and peak spending indicator (*Mon - Sun*).
- **Encouragement Insight Card** (*e.g., "Great job! 🌱 You spent 8% less than last week."*).

### 5. 🏦 Comprehensive EMI & Loan Management
- Track loans/EMIs (e.g. Home Loan, Car Loan, iPhone 15 Pro, Personal Loan).
- Stores loan name, lender, principal amount, interest rate (% p.a.), monthly EMI, tenure (months), start date, monthly due day, and outstanding balance.
- **Auto EMI Calculator**: Automatically calculates monthly EMI and total interest using standard financial formula ($P \times r \times (1+r)^n / ((1+r)^n - 1)$) or allows custom bank EMI amounts.
- **Record Installment Payments**: Prevents duplicate payments, automatically computes interest vs principal components, updates remaining loan balance, logs payment history, and auto-records an expense transaction under "EMI & Loans".
- **Schedule & Payment History**: Modal displaying complete installment log with payment date, principal/interest breakdown, and payment method.

### 6. 🎯 Monthly & Category Budgets
- Set monthly limits for overall spending or specific categories (e.g. Food, Shopping).
- Visual progress bars with dynamic warning thresholds (Alert at 80% / 100% exceeded).
- Over-budget and warning notifications logged automatically.

### 7. 🏆 Savings Goals
- Create savings targets with target dates and colors (e.g. Emergency Fund, Vacation Trip).
- Deposit funds to or withdraw funds from goals with real-time percentage progress tracking.

### 8. 🔄 Recurring Bills & Subscriptions
- Track recurring payments (Netflix, WiFi, Rent, Salary).
- Frequencies: Daily, Weekly, Monthly, Quarterly, Yearly.
- One-click **"Mark Paid & Advance"** records transaction and updates next due date.

### 9. 📅 Finance Calendar
- FullCalendar view displaying daily expenses (red), income (green), EMI due dates (purple), and recurring bills (amber).

### 10. 🏷️ Categories Management
- Custom income and expense categories with FontAwesome icons and color picker.

### 11. 🔔 Notifications & Reminders
- Alerts for overdue EMIs, upcoming installments (due in $\le 3$ days), recurring bills, and budget threshold warnings.

### 12. 📤 Data Export & Print Statements
- **CSV Export**: Direct download for all transactions and EMI records with UTF-8 BOM for Microsoft Excel.
- **Printable Statement / PDF**: Clean printable monthly financial statement formatted for printing or browser "Save as PDF".

---

## 🛠️ Technology Stack & Dependencies

- **Backend**: PHP 8.2+ with PDO MySQL prepared statements
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Styling**: Bootstrap 5.3.3 & Custom Mobile-App CSS (`assets/css/style.css`)
- **Typography**: Google Fonts (*Plus Jakarta Sans*)
- **Icons**: Font Awesome 6.5.2 (No emojis used for UI icons)
- **Charts**: Chart.js 4.4.2
- **Calendar**: FullCalendar 6.1.11
- **Notifications**: Toastr.js & SweetAlert2 11

---

## 🚀 Installation & Setup (XAMPP on macOS / Windows / Linux)

1. **Verify Project Location**:
   Ensure the project files are located in your XAMPP web root:
   ```
   /Applications/XAMPP/xamppfiles/htdocs/projects/Expense-Tracker/
   ```

2. **Configure Database Settings (`.env`)**:
   Verify or edit the `.env` file in the root directory:
   ```ini
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=expense_tracker_db
   DB_USER=root
   DB_PASS=

   APP_NAME=SmartSpend
   APP_URL=http://localhost/projects/Expense-Tracker
   DEFAULT_CURRENCY=INR
   DEFAULT_CURRENCY_SYMBOL=₹
   DEFAULT_DATE_FORMAT="d M Y"
   ```

3. **Import Database Schema**:
   Run the following in Terminal or import `schema.sql` via **phpMyAdmin**:
   ```bash
   /Applications/XAMPP/xamppfiles/bin/mysql -u root < schema.sql
   ```

4. **Seed Realistic Demo Data (Optional but Recommended)**:
   Populate the database with pre-configured transactions, EMIs, budgets, and goals matching the mockup:
   ```bash
   /Applications/XAMPP/xamppfiles/bin/php api/seed_demo.php
   ```

5. **Run the Application**:
   Open your browser and navigate to:
   ```
   http://localhost/projects/Expense-Tracker/
   ```

---

## 🔑 Demo Account Credentials

- **Email**: `demo@example.com`
- **Password**: `demo123`
*(Or click the **"Quick Demo Login"** button on `login.php` to sign in instantly).*

---

## 📂 Folder Structure

```
Expense-Tracker/
├── .env                     # Environment credentials & app settings (secured)
├── .env.example             # Example environment template
├── .htaccess                # Directory protection & security rules
├── schema.sql               # Complete SQL table definitions & seed categories
├── README.md                # Project documentation
│
├── config/
│   ├── database.php         # PDO connection singleton & .env parser
│   └── app.php              # App constants, session initialization & security
│
├── includes/
│   ├── auth.php             # Authentication checks, login & session helpers
│   ├── csrf.php             # CSRF token generator & validator
│   ├── functions.php        # Helper utilities, currency/date formatters & alert checks
│   ├── header.php           # HTML header, meta tags, CDNs & greeting bar
│   ├── nav.php              # Bottom floating navigation bar & More menu
│   ├── modals.php           # Quick Add (+) modal (Expense, Income, Pay EMI) & receipt modal
│   └── footer.php           # Scripts & closing tags
│
├── api/
│   ├── auth.php             # Register, login, change password, profile update
│   ├── transactions.php     # CRUD & grouped day listings for transactions
│   ├── emis.php             # Loan creation, installment payments, schedule & balance updates
│   ├── budgets.php          # Budget limits, spent calculation & usage %
│   ├── recurring.php        # Subscriptions & auto-advance logic
│   ├── goals.php            # Target savings & contribution deposits/withdrawals
│   ├── categories.php       # Custom category creation & management
│   ├── calendar.php         # JSON events feed for FullCalendar
│   ├── notifications.php    # Notifications list & read state updates
│   ├── reports.php          # Donut & line chart analytics data
│   └── seed_demo.php        # Database seeder script
│
├── export/
│   ├── csv.php              # CSV exporter with UTF-8 BOM
│   └── print_report.php     # Formatted printable statement / PDF template
│
├── assets/
│   ├── css/
│   │   └── style.css        # Warm cream pastel palette, cards, and mobile-app styles
│   ├── js/
│   │   └── app.js           # AJAX form handlers, Toastr, SweetAlert2 & UI interactions
│   ├── img/
│   │   ├── default-avatar.svg
│   │   └── piggy-bank.svg
│   └── uploads/
│       ├── receipts/        # Uploaded receipt files
│       └── avatars/         # Uploaded profile avatars
│
├── index.php                # App router (redirects to dashboard or login)
├── login.php                # Login screen
├── register.php             # Registration screen
├── logout.php               # Logout controller
├── dashboard.php            # Main Dashboard (Mockup Screen 1)
├── transactions.php         # Transactions Screen (Mockup Screen 2)
├── reports.php              # Stats & Analytics Screen (Mockup Screen 3)
├── expenses.php             # Dedicated Expenses manager
├── income.php               # Dedicated Income manager
├── emis.php                 # Loans & Installment Manager
├── budgets.php              # Monthly & Category Budgets
├── goals.php                # Savings Goals tracker
├── recurring.php            # Recurring bills & Subscriptions
├── calendar.php             # Finance Calendar
├── categories.php           # Custom Categories
├── notifications.php        # Notifications Center
└── settings.php             # Profile & Preferences
```

---

## 🔒 Security Implementations

1. **Prepared SQL Statements**: 100% of queries use PDO prepared statements with parameter binding to prevent SQL injection.
2. **Password Security**: Passwords are encrypted using `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt).
3. **CSRF Protection**: Every non-idempotent form submission and AJAX request is protected with CSRF tokens.
4. **File Upload Restrictions**: File uploads for receipts and avatars strictly validate MIME type, file extensions, and file sizes (max 5MB).
5. **Credential Protection**: Database passwords and secrets are loaded from `.env` and shielded by `.htaccess` rules.

