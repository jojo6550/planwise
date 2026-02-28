<?php
/**
 * Mail Class
 * Handles email sending using PHP mail() function
 * CS334 Module 2 - PHP Mail (10 marks)
 */

require_once __DIR__ . '/../config/mail.php';

class Mail
{
    private $config;

    /**
     * Constructor - Load mail configuration
     */
    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    /**
     * Send an email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body
     * @param array $headers Additional headers
     * @return array Result with success status
     */
    public function send(string $to, string $subject, string $body, array $headers = []): array
    {
        try {
            // Set default headers
            $defaultHeaders = [
                'MIME-Version: 1.0',
                'Content-type: text/plain; charset=UTF-8',
                'From: ' . $this->config['from']['name'] . ' <' . $this->config['from']['email'] . '>',
                'Reply-To: ' . $this->config['from']['email'],
                'X-Mailer: PHP/' . phpversion()
            ];

            // Merge with additional headers
            $allHeaders = array_merge($defaultHeaders, $headers);
            $headersString = implode("\r\n", $allHeaders);

            // Log email attempt
            error_log("=== EMAIL DEBUG START ===");
            error_log("To: " . $to);
            error_log("Subject: " . $subject);
            error_log("From: " . $this->config['from']['name'] . ' <' . $this->config['from']['email'] . '>');
            error_log("Headers: " . $headersString);
            error_log("Body preview: " . substr($body, 0, 200) . "...");
            
            // Check if mail() function is available
            if (!function_exists('mail')) {
                error_log("ERROR: mail() function is not available!");
                return [
                    'success' => false,
                    'message' => 'Email sending is not available on this server'
                ];
            }

            // Send email using PHP mail function
            $result = mail($to, $subject, $body, $headersString);

            error_log("mail() returned: " . ($result ? 'true' : 'false'));
            error_log("=== EMAIL DEBUG END ===");

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Email sent successfully'
                ];
            } else {
                // Get additional error info
                $error = error_get_last();
                error_log("mail() failed. Last error: " . ($error ? $error['message'] : 'Unknown error'));
                return [
                    'success' => false,
                    'message' => 'Failed to send email. Check server logs for details.'
                ];
            }

        } catch (Exception $e) {
            error_log("Mail send EXCEPTION: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Email sending failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send registration welcome email
     *
     * @param array $user User data
     * @return array Result
     */
    public function sendRegistrationEmail(array $user): array
    {
        $template = $this->config['templates']['registration'];

        // Replace placeholders
        $subject = $template['subject'];
        $body = str_replace(
            ['{first_name}', '{last_name}', '{email}', '{date}'],
            [$user['first_name'], $user['last_name'], $user['email'], date('Y-m-d H:i:s')],
            $template['body']
        );

        return $this->send($user['email'], $subject, $body);
    }

    /**
     * Send lesson plan creation notification
     *
     * @param array $user User data
     * @param array $lessonPlan Lesson plan data
     * @return array Result
     */
    public function sendLessonPlanCreatedEmail(array $user, array $lessonPlan): array
    {
        $template = $this->config['templates']['lesson_plan_created'];

        // Replace placeholders
        $subject = $template['subject'];
        $body = str_replace(
            ['{first_name}', '{last_name}', '{lesson_title}', '{lesson_url}'],
            [
                $user['first_name'],
                $user['last_name'],
                $lessonPlan['title'],
                '/planwise/public/index.php?page=teacher/lesson-plans/view&id=' . $lessonPlan['lesson_id']
            ],
            $template['body']
        );

        return $this->send($user['email'], $subject, $body);
    }

    /**
     * Send password reset email
     *
     * @param array $user User data (needs first_name, email)
     * @param string $token Password reset token
     * @return array Result
     */
    public function sendPasswordResetEmail(array $user, string $token): array
    {
        error_log("PASSWORD RESET EMAIL: Preparing to send to: " . $user['email']);
        
        // Check if password_reset template exists
        if (!isset($this->config['templates']['password_reset'])) {
            error_log("PASSWORD RESET EMAIL ERROR: Template not found in config");
            return [
                'success' => false,
                'message' => 'Password reset template not found'
            ];
        }

        $template = $this->config['templates']['password_reset'];
        
        // Build reset URL
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost/planwise/public/';
        $resetUrl = $appUrl . 'index.php?page=reset-password&token=' . $token;

        // Replace placeholders
        $subject = str_replace('{first_name}', $user['first_name'] ?? 'User', $template['subject']);
        $body = str_replace(
            ['{first_name}', '{reset_url}'],
            [$user['first_name'] ?? 'User', $resetUrl],
            $template['body']
        );

        error_log("PASSWORD RESET EMAIL: Subject: " . $subject);
        error_log("PASSWORD RESET EMAIL: Reset URL: " . $resetUrl);
        
        return $this->send($user['email'], $subject, $body);
    }

    /**
     * Send custom email using template
     *
     * @param string $templateName Template name
     * @param array $replacements Key-value pairs for replacement
     * @param string $to Recipient email
     * @return array Result
     */
    public function sendTemplateEmail(string $templateName, array $replacements, string $to): array
    {
        if (!isset($this->config['templates'][$templateName])) {
            return [
                'success' => false,
                'message' => 'Email template not found'
            ];
        }

        $template = $this->config['templates'][$templateName];

        // Replace placeholders in subject and body
        $subject = str_replace(array_keys($replacements), array_values($replacements), $template['subject']);
        $body = str_replace(array_keys($replacements), array_values($replacements), $template['body']);

        return $this->send($to, $subject, $body);
    }

    /**
     * Send lesson plan email
     *
     * @param array $lessonPlan Lesson plan data
     * @param array $sender Sender user data
     * @param string $recipientEmail Recipient email address
     * @param string $recipientName Recipient name
     * @param string $message Optional personal message
     * @return array Result
     */
    public function sendLessonPlanEmail(array $lessonPlan, array $sender, string $recipientEmail, string $recipientName, string $message = ''): array
    {
        try {
            $template = $this->config['templates']['lesson_plan_email'];

            // Build replacements array
            $replacements = [
                '{recipient_name}' => htmlspecialchars($recipientName),
                '{sender_name}' => htmlspecialchars($sender['first_name'] . ' ' . $sender['last_name']),
                '{sender_email}' => $sender['email'],
                '{lesson_title}' => htmlspecialchars($lessonPlan['title']),
                '{subject}' => htmlspecialchars($lessonPlan['subject'] ?? 'N/A'),
                '{grade_level}' => htmlspecialchars($lessonPlan['grade_level'] ?? 'N/A'),
                '{duration}' => htmlspecialchars($lessonPlan['duration'] ?? 'N/A'),
                '{objectives}' => trim($lessonPlan['objectives'] ?? 'No objectives specified'),
                '{materials}' => trim($lessonPlan['materials'] ?? 'No materials specified'),
                '{procedures}' => trim($lessonPlan['procedures'] ?? 'No procedures specified'),
                '{assessment}' => trim($lessonPlan['assessment'] ?? 'No assessment specified'),
                '{lesson_url}' => $_ENV['APP_URL'] . 'index.php?page=teacher/lesson-plans/view&id=' . $lessonPlan['lesson_id'],
            ];

            // Add personal message if provided
            if (!empty($message)) {
                $body = "Hello {recipient_name},\n\n" .
                       "{sender_name} ({sender_email}) has shared the following lesson plan with you and included this message:\n\n" .
                       "\"" . htmlspecialchars($message) . "\"\n\n" .
                       str_replace('{recipient_name}', '', str_replace('{sender_name}', '', $template['body']));
            } else {
                $body = $template['body'];
            }

            $subject = str_replace(array_keys($replacements), array_values($replacements), $template['subject']);
            $body = str_replace(array_keys($replacements), array_values($replacements), $body);

            return $this->send($recipientEmail, $subject, $body);

        } catch (Exception $e) {
            error_log("Send lesson plan email failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send lesson plan email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send lesson plan to multiple recipients
     *
     * @param array $lessonPlan Lesson plan data
     * @param array $sender Sender user data
     * @param array $recipients Array of recipient data with 'email' and 'name' keys
     * @param string $message Optional personal message
     * @return array Result with success count and failures
     */
    public function sendLessonPlanToMultiple(array $lessonPlan, array $sender, array $recipients, string $message = ''): array
    {
        $successCount = 0;
        $failures = [];

        foreach ($recipients as $recipient) {
            if (empty($recipient['email'])) {
                $failures[] = 'Invalid recipient: missing email';
                continue;
            }

            $result = $this->sendLessonPlanEmail(
                $lessonPlan,
                $sender,
                $recipient['email'],
                $recipient['name'] ?? 'Colleague',
                $message
            );

            if ($result['success']) {
                $successCount++;
            } else {
                $failures[] = $recipient['email'] . ': ' . $result['message'];
            }
        }

        return [
            'success' => count($failures) === 0 || $successCount > 0,
            'message' => "Sent to $successCount recipient(s)" . (count($failures) > 0 ? ", " . count($failures) . " failed" : ""),
            'success_count' => $successCount,
            'failure_count' => count($failures),
            'failures' => $failures
        ];
    }
}
