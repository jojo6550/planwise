# Quick Email Setup for PlanWise

## 5-Minute Setup Guide

### Step 1: Configure Environment (.env)

For **XAMPP** or **Local Development**:
```env
MAIL_DRIVER=mail
MAIL_FROM_NAME=PlanWise
MAIL_FROM_EMAIL=noreply@planwise.local
```

For **Production with Gmail**:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_NAME=PlanWise
MAIL_FROM_EMAIL=your-email@gmail.com
```

For **Mailtrap Testing**:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_NAME=PlanWise
MAIL_FROM_EMAIL=noreply@planwise.local
```

### Step 2: Add Email Button to Lesson Plan View

In your lesson plan view file (e.g., `views/teacher/lesson-plans/view.php`), add this button:

```html
<!-- Add this button near your other action buttons -->
<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailShareModal">
    <i class="bi bi-envelope"></i> Share via Email
</button>

<!-- Include the email share modal at the bottom of the page -->
<?php include __DIR__ . '/email-share.php'; ?>
```

### Step 3: Test Email Sending

Create a test script `test-email.php`:

```php
<?php
require_once 'vendor/autoload.php';
require_once 'classes/Mail.php';

$mail = new Mail();

// Test sending
$result = $mail->send(
    'your-email@example.com',
    'PlanWise Email Test',
    'If you receive this, email is configured correctly!'
);

if ($result['success']) {
    echo "✓ Email sent successfully!";
} else {
    echo "✗ Error: " . $result['message'];
}
?>
```

Run: `php test-email.php`

## Usage Examples

### Send to Single Colleague

```javascript
// From your frontend JavaScript
const formData = {
    lesson_id: 123,
    recipient_email: 'colleague@school.com',
    recipient_name: 'John Doe',
    message: 'Check this out!'
};

fetch('/planwise/controllers/LessonPlanController.php?action=emailLesson', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData)
})
.then(res => res.json())
.then(data => console.log(data.message));
```

### Send to Multiple Recipients

```javascript
const formData = {
    lesson_id: 123,
    recipients: [
        { email: 'teacher1@school.com', name: 'Teacher One' },
        { email: 'teacher2@school.com', name: 'Teacher Two' }
    ],
    message: 'Please review'
};

fetch('/planwise/controllers/LessonPlanController.php?action=emailLessonMultiple', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData)
})
.then(res => res.json())
.then(data => {
    console.log(`Sent to ${data.success_count} recipients`);
    if (data.failures.length > 0) {
        console.log('Failures:', data.failures);
    }
});
```

## Key Files

| File | Purpose |
|------|---------|
| `config/mail.php` | Email configuration & templates |
| `classes/Mail.php` | Mail sending class |
| `controllers/LessonPlanController.php` | API endpoints for email |
| `views/teacher/lesson-plans/email-share.php` | Frontend modal component |
| `LESSON_PLAN_EMAIL_GUIDE.md` | Full documentation |

## Verify Installation

Check these files are in place:
- ✓ `config/mail.php` - Configuration file
- ✓ `classes/Mail.php` - Mail class with email methods
- ✓ `controllers/LessonPlanController.php` - Email methods added
- ✓ `views/teacher/lesson-plans/email-share.php` - Frontend component
- ✓ `.env` - Environment variables configured

## Troubleshooting

**Email not sending?**
1. Check `.env` has correct `MAIL_FROM_EMAIL`
2. Test with `test-email.php`
3. Check `logs/` directory for errors
4. Verify server mail configuration

**XAMPP on Windows?**
Add to `php.ini`:
```ini
[mail function]
SMTP=localhost
smtp_port=25
```

**Gmail not working?**
1. Enable 2FA on Gmail
2. Generate app password: https://myaccount.google.com/apppasswords
3. Use app password in `.env`

## What's Included

✓ Single and multiple recipient support
✓ Professional email templates
✓ Activity logging
✓ Error handling & validation
✓ Frontend modal interface
✓ AJAX-based sending
✓ Comprehensive documentation
✓ Security: Auth + authorization checks

## Next Steps

1. Configure `.env` with your email settings
2. Add email button to lesson plan view
3. Test with `test-email.php`
4. Use the email share modal in your app!

## Support

- Full guide: See `LESSON_PLAN_EMAIL_GUIDE.md`
- Code examples: See `classes/Mail.php`
- Frontend: See `views/teacher/lesson-plans/email-share.php`
