# PlanWise Lesson Plan Email Sharing Guide

## Overview

The email sharing feature allows teachers to easily email lesson plans to colleagues and other educators. This guide explains how to configure and use the email functionality in PlanWise.

## Features

- **Single Recipient Email**: Send a lesson plan to one person with a personalized message
- **Multiple Recipients**: Send the same lesson plan to multiple people at once
- **Professional Templates**: Pre-formatted email templates with all lesson plan details
- **Activity Logging**: All email sends are logged for audit purposes
- **Error Handling**: Comprehensive error handling and user feedback

## Setup Configuration

### 1. Environment Variables

Configure your email settings in `.env` file:

```env
# Mail Configuration
MAIL_DRIVER=mail                      # Use 'mail' for PHP's mail() function
MAIL_HOST=smtp.mailtrap.io           # SMTP host (if using SMTP)
MAIL_PORT=2525                       # SMTP port
MAIL_USERNAME=                       # SMTP username
MAIL_PASSWORD=                       # SMTP password
MAIL_FROM_NAME=PlanWise              # Sender name
MAIL_FROM_EMAIL=noreply@planwise.local  # Sender email
```

### 2. Email Driver Options

#### Option A: PHP mail() Function (Recommended for XAMPP)

```env
MAIL_DRIVER=mail
MAIL_FROM_NAME=PlanWise
MAIL_FROM_EMAIL=noreply@planwise.local
```

This uses your server's built-in mail function. Works by default on most systems.

#### Option B: SMTP (Gmail, Mailgun, Mailtrap, etc.)

For Gmail:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_NAME=PlanWise
MAIL_FROM_EMAIL=your-email@gmail.com
```

For Mailtrap (testing):
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_FROM_NAME=PlanWise
MAIL_FROM_EMAIL=noreply@planwise.local
```

## Usage

### For Teachers: Sharing Lesson Plans

#### Method 1: Using the Web Interface

1. Navigate to your lesson plan
2. Click the "Share via Email" button (email icon)
3. Choose sharing method:
   - **Single Recipient**: Enter one email address
   - **Multiple Recipients**: Enter multiple emails (one per line)
4. Add optional personal message
5. Click "Send Email"

#### Method 2: Using the API

**Single Recipient:**

```php
require_once 'classes/Mail.php';

$mail = new Mail();
$result = $mail->sendLessonPlanEmail(
    $lessonPlanData,  // Array with lesson plan info
    $senderData,      // Array with sender info
    'recipient@example.com',
    'Recipient Name',
    'Check out this great lesson plan!'
);

if ($result['success']) {
    echo "Email sent successfully!";
} else {
    echo "Error: " . $result['message'];
}
```

**Multiple Recipients:**

```php
$recipients = [
    ['email' => 'teacher1@school.com', 'name' => 'Teacher One'],
    ['email' => 'teacher2@school.com', 'name' => 'Teacher Two']
];

$result = $mail->sendLessonPlanToMultiple(
    $lessonPlanData,
    $senderData,
    $recipients,
    'Please review this lesson plan'
);

echo "Sent to: " . $result['success_count'] . " recipients";
```

## API Endpoints

### Email Single Lesson Plan

**Endpoint:** `POST /planwise/controllers/LessonPlanController.php?action=emailLesson`

**Request Body:**
```json
{
    "lesson_id": 123,
    "recipient_email": "colleague@example.com",
    "recipient_name": "John Doe",
    "message": "Please check this lesson plan"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Lesson plan sent successfully"
}
```

### Email Multiple Recipients

**Endpoint:** `POST /planwise/controllers/LessonPlanController.php?action=emailLessonMultiple`

**Request Body:**
```json
{
    "lesson_id": 123,
    "recipients": [
        {"email": "teacher1@example.com", "name": "Teacher One"},
        {"email": "teacher2@example.com", "name": "Teacher Two"}
    ],
    "message": "Check out this lesson plan"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Sent to 2 recipient(s)",
    "success_count": 2,
    "failure_count": 0,
    "failures": []
}
```

## Email Templates

### Lesson Plan Email Template

The lesson plan email includes:
- Personalized greeting
- Lesson title, subject, and grade level
- Complete lesson content (objectives, materials, procedures, assessment)
- Link to view online
- Sender name and affiliation

**Template Example:**

```
Hello [Recipient Name],

[Sender Name] ([Sender Email]) has shared the following lesson plan with you:

Lesson Title: Advanced Fractions
Subject: Mathematics
Grade Level: 5-6

OBJECTIVES:
Students will be able to add and subtract fractions with unlike denominators...

MATERIALS:
- Fraction manipulatives
- Whiteboard...

[Personal Message if provided]

You can view the full lesson plan online: [Link]

Best regards,
PlanWise Team
```

## PHP Classes and Methods

### Mail Class

Located in: `classes/Mail.php`

**Key Methods:**

1. `send(string $to, string $subject, string $body, array $headers = []): array`
   - Generic email sending method

2. `sendLessonPlanEmail(array $lessonPlan, array $sender, string $recipientEmail, string $recipientName, string $message = ''): array`
   - Send lesson plan to single recipient
   - Returns: `['success' => bool, 'message' => string]`

3. `sendLessonPlanToMultiple(array $lessonPlan, array $sender, array $recipients, string $message = ''): array`
   - Send lesson plan to multiple recipients
   - Returns: `['success' => bool, 'message' => string, 'success_count' => int, 'failure_count' => int, 'failures' => array]`

4. `sendTemplateEmail(string $templateName, array $replacements, string $to): array`
   - Send using predefined templates

5. `sendRegistrationEmail(array $user): array`
   - Send registration welcome email

## Error Handling

### Common Errors and Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| "Invalid recipient email" | Email format is wrong | Use valid email format: user@domain.com |
| "No valid recipients" | All emails are invalid | Check email addresses in the list |
| "Lesson plan not found or unauthorized" | User doesn't own the lesson | Only teachers can email their own plans |
| "Failed to send email" | Server mail configuration | Check `.env` MAIL_* settings |

### Mail Server Testing

To test your email configuration:

```php
require_once 'classes/Mail.php';

$mail = new Mail();
$testResult = $mail->send(
    'test@example.com',
    'PlanWise Email Test',
    'If you receive this, email is working!'
);

if ($testResult['success']) {
    echo "Email sent successfully!";
} else {
    echo "Error: " . $testResult['message'];
}
```

## Activity Logging

All email sends are logged in the `activity_logs` table with:
- User ID
- Action: `lesson_plan_emailed` or `lesson_plan_emailed_multiple`
- Description: Lesson title and recipient(s)
- Timestamp

**View logs in admin panel:** Admin Dashboard → Activity Logs

## Security Considerations

1. **Authentication Required**: Only authenticated teachers can send emails
2. **Authorization**: Teachers can only email their own lesson plans
3. **Input Validation**: Email addresses validated before sending
4. **XSS Prevention**: All user input sanitized
5. **CSRF Protection**: Form tokens validated
6. **Error Logging**: All errors logged for debugging

## Database Schema

No new tables required. Uses existing tables:
- `users`: Sender information
- `lesson_plans`: Lesson content
- `activity_logs`: Email send tracking

## Configuration Files

### config/mail.php
Defines email templates and configuration options:
- `driver`: Mail driver type
- `from`: Sender name and email
- `templates`: Email template definitions

### .env
Runtime email configuration:
```env
MAIL_DRIVER=mail
MAIL_FROM_NAME=PlanWise
MAIL_FROM_EMAIL=noreply@planwise.local
```

## Troubleshooting

### Emails Not Sending

1. **Check error logs:** `tail -f logs/database.log`
2. **Verify `.env` configuration**
3. **Test mail function:** `php -a` then `mail('test@example.com', 'Test', 'Hello');`
4. **Check server mail logs:** `/var/log/mail.log` (Linux) or Windows Event Viewer

### XAMPP Configuration

For XAMPP on Windows, add this to `php.ini`:

```ini
[mail function]
SMTP=localhost
smtp_port=25
sendmail_from = noreply@planwise.local
```

## Example Implementation

### Basic Example

```php
<?php
require_once 'classes/Mail.php';
require_once 'classes/LessonPlan.php';

$mail = new Mail();
$lessonPlan = new LessonPlan();

// Get lesson plan
$lesson = $lessonPlan->getById(123, $userId);

// Send email
$result = $mail->sendLessonPlanEmail(
    $lesson,
    ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@school.com'],
    'colleague@school.com',
    'Colleague Name',
    'Check this out!'
);

if ($result['success']) {
    echo "Email sent!";
} else {
    echo "Failed: " . $result['message'];
}
?>
```

### Batch Processing

```php
$recipients = [
    ['email' => 'teacher1@school.com', 'name' => 'Teacher 1'],
    ['email' => 'teacher2@school.com', 'name' => 'Teacher 2'],
    // ... more recipients
];

$result = $mail->sendLessonPlanToMultiple($lesson, $sender, $recipients);

echo "Sent to " . $result['success_count'] . " of " . count($recipients) . " recipients";

if ($result['failure_count'] > 0) {
    echo "\nFailures:\n";
    foreach ($result['failures'] as $failure) {
        echo "- " . $failure . "\n";
    }
}
```

## Support

For issues or questions:
1. Check the Activity Logs in admin panel
2. Review error logs in `logs/` directory
3. Test with the troubleshooting examples above
4. Verify `.env` configuration

## CS334 Module Mapping

This implementation covers:
- **Module 2 - PHP Mail (10 marks)**
  - Email sending using PHP mail() function
  - Email templates with placeholders
  - Error handling and validation
  - Activity logging

- **Module 1 - Database Manipulation**
  - Lesson plan retrieval
  - User information access
  - Activity log insertion

- **Module 3 - OOP and Control Structures**
  - Mail class encapsulation
  - Loop-based recipient handling
  - Conditional logic for error handling
