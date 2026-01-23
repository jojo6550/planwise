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

            // Send email using PHP mail function
            $result = mail($to, $subject, $body, $headersString);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Email sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send email'
                ];
            }

        } catch (Exception $e) {
            error_log("Mail send failed: " . $e->getMessage());
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
}
