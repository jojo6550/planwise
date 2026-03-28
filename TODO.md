# CS334 - Applied Web Programming Project Audit & Enhancement Prompt

You are an expert full-stack PHP/MySQL web developer and lecturer who has taught this exact module at VTDI. Your task is to thoroughly audit, fix, improve, and complete a student's CS334 project so that it meets **100% of the requirements** from the assignment specification and scores maximum marks in all modules.

## Project Documents Provided

I have attached the following official documents:

1. **CS334 - Applied Web Programming PROJECT.pdf** (Assignment Specification)
2. **CS334 Course Outline - Applied Web Programming.pdf**
3. **php_mysql_book2.pdf** (A2 Project Starter Guide - good reference for basics)

Please read and internalize all requirements from these documents.

## Core Requirements Summary (You must enforce ALL of these)

### Client Side (Module 1)
- Aesthetically appealing, modern, responsive User Interface (HTML5 + CSS)
- Embed PHP in HTML
- Incorporate AJAX (at least for search or dynamic content loading)

### Server Side - Validation (Module 1)
- Strong server-side validation (prevent unclean/insecure data)
- Clear, user-friendly feedback for incorrect inputs

### Database (Module 3)
- Use MySQL Workbench to graphically model the database (include the diagram in documentation)
- Generate database from the graphical model

### Security (Module 3)
- Only registered users can use the system (proper login/registration)
- Different access levels / roles (e.g., Admin, User, maybe Manager/Staff)
- Use secure sessions with proper regeneration and timeout
- Encrypt passwords (use `password_hash()` + `password_verify()` — **never** plain text or md5)
- Log all actions (activity logs) — only administrators can view the logs

### General Requirements (Spread across modules)
- Control structures (if/else, switch, loops)
- User-defined functions + built-in functions
- `include()` and `require()`
- PHP Mail functions (e.g., for registration confirmation, password reset, or notifications)
- Custom PHP Classes (OOP paradigm)
- Connect to and manipulate MySQL database
- PHP Sessions and Cookies
- Implement **PHP QR Code** library (LGPL open source)
- Upload images
- Generate and display **thumbnails**
- Generate **PDF Reports** (use a library like TCPDF, mPDF, or Dompdf)
- Use of Files (read/write)
- Read information from **.xls or .csv files**

**Additional Mandatory:**
- Application **must be hosted on a live server** (recommendations welcome)
- Submit zipped project + documentation containing:
  - Database Model (ERD)
  - Screenshots
  - Forms and UI Layouts
  - Project Task Checklist per group member

## Marking Scheme Focus Areas (Maximize marks)

**Module 1 (100 marks):**
- Appealing UI (10)
- Embed PHP in HTML (12)
- AJAX (10)
- Input validation (40) ← **Very heavy**
- Feedback on errors (10)
- Control structures (18)

**Module 2 (100 marks):**
- Password encryption (2)
- User-defined & built-in functions (10)
- include/require (5)
- PHP Mail (7)
- **PHP QR Code library** (22) ← High weight
- **Generate PDF Reports** (22) ← High weight
- Use of Files (10)
- Read .xls/.csv (22) ← High weight

**Module 3 (100 marks):**
- Database modeling & generation (10)
- Registration + different access levels (25)
- Secure sessions (5)
- Activity logs (10)
- Custom PHP Classes + OOP (15)
- Database connection & manipulation (10)
- Sessions & Cookies (5)
- Upload images + thumbnails (20)

## Your Task

I will now provide you with my **current project files** (folder structure, code, database model, etc.).

Please do the following in order:

1. **Full Audit**: Analyze my current project against every single requirement above. Point out what is missing, incomplete, insecure, or low-quality.

2. **Fix & Secure**: Fix all security issues (especially password handling, SQL injection, session security, file upload vulnerabilities).

3. **Complete Missing Features**:
   - Implement proper role-based access control (RBAC)
   - Add comprehensive activity logging
   - Integrate PHP QR Code library with meaningful usage
   - Implement image upload + thumbnail generation
   - Create at least one PDF report generator
   - Add functionality to read/process .csv or .xls file
   - Add AJAX functionality (e.g., live search, dynamic form loading)
   - Send email using PHP mail() or PHPMailer
   - Improve UI to be aesthetically appealing and responsive

4. **Improve Architecture**:
   - Use proper OOP with custom classes (User, Database, Logger, PDFGenerator, etc.)
   - Clean folder structure (controllers, models, views, config, includes, assets, etc.)
   - Use prepared statements everywhere
   - Proper error handling and user feedback


5. **Final Output**:
   - Provide a complete, well-organized list of changes/files to create or modify.
   - Give corrected or new code snippets for critical parts.
   - Suggest a professional project name and theme (solve a real organizational problem).

