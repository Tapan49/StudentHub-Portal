# StudentHub Portal - Web Development Frameworks

[![GitHub Repository](https://img.shields.io/badge/GitHub-Repository-blue?logo=github)](https://github.com/Tapan49/StudentHub-Portal)
**Repository URL:** `https://github.com/Tapan49/StudentHub-Portal`

StudentHub is a comprehensive, production-grade full-stack university web portal designed for student lifecycle management, campus events coordination, and administrative oversight. The portal features secure role-based access control, relational database design, client-side dynamic rendering, server-side data processing, RESTful JSON APIs, and asynchronous single-page interactions.

---

## 🛠️ Technology Stack & Architecture

* **Frontend:**
  * Semantic HTML5 with accessibility-ready structures.
  * Custom CSS3 utilizing CSS Grid and Flexbox for responsive cross-device layouts.
  * Modern JavaScript (ES6+) for DOM manipulation, client-side regex validations, asynchronous Fetch API, and search debouncing.
* **Backend:**
  * PHP (8.x) architected with secure PHP Data Objects (PDO).
  * True Prepared Statements for complete SQL injection protection.
  * Native session management, session fixation prevention (`session_regenerate_id`), and inactivity timeouts.
  * Cryptographic password hashing (`password_hash` with Bcrypt) and CSRF token defenses.
* **Database:**
  * MySQL 8.x normalized relational database with foreign key constraints, cascading rules, and stored procedures.
* **Security Measures:**
  * Strict Role-Based Access Control (RBAC) separating Administrator and Student capabilities.
  * Multi-layer file upload validation (true MIME-type inspection via `finfo`, extension whitelisting, 2MB size limit).
  * Randomized upload renaming and `.htaccess` execution prevention in upload directories.
  * System-wide security audit logging for administrative traceability.

---

## 📂 Project Directory Structure

```text
WDF/
├── css/
│   └── style.css                     # Responsive stylesheets, Flexbox/Grid layouts, theme styling
├── data/
│   ├── contacts.json                 # JSON flat-file storage for contact inquiries
│   └── events.json                   # External event datasets for Fetch API consumption
├── database/
│   └── studenthub_db.sql             # Complete MySQL relational schema, tables, procedures & seed data
├── html/
│   ├── home.html                     # Public portal landing page with banner and navigation
│   ├── events.html                   # Dynamic events catalog with client-side filters & pagination
│   ├── events-app.js                 # Frontend Fetch API & client-side array manipulation script
│   ├── login.html                    # Static login skeleton
│   ├── signup.html                   # Student sign-up form with real-time regex feedback
│   ├── signup-validation.js          # Client-side form validation and password strength meter
│   ├── courses.html                  # Academic course catalog
│   ├── timetable.html                # Academic timetable display
│   ├── profile.html                  # Student profile display
│   ├── faq.html                      # Interactive collapsible FAQ
│   ├── feedback.html                 # Feedback submission form
│   └── about.html                    # University information page
├── image/                            # University assets, event banners, and gallery photographs
├── php/
│   ├── api/
│   │   └── events_api.php            # RESTful JSON API handling GET, POST, PUT, DELETE with status codes
│   ├── auth.php                      # Core authentication, RBAC enforcement, session timeouts, remember-me
│   ├── db.php                        # PDO connection setup, auto-migrations, and audit log helper
│   ├── login.php                     # Secure login interface with CSRF check, password_verify & RBAC routing
│   ├── logout.php                    # Secure session destruction and cookie invalidation
│   ├── student_dashboard.php         # Protected dashboard for student accounts
│   ├── admin_dashboard.php           # Central admin command panel with KPIs, user toggle & analytics chart
│   ├── student_manage.php            # Full Student CRUD with search, filters, soft-delete & restore
│   ├── export_students_csv.php       # Dynamic CSV export streaming script
│   ├── event_manage.php              # Full Event CRUD with validated poster image upload
│   ├── events_live.php               # Single-Page Live Event Manager with 300ms search debounce
│   ├── contact.php                   # Contact inquiry form with CSRF token
│   ├── process_contact.php           # Server-side validation and file-based data storage
│   ├── view_contacts.php             # Contact submission reader and viewer
│   ├── view_registered_students.php  # Database verification viewer showing bcrypt hashes
│   ├── test_db.php                   # Database connectivity and prepared statement join test
│   └── test_integration.php          # Automated end-to-end integration and health verification dashboard
├── uploads/
│   └── posters/                      # Secure directory for uploaded event poster graphics
│       └── .htaccess                 # Direct script execution protection rule
├── .gitignore                        # Git exclusion rules
└── README.md                         # Project documentation and deployment guide
```

---

## 🚀 Installation & Local Deployment Guide

### Prerequisites
* **Local Web Server:** MAMP (macOS) or XAMPP (Windows/macOS/Linux) with **PHP 8.x** and **MySQL 5.7+ / 8.x**.
* **Web Browser:** Modern web browser (Chrome, Safari, Edge, Firefox).

### Setup Steps
1. **Clone or Copy the Project:**
   Place the project directory into your web server's root:
   * **MAMP:** Set Document Root in MAMP Preferences to this folder, or place under `/Applications/MAMP/htdocs/WDF`.
   * **XAMPP:** Place under `C:\xampp\htdocs\WDF` or `/opt/lampp/htdocs/WDF`.

2. **Start Web & Database Servers:**
   Launch the MAMP / XAMPP control panel and start both **Apache** and **MySQL**.

3. **Import Database:**
   * Open **phpMyAdmin** (`http://localhost:8888/phpmyadmin` or `http://localhost/phpmyadmin`).
   * Create a database named `studenthub_db` (or simply go to the **Import** tab).
   * Choose [`database/studenthub_db.sql`](database/studenthub_db.sql) and click **Import/Go**.
   * *(Note: The portal also contains automated schema migration in `php/db.php` that will auto-provision any missing tables or columns on first run).*

4. **Database Configuration (`php/db.php`):**
   Verify the database credentials in [`php/db.php`](php/db.php):
   * **Host:** `127.0.0.1`
   * **Port:** `8889` (default for MAMP) or `3306` (default for XAMPP)
   * **User:** `root`
   * **Password:** `root` (MAMP default) or `""` (empty for XAMPP default)

5. **Launch Portal in Browser:**
   * **Public Landing Page:** `http://localhost:8888/html/home.html`
   * **Portal Login:** `http://localhost:8888/php/login.php`
   * **System Health & Integration Test:** `http://localhost:8888/php/test_integration.php`

---

## 🔑 Default Evaluation Credentials

| Role | Email Address | Password | Privileges |
|---|---|---|---|
| **Administrator** | `admin@studenthub.com` | `Admin@123` | Full access to Admin Panel, Student CRUD, Event CRUD, Live AJAX Manager, User Status Toggle, and Audit Logs |
| **Student** | `john@example.com` | `Student@123` | Access to Student Dashboard, Events Directory, Profile, and Support |

---

## 🌟 Key Functional Modules & Features

### 1. Authentication, Sessions & RBAC
* Secure user authentication utilizing PHP's `password_verify()`.
* Session fixation protection via `session_regenerate_id(true)`.
* Automatic 15-minute inactivity session timeout.
* Cryptographically secure Remember-Me persistent cookie using SHA-256 validator hash in the database.
* Role-Based Access Control automatically routing administrators to `admin_dashboard.php` and students to `student_dashboard.php`, with strict interception of unauthorized access attempts.

### 2. Student Management Module
* Complete CRUD (Create, Read, Update, Delete) backed by prepared statements.
* Keyword search querying student name, email, and enrollment number.
* Multi-criteria filtering by academic course (`B.Tech`, `MCA`, `BCA`, `M.Tech`) and status.
* JavaScript confirmation prompt before delete operations.
* Soft-delete functionality marking records as `Deleted` without data loss, alongside full restore and purge capabilities.
* One-click dynamic CSV export streaming.

### 3. Event Management Module
* Multi-field event administration (title, date/time, category, format, location, description, status).
* Multi-layer secure file upload for event posters:
  * Strict MIME verification (`image/jpeg`, `image/png`, `image/webp`, `image/gif`).
  * File size limitation enforced at 2 MB.
  * Filename randomization (`poster_<timestamp>_<random_hex>.<ext>`) to prevent script execution attacks.
  * Upload directory `.htaccess` protection blocking direct script execution.

### 4. RESTful JSON API & Live AJAX Single-Page Application
* Backend JSON API (`php/api/events_api.php`) supporting `GET`, `POST`, `PUT`, and `DELETE` with standard HTTP status codes (`200`, `201`, `400`, `404`, `405`, `500`).
* Live Event Manager (`php/events_live.php`) providing an interactive single-page interface with zero page reloads.
* Asynchronous live search powered by a **300ms debounce timer** for instantaneous server querying as the user types.
* In-place modal creation, updating, and animated card removal upon deletion.

### 5. Administrative Dashboard, Analytics & Audit Logging
* Dynamic KPI stat cards calculated via SQL aggregations (Total Users, Active Students, Campus Events, Total Registrations).
* Interactive category distribution analytics chart powered by Chart.js.
* Recent registrations live activity feed linking students and events via SQL JOINs.
* User account status control allowing administrators to activate or deactivate accounts with immediate login rejection for disabled users.
* Full security audit trail logging every administrative action (`CREATE`, `UPDATE`, `DELETE`, `STATUS_TOGGLE`, `LOGIN`) with timestamps and operator details.

---

## 🧪 Verification & End-to-End Test Matrix

| # | Test Scenario | Expected Result | Status |
|---|---|---|---|
| **1** | Database Connectivity & PDO Prepared Statements | Successful PDO handshake with UTF8mb4 charset and prepared statement execution | Passed |
| **2** | Student Registration & Password Hashing | Plaintext password converted to Bcrypt hash (`$2y$10$...`); duplicate email rejected | Passed |
| **3** | Authentication & RBAC Redirection | Admin directed to admin dashboard; Student directed to student dashboard | Passed |
| **4** | Unauthorized Dashboard Access Attempt | Student or unauthenticated user blocked with access denied notification | Passed |
| **5** | Session Inactivity Timeout | Session invalidated after inactivity with prompt to log in again | Passed |
| **6** | Student CRUD & Soft-Delete | Full record addition, update, soft-delete, and restore verified | Passed |
| **7** | Dynamic CSV Export | Streams structured `.csv` file with matching filter parameters | Passed |
| **8** | File Upload Validation | Rejects non-image files and files > 2MB with explicit validation messages | Passed |
| **9** | RESTful JSON API & Live AJAX CRUD | Asynchronous operations execute with HTTP status codes and zero page reloads | Passed |
| **10** | Live Search with 300ms Debounce | Queries server after typing pause without manual submission | Passed |
| **11** | User Status Toggle & Deactivation | Deactivated user barred from logging in; action recorded in audit log | Passed |
| **12** | Security Audit Trail | Complete traceability of administrative actions with operator name and timestamp | Passed |
