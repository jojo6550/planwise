# PlanWise Quick Start Setup Guide

## Pre-Installation Requirements

- PHP 7.4 or higher (PHP 8+ recommended)
- MySQL 5.7 or higher
- Composer installed globally
- XAMPP or similar local server setup
- Code editor (VS Code, Sublime, etc.)

---

## STEP 1: Database Setup

### Option A: Using phpMyAdmin (XAMPP)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" to create a new database
3. Name: `planwise_db`
4. Charset: `utf8mb4`
5. Click "Create"
6. Navigate to the SQL tab
7. Copy and paste contents of `database/schema.sql`
8. Click "Go" to execute

### Option B: Using Command Line

```bash
# Login to MySQL
mysql -u root -p

# Create database and import schema
mysql -u root -p < database/schema.sql

# Verify
mysql -u root -p planwise_db -e "SHOW TABLES;"
```

### Option C: Programmatically

1. Ensure database is created:
```php
// Create a temporary script to run schema
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS planwise_db CHARACTER SET utf8mb4;"
mysql -u root -p planwise_db < database/schema.sql
```

---

## STEP 2: Environment Configuration

1. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

2. Edit `.env` with your local settings:
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=planwise_db
DB_USER=root
DB_PASS=
MAIL_FROM_EMAIL=noreply@planwise.local
MAIL_FROM_NAME=PlanWise
```

---

## STEP 3: Install Dependencies

```bash
# Install Composer dependencies
composer install

# Verify installation
composer list
```

Expected output should show installed packages:
- vlucas/phpdotenv
- tecnickcom/tcpdf
- chillerlan/php-qrcode
- phpoffice/phpword

---

## STEP 4: Directory Permissions

Ensure these directories are writable:

```bash
# On Linux/Mac
chmod 755 uploads/
chmod 755 exports/
chmod 755 logs/
chmod 755 public/qr/

# Create directories if they don't exist
mkdir -p uploads/lesson-plans
mkdir -p uploads/thumbnails
mkdir -p exports/pdf
mkdir -p exports/word
mkdir -p logs
mkdir -p public/qr
```

---

## STEP 5: Test Database Connection

Create a test file `test-connection.php` in the project root:

```php
<?php
require_once 'vendor/autoload.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Test query
    $result = $db->fetch("SELECT COUNT(*) as count FROM roles");
    
    echo "✅ Database connection successful!\n";
    echo "Roles in database: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
?>
```

Run it:
```bash
php test-connection.php
```

Expected output:
```
✅ Database connection successful!
Roles in database: 2
```

---

## STEP 6: Verify Database Structure

Check that all tables exist:

```bash
mysql -u root -p planwise_db -e "SHOW TABLES;"
```

Expected tables:
```
activity_logs
files
lesson_plans
lesson_sections
password_resets
qr_codes
roles
users
```

---

## STEP 7: Check Default Data

```bash
mysql -u root -p planwise_db -e "SELECT * FROM roles;"
```

Expected:
```
+---------+----------+
| role_id | role_name|
+---------+----------+
|    1    | Admin    |
|    2    | Teacher  |
+---------+----------+
```

---

## STEP 8: Test Core Classes

Create `test-classes.php`:

```php
<?php
require_once 'vendor/autoload.php';

// Test each class
echo "Testing core classes...\n\n";

// Test Database
require_once 'classes/Database.php';
echo "✅ Database class loaded\n";

// Test User
require_once 'classes/User.php';
echo "✅ User class loaded\n";

// Test Auth
require_once 'classes/Auth.php';
echo "✅ Auth class loaded\n";

// Test Role
require_once 'classes/Role.php';
$role = new Role();
$roles = $role->getAll();
echo "✅ Role class loaded - Found " . count($roles) . " roles\n";

// Test LessonPlan
require_once 'classes/LessonPlan.php';
echo "✅ LessonPlan class loaded\n";

// Test ActivityLog
require_once 'classes/ActivityLog.php';
echo "✅ ActivityLog class loaded\n";

// Test Validator
require_once 'classes/Validator.php';
echo "✅ Validator class loaded\n";

// Test PasswordReset
require_once 'classes/PasswordReset.php';
echo "✅ PasswordReset class loaded\n";

echo "\n✅ All core classes loaded successfully!\n";
?>
```

Run it:
```bash
php test-classes.php
```

---

## STEP 9: Start Local Server

### Using XAMPP

1. Start Apache and MySQL from XAMPP Control Panel
2. Navigate to: `http://localhost/planwise/public/`

### Using PHP Built-in Server

```bash
cd public/
php -S localhost:8000
```

Then open: `http://localhost:8000/`

### Using Docker (Optional)

```bash
docker-compose up -d
```

---

## STEP 10: Test Registration & Login

### Create Admin User (SQL)

```sql
INSERT INTO users (first_name, last_name, email, password_hash, role_id, status)
VALUES (
    'Admin', 
    'User', 
    'admin@example.com', 
    '$2y$10$slYQmyNdGzBy6OtIjSKsu.L6GdmVLzYx/qQkZW7T2mD3l9V8h2JCe', 
    1, 
    'active'
);
```

Password: `admin@123` (hashed)

### Create Teacher User (SQL)

```sql
INSERT INTO users (first_name, last_name, email, password_hash, role_id, status)
VALUES (
    'Teacher', 
    'User', 
    'teacher@example.com', 
    '$2y$10$slYQmyNdGzBy6OtIjSKsu.L6GdmVLzYx/qQkZW7T2mD3l9V8h2JCe', 
    2, 
    'active'
);
```

Password: `admin@123` (same for testing)

### Test Login

1. Go to: `http://localhost/planwise/public/index.php?page=login`
2. Enter:
   - Email: `admin@example.com`
   - Password: `admin@123`
3. Should redirect to admin dashboard

---

## VERIFICATION CHECKLIST

- [ ] Database created and all tables present
- [ ] .env file configured with correct database settings
- [ ] Composer dependencies installed
- [ ] Directory permissions set (755 or writable)
- [ ] Database connection test passes
- [ ] All core classes load without errors
- [ ] Default roles exist in database
- [ ] Admin and teacher users created
- [ ] Can log in successfully
- [ ] Session handling works (30-min timeout)
- [ ] CSRF token generation works
- [ ] Activity logging creates entries

---

## TROUBLESHOOTING

### Database Connection Error

```
Error: Could not connect to database
```

**Solution:**
1. Check DB_HOST, DB_USER, DB_PASS in .env
2. Verify MySQL is running
3. Check database exists: `mysql -u root -p -e "SHOW DATABASES;"`
4. Verify table exists: `mysql -u root -p planwise_db -e "SHOW TABLES;"`

### Class Not Found Error

```
Error: Class not found
```

**Solution:**
1. Run `composer install`
2. Check autoload in composer.json points to correct namespace
3. Verify class file exists and is readable

### Permission Denied Error

```
Error: Permission denied on uploads/
```

**Solution:**
1. Set directory permissions: `chmod 755 uploads/`
2. Verify ownership: `ls -la uploads/`
3. Create required subdirectories manually

### Blank Page

**Solution:**
1. Check error logs: `tail logs/database.log`
2. Check PHP error log: `php -r "echo ini_get('error_log');"`
3. Enable display_errors temporarily in a test script
4. Check browser console for JavaScript errors

---

## NEXT STEPS

Once setup is verified:

1. **Review Database Schema** - Understand relationships
2. **Test Authentication** - Login as admin and teacher
3. **Create Sample Lesson Plan** - Test CRUD operations
4. **Test File Uploads** - Upload test document
5. **Generate PDF** - Test export functionality
6. **Generate QR Code** - Test QR code generation
7. **Check Activity Logs** - Verify logging is working
8. **Review Views** - Complete missing UI components

---

## USEFUL COMMANDS

```bash
# View database logs
tail -f logs/database.log

# View activity logs
tail -f logs/activity.log

# Check PHP version
php --version

# List installed packages
composer list

# Update dependencies
composer update

# Clear composer cache
composer clearcache

# Validate composer.json
composer validate

# Run PHP built-in server on port 8000
php -S localhost:8000 -t public/

# MySQL commands
mysql -u root -p planwise_db
SHOW TABLES;
DESC users;
SELECT * FROM roles;
SELECT * FROM activity_logs LIMIT 10;
```

---

## REFERENCE LINKS

- **PHP Documentation:** https://www.php.net/manual/
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **Composer:** https://getcomposer.org/
- **TCPDF:** https://tcpdf.org/
- **QRCode Library:** https://github.com/chillerlan/php-qrcode
- **PHPWord:** https://phpword.readthedocs.io/

---

**Setup Time: ~15-20 minutes**  
**Difficulty: Beginner to Intermediate**  
**Last Updated:** 2026-02-28

