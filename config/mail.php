<?php
/**
 * Mail Configuration
 * Configure email settings and templates
 */

return [
    'driver' => $_ENV['MAIL_DRIVER'] ?? 'mail',
    'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
    'port' => $_ENV['MAIL_PORT'] ?? 25,
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'from' => [
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'PlanWise',
        'email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@planwise.local'
    ],
    'templates' => [
        'registration' => [
            'subject' => 'Welcome to PlanWise',
            'body' => "Hello {first_name},\n\n" .
                    "Welcome to PlanWise! Your account has been successfully created.\n\n" .
                    "Email: {email}\n" .
                    "Date: {date}\n\n" .
                    "You can now login and start creating lesson plans.\n\n" .
                    "Best regards,\n" .
                    "The PlanWise Team"
        ],
        'lesson_plan_created' => [
            'subject' => 'Lesson Plan Created - {lesson_title}',
            'body' => "Hello {first_name},\n\n" .
                    "Your lesson plan has been created successfully.\n\n" .
                    "Lesson Title: {lesson_title}\n" .
                    "View it here: {lesson_url}\n\n" .
                    "You can edit, publish, or share this lesson plan with colleagues.\n\n" .
                    "Best regards,\n" .
                    "The PlanWise Team"
        ],
        'lesson_plan_email' => [
            'subject' => 'Lesson Plan - {lesson_title}',
            'body' => "Hello {recipient_name},\n\n" .
                    "{sender_name} has shared a lesson plan with you.\n\n" .
                    "Lesson Title: {lesson_title}\n" .
                    "Subject: {subject}\n" .
                    "Grade Level: {grade_level}\n" .
                    "Duration: {duration} minutes\n\n" .
                    "OBJECTIVES:\n{objectives}\n\n" .
                    "MATERIALS:\n{materials}\n\n" .
                    "PROCEDURES:\n{procedures}\n\n" .
                    "ASSESSMENT:\n{assessment}\n\n" .
                    "View the full lesson plan online: {lesson_url}\n\n" .
                    "Best regards,\n" .
                    "{sender_name}\n" .
                    "Via PlanWise"
        ],
        'lesson_plan_share' => [
            'subject' => 'Shared Lesson Plan - {lesson_title}',
            'body' => "Hello {recipient_name},\n\n" .
                    "{sender_name} ({sender_email}) has shared the following lesson plan with you:\n\n" .
                    "Lesson Title: {lesson_title}\n" .
                    "Subject: {subject}\n" .
                    "Grade Level: {grade_level}\n\n" .
                    "Message: {message}\n\n" .
                    "You can view and download this lesson plan here: {lesson_url}\n\n" .
                    "Best regards,\n" .
                    "PlanWise Team"
        ],
        'password_reset' => [
            'subject' => 'Password Reset Request - PlanWise',
            'body' => "Hello {first_name},\n\n" .
                    "We received a request to reset your password.\n\n" .
                    "Click here to reset your password: {reset_url}\n\n" .
                    "If you didn't request a password reset, ignore this email.\n\n" .
                    "Best regards,\n" .
                    "The PlanWise Team"
        ]
    ]
];
