# What Has Been Done List

This list contains all the folders and files created so far in the PlanWise project.

## Root Directory Files
- .gitignore
- .htaccess
- composer.json
- README.md
- structure.md

## config/
- app.php
- database.php
- mail.php

## classes/
- ActivityLog.php
- Auth.php
- Database.php
- File.php
- LessonPlan.php
- LessonSection.php
- PasswordReset.php
- PDFExporter.php
- QRCode.php
- Role.php
- User.php
- Validator.php
- WordExporter.php

## controllers/
- ActivityLogController.php
- AuthController.php
- ExportController.php
- FileController.php
- LessonPlanController.php
- QRCodeController.php
- UserController.php

## database/
- schema.sql

### database/migrations/
- create_tables.sql

### database/seeds/
- default_roles.sql

## helpers/
- functions.php
- response.php
- sanitize.php

## middleware/
- AuthMiddleware.php
- CSRFMiddleware.php
- RoleMiddleware.php

## public/
- index.php

### public/css/
- admin.css
- style.css

### public/js/
- ajax-handler.js
- app.js
- form-validator.js
- lesson-plan.js

## views/

### views/admin/
- activity-logs.php
- dashboard.php
- system-settings.php

### views/admin/users/
- create.php
- edit.php
- index.php
- view.php

### views/auth/
- forgot-password.php
- login.php
- register.php
- reset-password.php

### views/components/
- alerts.php
- forms.php
- modals.php
- tables.php

### views/errors/
- 403.php
- 404.php
- 500.php

### views/layouts/
- footer.php
- header.php
- main.php
- sidebar.php

### views/teacher/
- dashboard.php
- profile.php

### views/teacher/lesson-plans/
- create.php
- edit.php
- index.php
- view.php
