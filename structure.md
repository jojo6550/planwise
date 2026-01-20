# PlanWise - Folder and File Structure

## Root Directory Structure

```
planwise/
│
├── config/                          # Configuration files
│   ├── database.php                 # Database connection settings (PDO)
│   ├── app.php                      # Application-wide constants and settings
│   └── mail.php                     # Email configuration (for password resets)
│
├── classes/                         # Object-Oriented PHP classes
│   ├── Database.php                 # PDO database wrapper class
│   ├── Auth.php                     # Authentication handler
│   ├── User.php                     # User model
│   ├── Role.php                     # Role model
│   ├── LessonPlan.php              # Lesson plan model
│   ├── LessonSection.php           # Lesson section model
│   ├── File.php                     # File upload handler
│   ├── QRCode.php                   # QR code generator
│   ├── ActivityLog.php              # Activity logging class
│   ├── PasswordReset.php            # Password reset handler
│   ├── PDFExporter.php              # PDF export functionality
│   ├── WordExporter.php             # Word document export functionality
│   └── Validator.php                # Form validation class
│
├── controllers/                     # Request handlers (AJAX & form submissions)
│   ├── AuthController.php           # Login, logout, registration handlers
│   ├── LessonPlanController.php     # CRUD operations for lesson plans
│   ├── UserController.php           # User management (admin only)
│   ├── FileController.php           # File upload/download handlers
│   ├── ExportController.php         # PDF and Word export handlers
│   ├── QRCodeController.php         # QR code generation handlers
│   └── ActivityLogController.php    # Activity log retrieval
│
├── views/                           # HTML/PHP view files
│   ├── layouts/                     # Layout templates
│   │   ├── header.php               # Common header (navbar, meta tags)
│   │   ├── footer.php               # Common footer
│   │   ├── sidebar.php              # Sidebar navigation
│   │   └── main.php                 # Main layout wrapper
│   │
│   ├── auth/                        # Authentication views
│   │   ├── login.php                # Login page
│   │   ├── register.php             # Registration page
│   │   ├── forgot-password.php      # Password reset request
│   │   └── reset-password.php       # Password reset form
│   │
│   ├── teacher/                     # Teacher role views
│   │   ├── dashboard.php            # Teacher dashboard
│   │   ├── lesson-plans/            # Lesson plan management
│   │   │   ├── index.php            # List all lesson plans
│   │   │   ├── create.php           # Create new lesson plan
│   │   │   ├── edit.php             # Edit lesson plan
│   │   │   └── view.php             # View lesson plan details
│   │   └── profile.php              # Teacher profile
│   │
│   ├── admin/                       # Admin role views
│   │   ├── dashboard.php            # Admin dashboard
│   │   ├── users/                   # User management
│   │   │   ├── index.php            # List all users
│   │   │   ├── create.php           # Create new user
│   │   │   ├── edit.php             # Edit user
│   │   │   └── view.php             # View user details
│   │   ├── activity-logs.php        # View activity logs
│   │   └── system-settings.php      # System configuration
│   │
│   ├── components/                  # Reusable view components
│   │   ├── alerts.php               # Alert messages component
│   │   ├── modals.php               # Modal dialogs
│   │   ├── tables.php               # Data table templates
│   │   └── forms.php                # Form field templates
│   │
│   └── errors/                      # Error pages
│       ├── 404.php                  # Page not found
│       ├── 403.php                  # Access denied
│       └── 500.php                  # Server error
│
├── public/                          # Publicly accessible assets
│   ├── index.php                    # Application entry point
│   ├── css/                         # Stylesheets
│   │   ├── bootstrap.min.css        # Bootstrap framework
│   │   ├── style.css                # Custom styles
│   │   └── admin.css                # Admin-specific styles
│   │
│   ├── js/                          # JavaScript files
│   │   ├── bootstrap.bundle.min.js  # Bootstrap JS
│   │   ├── jquery.min.js            # jQuery library
│   │   ├── app.js                   # Main application JS
│   │   ├── ajax-handler.js          # AJAX request handler
│   │   ├── form-validator.js        # Client-side form validation
│   │   └── lesson-plan.js           # Lesson plan specific JS
│   │
│   ├── images/                      # Image assets
│   │   ├── logo.png                 # Application logo
│   │   ├── icons/                   # Icon files
│   │   └── placeholders/            # Placeholder images
│   │
│   └── fonts/                       # Custom fonts (if any)
│
├── uploads/                         # User uploaded files
│   ├── lesson-plans/                # Lesson plan attachments
│   ├── profiles/                    # User profile pictures
│   └── temp/                        # Temporary uploads
│
├── exports/                         # Generated export files
│   ├── pdf/                         # PDF exports
│   └── word/                        # Word document exports
│
├── database/                        # Database related files
│   ├── migrations/                  # Database migration scripts
│   │   └── create_tables.sql        # Initial table creation
│   ├── seeds/                       # Sample data seeders
│   │   └── default_roles.sql        # Default role data
│   └── schema.sql                   # Complete database schema
│
├── helpers/                         # Helper/utility functions
│   ├── functions.php                # Common utility functions
│   ├── sanitize.php                 # Input sanitization helpers
│   └── response.php                 # JSON response helpers
│
├── middleware/                      # Request middleware
│   ├── AuthMiddleware.php           # Authentication check
│   ├── RoleMiddleware.php           # Role-based access control
│   └── CSRFMiddleware.php           # CSRF token validation
│
├── logs/                            # Application logs
│   ├── app.log                      # General application logs
│   ├── error.log                    # Error logs
│   └── activity.log                 # User activity logs
│
├── vendor/                          # Composer dependencies
│   └── autoload.php                 # Composer autoloader
│
├── .htaccess                        # Apache rewrite rules and security
├── .env.example                     # Environment variables template
├── .env                             # Environment variables (gitignored)
├── .gitignore                       # Git ignore file
├── composer.json                    # Composer dependencies
├── composer.lock                    # Composer lock file
└── README.md                        # Project documentation
```

## Key Design Principles

### 1. **Separation of Concerns**
- **config/**: Centralized configuration management
- **classes/**: Business logic and data models (OOP)
- **controllers/**: Request handling and routing logic
- **views/**: Presentation layer (HTML/PHP templates)
- **public/**: Web-accessible assets only

### 2. **Security**
- Public folder as document root (prevents direct PHP file access)
- Middleware for authentication and authorization
- CSRF protection
- Input sanitization helpers
- Secure file upload handling

### 3. **Role-Based Access**
- Separate view folders for admin and teacher roles
- RoleMiddleware for access control
- Role model for permission management

### 4. **Modular Architecture**
- Reusable components in views/components/
- Helper functions separated by concern
- Object-oriented class structure

### 5. **AJAX Support**
- Dedicated controllers for AJAX handlers
- JSON response helpers
- Client-side AJAX handler (ajax-handler.js)
- Form validation (client + server side)

### 6. **File Management**
- Organized uploads directory by type
- Separate exports directory for generated files
- Proper file handling classes

### 7. **Database Management**
- PDO wrapper for secure database access
- Migration and seed files for version control
- Complete schema documentation

## Database Tables (Reference)
- `users` - User accounts
- `roles` - User roles (Admin, Teacher)
- `lesson_plans` - Lesson plan records
- `lesson_sections` - Sections within lesson plans
- `files` - Uploaded file references
- `qr_codes` - Generated QR code data
- `activity_logs` - User activity tracking
- `password_resets` - Password reset tokens

## Notes
- All PHP files should use namespaces for better organization
- Use PSR-4 autoloading with Composer
- Environment-specific settings in .env file
- Use prepared statements (PDO) for all database queries
- Implement proper error handling and logging
- All AJAX responses should return JSON format
