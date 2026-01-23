# PlanWise - Missing Features Implementation

## ✅ COMPLETED
- Analysis of existing codebase and identification of missing features

## 🚧 IN PROGRESS

### 1. QR Code Generation (Module 2 - PHP Libraries)
- [ ] Install chillerlan/php-qrcode via Composer
- [ ] Implement QRCode::generate() method with proper library usage
- [ ] Create QRCodeController for handling requests
- [ ] Ensure QR codes can be embedded in PDF exports and lesson plan views

### 2. CSV/XLS Data Import (Module 2 - File Handling)
- [ ] Implement ImportController with CSV parsing logic
- [ ] Add file validation (type, size, required columns)
- [ ] Implement database insertion with PDO prepared statements
- [ ] Add success/failure logging and reporting

### 3. Word Document Export (DOCX)
- [ ] Install phpoffice/phpword via Composer
- [ ] Implement WordExporter class with document generation
- [ ] Match lesson plan structure used in PDF export
- [ ] Ensure clean formatting and readability

### 4. Admin User Management UI
- [ ] Create views/admin/users/index.php for user listing
- [ ] Add user management actions (activate/deactivate, role changes)
- [ ] Implement AJAX functionality for status updates
- [ ] Ensure admin-only access with role middleware

### 5. Password Reset Email Completion
- [ ] Add password reset email method to Mail class
- [ ] Update AuthController::forgotPassword() to send actual emails
- [ ] Include reset link with token in email
- [ ] Test email delivery and token validation

## 📋 IMPLEMENTATION ORDER
1. Install required Composer packages
2. Implement QR Code generation
3. Implement Word document export
4. Implement CSV import functionality
5. Complete admin user management UI
6. Add password reset email functionality

## 🧪 TESTING CHECKLIST
- [ ] QR codes generate and are scannable
- [ ] Word documents open correctly in Microsoft Word
- [ ] CSV import validates files and imports data correctly
- [ ] Admin can manage users (list, edit roles, activate/deactivate)
- [ ] Password reset emails are sent and links work
- [ ] All features work without breaking existing functionality
