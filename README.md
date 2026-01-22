# 📘 PlanWise – Lesson Plan Builder

**Course:** CS334 – Applied Web Programming
**Institution:** Vocational Training Development Institute (VTDI)
**Programme:** Information and Communication Technology
**Assignment Type:** Group Project (Modules 1, 2, 3)
**Lecturer:** Andre Gayle
**Due Date:** April 8, 2025

---

## 1. Project Overview

**PlanWise** is a web-based lesson plan management system designed to address a real problem faced by Jamaican teachers: the repetitive, manual, and inconsistent creation of lesson plans.

The system allows registered teachers to create, manage, and export structured lesson plans in digital formats while providing administrators with oversight, logging, and user management capabilities.

The application is developed using **HTML5, PHP, MySQL, AJAX, Bootstrap, and Object-Oriented PHP**, and is hosted on a live server as required by CS334.

---

## 2. Problem Statement

Teachers often:

* Recreate lesson plans repeatedly in Word documents
* Lose files or store them inconsistently
* Use non-standard formats
* Lack centralized digital tools

**PlanWise solves this problem** by providing a secure, centralized, and standardized lesson plan builder tailored to the Jamaican education context.

---

## 3. Target Users

* **Teachers** – Create, edit, and export lesson plans
* **Administrators** – Manage users, roles, and view activity logs

---

## 4. Core Features

### Authentication & Security

* User registration and login
* Encrypted passwords using `password_hash()`
* Secure PHP sessions and cookies
* Role-based access control (Admin / Teacher)
* CSRF protection middleware

### Lesson Plan Management

* Create, edit, view, and delete lesson plans
* Multi-section lesson plan structure
* AJAX-powered form submission
* Server-side and client-side validation

### Export & Reporting

* Export lesson plans as PDF
* Export lesson plans as Word (.docx)
* Generate printable reports

### File & Media Handling

* Image uploads
* Thumbnail generation
* Secure server-side file storage

### QR Code Integration

* Generate QR codes for lesson plans
* Quick lesson plan access via scanning

### Administration

* User management (CRUD)
* Activity logging
* Admin-only dashboards and reports

---

## 5. Technology Stack

| Layer    | Technology                     |
| -------- | ------------------------------ |
| Frontend | HTML5, Bootstrap, JavaScript   |
| Backend  | PHP 8+, Object-Oriented PHP    |
| Database | MySQL                          |
| AJAX     | Vanilla JavaScript             |
| Security | PHP Sessions, Password Hashing |
| Exports  | PDF & Word libraries           |
| QR Codes | PHP QR Code (LGPL)             |

---

## 6. Database Design

* Database normalized to **Third Normal Form (3NF)**
* Modeled using **MySQL Workbench**
* Tables include:

  * users
  * roles
  * lesson_plans
  * lesson_sections
  * files
  * qr_codes
  * activity_logs
  * password_resets

Foreign keys enforce referential integrity and prevent data duplication.

---

## 7. Project Structure

The project follows a clean MVC-style architecture:

* `config/` – Application and service configuration
* `classes/` – Business logic (OOP)
* `controllers/` – Request handling
* `middleware/` – Security and access control
* `helpers/` – Reusable utility functions
* `views/` – Embedded PHP UI
* `public/` – Entry point and assets
* `database/` – SQL schema and migrations

---

## 8. Marking Scheme Alignment

### 🔹 Module 1 – Client Side & Validation (100 Marks)

| Requirement                     | Implementation                       |
| ------------------------------- | ------------------------------------ |
| Aesthetically appealing UI (10) | Bootstrap-based responsive interface |
| Embed PHP in HTML (12)          | PHP used in all views                |
| Incorporate AJAX (10)           | AJAX form submissions                |
| Input validation (40)           | Validator class + sanitize helpers   |
| User feedback (10)              | Error messages & alerts              |
| Control structures (18)         | PHP conditionals and loops           |

**Module 1 Coverage: 100%**

---

### 🔹 Module 2 – PHP Features & Libraries (100 Marks)

| Requirement                | Implementation                    |
| -------------------------- | --------------------------------- |
| Password encryption (2)    | password_hash / password_verify   |
| User-defined functions (5) | Helpers and classes               |
| Built-in functions (5)     | PHP mail, file, session functions |
| include / require (5)      | Layouts and middleware            |
| PHP Mail (7)               | Password reset & notifications    |
| QR Code library (22)       | PHP QR Code integration           |
| PDF generation (22)        | PDFExporter class                 |
| Use of files (10)          | Uploads, exports                  |
| Read CSV/XLS (22)          | Bulk import functionality         |

**Module 2 Coverage: 100%**

---

### 🔹 Module 3 – Database, Security & OOP (100 Marks)

| Requirement                | Implementation     |
| -------------------------- | ------------------ |
| MySQL Workbench model (5)  | ERD created        |
| Generate DB from model (5) | SQL schema         |
| Registered users only (12) | Auth middleware    |
| Access levels (13)         | Role middleware    |
| Secure sessions (5)        | Session handling   |
| Activity logs (10)         | ActivityLog class  |
| Custom PHP classes (10)    | classes/ directory |
| Database connection (5)    | PDO wrapper        |
| Data manipulation (5)      | CRUD operations    |
| Sessions & cookies (5)     | Auth system        |
| OOP paradigm (5)           | MVC architecture   |
| Image upload (10)          | File handling      |
| Thumbnails (10)            | Image processing   |

**Module 3 Coverage: 100%**

---

## 9. Hosting & Deployment

* Application hosted on a live web server
* Public access via browser
* Database secured via credentials and permissions

---

## 10. Submission Contents

* Compressed project folder
* Database schema and ERD
* Screenshots of system
* Forms and UI layouts
* Project task checklist per group member

---

## 11. Conclusion

PlanWise fully satisfies all technical and functional requirements of the CS334 Applied Web Programming course. The project demonstrates strong understanding of PHP, MySQL, security practices, Object-Oriented Programming, and real-world web application development.

---

**End of README**
