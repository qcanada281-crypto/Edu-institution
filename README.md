# EduInstitution - School Management & Student Portal

A modern, secure, and performant web platform for educational institution management, student grades tracking, attendance monitoring, and administration.

---

## 🌟 Key Features

- **Multi-Role Administration & RBAC:**
  - **Director:** Full administrative oversight, analytics, student & staff management.
  - **Secretary:** Enrollment, registrations, student records management.
  - **Teachers:** Section grades entry, homework assignments, attendance tracking.
  - **Students & Parents:** Public transcript lookup with Massar code, notifications, and course timetables.
- **Data Security & Privacy:**
  - **Tamper-Evident Audit Trail:** Detailed audit logging (`grade_audit_logs`) tracking any grade insert, update, or deletion with user attribution and IP address.
  - **Brute-Force & Scraping Protection:** Built-in IP rate limiter safeguarding public lookup endpoints and authentication screens.
  - **Hardened Sessions:** Strict session isolation with `HttpOnly`, `SameSite=Lax`, strict cookies, and automated session ID regeneration to block fixation and CSRF.
  - **OWASP Top 10 Compliant:** Prepared PDO statements, SQLi prevention, and strict XSS defense.
- **High Performance:**
  - Optimized composite B-Tree database indexing for instant grade and attendance queries.
  - Decoupled runtime DDL execution for sub-50ms API responses.

---

## 🛠️ Technology Stack

- **Backend:** PHP 8+ (Strict types, PDO MySQL)
- **Frontend:** Vanilla HTML5, Modern CSS3 (Responsive Design), Vanilla JavaScript (ES6+)
- **Database:** MySQL / MariaDB (InnoDB Engine with utf8mb4)
- **Local Server:** XAMPP / Apache

---

## 🚀 Getting Started

### 1. Prerequisites
- [XAMPP](https://www.apachefriends.org/) with PHP 8+ and MySQL/MariaDB.

### 2. Installation
1. Clone this repository into your XAMPP `htdocs` directory:
   ```bash
   git clone https://github.com/YOUR_USERNAME/edu-institution.git
   ```
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `backend/config.php` and verify your database connection credentials (default port: `3306` or `3308`).
4. Run the automated database security and performance migration by visiting:
   ```
   http://localhost/edu-institution/backend/db_security_migration.php
   ```
5. Open your browser and navigate to:
   ```
   http://localhost/edu-institution/
   ```

---

## 📂 Project Structure

```
edu-institution/
├── backend/
│   ├── config.php                  # Database, session security & helper functions
│   ├── rbac_middleware.php         # Role-Based Access Control logic
│   ├── admin_records.php           # Audited grades and attendance management
│   ├── admin_students.php          # Student enrollment & registration logic
│   ├── transcript_lookup.php       # Rate-limited public student transcript API
│   ├── db_security_migration.php   # Database indexes and audit table migration
│   └── ...                         # Other API endpoints
├── css/                            # Custom stylesheets
├── js/                             # Interactive scripts
├── images/                         # Static media assets
├── uploads/                        # Document and media uploads
├── index.html                      # Public home page
├── admin.html                      # Institutional admin portal
├── teacher.html                    # Teacher management dashboard
├── .gitignore                      # Git exclusion rules
└── README.md                       # Documentation
```

---

## 🔒 Security & Contribution

- All database queries are executed using strictly prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`).
- Sensitive grade operations require role elevation and are automatically recorded in the audit trail.
