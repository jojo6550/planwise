# MySQL Workbench EER Diagram Creation Guide

**Project**: PlanWise - Lesson Plan Builder  
**Database**: planwise_db  
**Date**: February 1, 2026

---

## ⚠️ CRITICAL REQUIREMENT

The CS334 assignment **explicitly requires** a MySQL Workbench graphical model file (.mwb).

**Current Status**: ❌ File does not exist in repository

**Action Required**: Create EER diagram in MySQL Workbench and save as `.mwb` file

---

## Step-by-Step Instructions to Create .mwb File

### Step 1: Install MySQL Workbench
1. Download from: https://dev.mysql.com/downloads/workbench/
2. Install for your operating system
3. Launch MySQL Workbench

### Step 2: Create New EER Model
1. Click **File** → **New Model** or press `Ctrl+N`
2. In the model overview, double-click **Add Diagram**
3. You'll see a blank canvas for designing

### Step 3: Add Tables

#### Table 1: roles
1. Click the **Table** icon in the left toolbar
2. Click on canvas to place table
3. Double-click table to edit
4. Set table name: `roles`
5. Add columns:
   - `role_id` - INT, PK, NN, AI
   - `role_name` - VARCHAR(50), NN, UQ

#### Table 2: users
1. Add new table: `users`
2. Add columns:
   - `user_id` - INT, PK, NN, AI
   - `first_name` - VARCHAR(100), NN
   - `last_name` - VARCHAR(100), NN
   - `email` - VARCHAR(150), NN, UQ
   - `password_hash` - VARCHAR(255), NN
   - `role_id` - INT, NN
   - `status` - ENUM('active','inactive'), Default: 'active'
   - `created_at` - TIMESTAMP, NN, Default: CURRENT_TIMESTAMP

#### Table 3: lesson_plans
1. Add new table: `lesson_plans`
2. Add columns:
   - `lesson_id` - INT, PK, NN, AI
   - `user_id` - INT, NN
   - `title` - VARCHAR(255), NN
   - `subject` - VARCHAR(100), NN
   - `grade_level` - VARCHAR(50)
   - `duration` - INT
   - `objectives` - TEXT
   - `materials` - TEXT
   - `procedures` - TEXT
   - `assessment` - TEXT
   - `notes` - TEXT
   - `status` - ENUM('draft','published','archived'), Default: 'draft'
   - `grade` - VARCHAR(50), NN
   - `theme` - VARCHAR(150)
   - `attainment_target` - TEXT
   - `created_at` - TIMESTAMP, NN, Default: CURRENT_TIMESTAMP
   - `updated_at` - TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP

#### Table 4: lesson_sections
1. Add new table: `lesson_sections`
2. Add columns:
   - `section_id` - INT, PK, NN, AI
   - `lesson_id` - INT, NN
   - `section_type` - VARCHAR(50), NN
   - `content` - TEXT, NN

#### Table 5: files
1. Add new table: `files`
2. Add columns:
   - `file_id` - INT, PK, NN, AI
   - `lesson_id` - INT, NN
   - `file_type` - VARCHAR(50), NN
   - `file_path` - VARCHAR(255), NN
   - `uploaded_at` - TIMESTAMP, NN, Default: CURRENT_TIMESTAMP

#### Table 6: qr_codes
1. Add new table: `qr_codes`
2. Add columns:
   - `qr_id` - INT, PK, NN, AI
   - `lesson_id` - INT, NN, UQ
   - `qr_path` - VARCHAR(255), NN
   - `generated_at` - TIMESTAMP, NN, Default: CURRENT_TIMESTAMP

#### Table 7: activity_logs
1. Add new table: `activity_logs`
2. Add columns:
   - `log_id` - INT, PK, NN, AI
   - `user_id` - INT, NN
   - `action` - VARCHAR(255), NN
   - `description` - TEXT
   - `ip_address` - VARCHAR(45)
   - `user_agent` - VARCHAR(255)
   - `created_at` - TIMESTAMP, NN, Default: CURRENT_TIMESTAMP

#### Table 8: password_resets
1. Add new table: `password_resets`
2. Add columns:
   - `reset_id` - INT, PK, NN, AI
   - `user_id` - INT, NN
   - `token` - VARCHAR(255), NN
   - `expires_at` - DATETIME, NN

### Step 4: Create Relationships (Foreign Keys)

Click the **1:n Non-Identifying Relationship** tool and create connections:

1. **roles → users**
   - From: `roles.role_id`
   - To: `users.role_id`
   - Cardinality: One-to-Many (1:n)
   - On Delete: RESTRICT or CASCADE
   - On Update: CASCADE

2. **users → lesson_plans**
   - From: `users.user_id`
   - To: `lesson_plans.user_id`
   - Cardinality: One-to-Many (1:n)
   - On Delete: CASCADE
   - On Update: CASCADE

3. **users → activity_logs**
   - From: `users.user_id`
   - To: `activity_logs.user_id`
   - Cardinality: One-to-Many (1:n)
   - On Delete: CASCADE
   - On Update: CASCADE

4. **users → password_resets**
   - From: `users.user_id`
   - To: `password_resets.user_id`
   - Cardinality: One-to-Many (1:n)
   - On Delete: CASCADE
   - On Update: CASCADE

5. **lesson_plans → lesson_sections**
   - From: `lesson_plans.lesson_id`
   - To: `lesson_sections.lesson_id`
   - Cardinality: One-to-Many (1:n)
   - On Delete: CASCADE
   - On Update: CASCADE

6. **lesson_plans → files**
   - From: `lesson_plans.lesson_id`
   - To: `files.lesson_id`
   - Cardinality: One-to-Many (1:n)
   - On Delete: CASCADE
   - On Update: CASCADE

7. **lesson_plans → qr_codes**
   - From: `lesson_plans.lesson_id`
   - To: `qr_codes.lesson_id`
   - Cardinality: One-to-One (1:1) - Use identifying relationship
   - On Delete: CASCADE
   - On Update: CASCADE

### Step 5: Add Indexes

For each table, add indexes on foreign keys and frequently queried columns:

**users table:**
- Index on `role_id` (idx_user_role)
- Unique index on `email` (automatically created)

**lesson_plans table:**
- Index on `user_id` (idx_lesson_user)
- Index on `status` (idx_lesson_status)

**activity_logs table:**
- Index on `user_id` (idx_log_user)
- Index on `action` (idx_log_action)
- Index on `created_at` (idx_log_date)

### Step 6: Arrange Layout

1. Auto-arrange tables: **Model** → **Auto-Arrange**
2. Manually adjust for clarity
3. Suggested layout:

```
┌─────────┐
│  roles  │
└────┬────┘
     │
     ▼
┌─────────────┐
│    users    │◄────────────┐
└────┬────────┘             │
     │                      │
     ├──────────────────┐   │
     │                  │   │
     ▼                  ▼   │
┌──────────┐      ┌─────────────┐
│ activity │      │  password   │
│   logs   │      │   resets    │
└──────────┘      └─────────────┘
     
                  
     ▼                  
┌──────────────┐
│ lesson_plans │
└──────┬───────┘
       │
       ├─────────────┐
       │             │
       ▼             ▼
┌─────────────┐  ┌─────────┐
│   lesson    │  │  files  │
│  sections   │  └─────────┘
└─────────────┘
       │
       ▼
┌─────────────┐
│  qr_codes   │
└─────────────┘
```

### Step 7: Add Model Notes

1. Click **Note** icon in toolbar
2. Add a note with project information:

```
PlanWise - Lesson Plan Builder
Database: planwise_db
CS334 Applied Web Programming
VTDI - 2026

Description:
This database supports a lesson plan management system with:
- User authentication and role-based access
- Lesson plan CRUD operations
- File attachments and QR code generation
- Comprehensive activity logging
- Password reset functionality

Relationships:
- One-to-Many: User → Lesson Plans
- One-to-Many: Lesson Plan → Sections/Files
- One-to-One: Lesson Plan → QR Code
```

### Step 8: Set MySQL Options

1. Click **Model** in top menu
2. Select **Model Options**
3. Set:
   - Target MySQL Version: 8.0 (or your server version)
   - Default Storage Engine: InnoDB
   - Default Charset: utf8mb4
   - Default Collation: utf8mb4_general_ci

### Step 9: Forward Engineer (Generate SQL)

1. Click **Database** → **Forward Engineer**
2. Connect to your MySQL server (or skip this step)
3. Review generated SQL
4. This confirms your model is valid

### Step 10: Save the Model

1. Click **File** → **Save Model**
2. Save as: `planwise_db.mwb`
3. Location: `database/planwise_db.mwb`
4. This creates the required .mwb file

### Step 11: Export Diagram as Image (Bonus)

1. Click **File** → **Export** → **Export as PNG**
2. Save as: `database/planwise_eer_diagram.png`
3. Include in documentation

---

## Verification Checklist

Before submission, verify:

- [ ] All 8 tables created
- [ ] All columns have correct data types
- [ ] Primary keys defined (PK, NN, AI)
- [ ] Foreign keys created with relationships
- [ ] Unique constraints on email and lesson_id (qr_codes)
- [ ] Default values set (timestamps, status enums)
- [ ] Indexes added on foreign keys
- [ ] ON DELETE and ON UPDATE rules set
- [ ] Model saved as `.mwb` file
- [ ] SQL can be forward engineered without errors
- [ ] Diagram exported as PNG for documentation

---

## Common Mistakes to Avoid

❌ **Don't** forget to set foreign key constraints  
❌ **Don't** use identifying relationships for all FK (only for qr_codes)  
❌ **Don't** forget AUTO_INCREMENT on primary keys  
❌ **Don't** miss UNIQUE constraint on users.email  
❌ **Don't** forget DEFAULT values for timestamps and enums  

✅ **Do** use descriptive index names  
✅ **Do** set appropriate ON DELETE/UPDATE rules  
✅ **Do** use consistent naming conventions  
✅ **Do** add model notes for documentation  
✅ **Do** verify generated SQL matches schema  

---

## Alternative: Reverse Engineer from Existing Database

If database already exists:

1. **Database** → **Reverse Engineer**
2. Connect to your MySQL server
3. Select `planwise_db` database
4. Select all tables
5. Click **Execute**
6. MySQL Workbench will generate EER diagram automatically
7. Clean up layout and add notes
8. Save as `planwise_db.mwb`

This is the **fastest method** if your database is already set up!

---

## Submission

Include in your CS334 project submission:
1. `planwise_db.mwb` (Required .mwb file)
2. `planwise_eer_diagram.png` (Visual documentation)
3. `planwise_db.sql` (SQL schema)
4. `database/documentation.md` (Text documentation)

---

**IMPORTANT**: The .mwb file is **explicitly required** by the CS334 assignment rubric. Missing this file will result in point deduction (5 marks for Module 3).
