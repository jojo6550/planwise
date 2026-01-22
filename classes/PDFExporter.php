<?php
/**
 * PDFExporter Class
 * Generates PDF reports for lesson plans using TCPDF
 * CS334 Module 2 - Generate PDF reports (22 marks) + Use of Files (10 marks)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/LessonPlan.php';
require_once __DIR__ . '/LessonSection.php';
require_once __DIR__ . '/File.php';

use TCPDF;

class PDFExporter
{
    private $lessonPlan;
    private $lessonSection;
    private $fileHandler;
    private $exportDir;

    /**
     * Constructor - Initialize PDF exporter
     */
    public function __construct()
    {
        $this->lessonPlan = new LessonPlan();
        $this->lessonSection = new LessonSection();
        $this->fileHandler = new File();
        $this->exportDir = __DIR__ . '/../exports/pdf/';

        // Create export directory if it doesn't exist
        if (!file_exists($this->exportDir)) {
            mkdir($this->exportDir, 0755, true);
        }
    }

    /**
     * Generate PDF for a lesson plan
     *
     * @param int $lessonPlanId Lesson plan ID
     * @param int $userId User ID for authorization
     * @param bool $download Whether to download or save
     * @return array Result with file path or download status
     */
    public function generateLessonPlanPDF(int $lessonPlanId, int $userId, bool $download = true): array
    {
        try {
            // Get lesson plan data
            $plan = $this->lessonPlan->getById($lessonPlanId, $userId);
            if (!$plan) {
                return [
                    'success' => false,
                    'message' => 'Lesson plan not found or unauthorized'
                ];
            }

            // Get lesson sections
            $sections = $this->lessonSection->getByLessonPlan($lessonPlanId);

            // Get attached files
            $files = $this->fileHandler->getByLessonPlan($lessonPlanId);

            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('PlanWise - VTDI');
            $pdf->SetAuthor($plan['first_name'] . ' ' . $plan['last_name']);
            $pdf->SetTitle('Lesson Plan: ' . $plan['title']);
            $pdf->SetSubject('Lesson Plan Export');

            // Set default header data
            $pdf->SetHeaderData('', 0, 'PlanWise - Lesson Plan', 'Vocational Training Development Institute');

            // Set header and footer fonts
            $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
            $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

            // Set margins
            $pdf->SetMargins(15, 27, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);

            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 15);

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 11);

            // Build PDF content
            $html = $this->buildPDFContent($plan, $sections, $files);

            // Write HTML content
            $pdf->writeHTML($html, true, false, true, false, '');

            // Generate filename
            $fileName = 'lesson_plan_' . $lessonPlanId . '_' . time() . '.pdf';
            $filePath = $this->exportDir . $fileName;

            if ($download) {
                // Output PDF for download
                $pdf->Output($fileName, 'D');
                return [
                    'success' => true,
                    'message' => 'PDF generated successfully'
                ];
            } else {
                // Save PDF to file
                $pdf->Output($filePath, 'F');
                return [
                    'success' => true,
                    'message' => 'PDF saved successfully',
                    'file_path' => $filePath,
                    'file_name' => $fileName
                ];
            }

        } catch (Exception $e) {
            error_log("PDF generation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build PDF HTML content
     *
     * @param array $plan Lesson plan data
     * @param array $sections Lesson sections
     * @param array $files Attached files
     * @return string HTML content
     */
    private function buildPDFContent(array $plan, array $sections, array $files): string
    {
        $html = '<style>
            h1 { color: #2c3e50; font-size: 20px; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
            h2 { color: #34495e; font-size: 16px; margin-top: 15px; background-color: #ecf0f1; padding: 5px; }
            h3 { color: #7f8c8d; font-size: 14px; margin-top: 10px; }
            .info-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .info-table td { padding: 5px; border: 1px solid #ddd; }
            .label { font-weight: bold; background-color: #f8f9fa; width: 30%; }
            .content { background-color: #ffffff; }
            .section { margin: 15px 0; padding: 10px; border-left: 3px solid #3498db; background-color: #f8f9fa; }
            ul { margin: 5px 0; padding-left: 20px; }
        </style>';

        // Title
        $html .= '<h1>' . htmlspecialchars($plan['title']) . '</h1>';

        // Basic Information
        $html .= '<h2>Basic Information</h2>';
        $html .= '<table class="info-table">';
        $html .= '<tr><td class="label">Subject:</td><td class="content">' . htmlspecialchars($plan['subject'] ?: 'N/A') . '</td></tr>';
        $html .= '<tr><td class="label">Grade Level:</td><td class="content">' . htmlspecialchars($plan['grade_level'] ?: 'N/A') . '</td></tr>';
        $html .= '<tr><td class="label">Duration:</td><td class="content">' . ($plan['duration'] ? $plan['duration'] . ' minutes' : 'N/A') . '</td></tr>';
        $html .= '<tr><td class="label">Status:</td><td class="content">' . ucfirst(htmlspecialchars($plan['status'])) . '</td></tr>';
        $html .= '<tr><td class="label">Created By:</td><td class="content">' . htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']) . '</td></tr>';
        $html .= '<tr><td class="label">Created At:</td><td class="content">' . date('F j, Y g:i A', strtotime($plan['created_at'])) . '</td></tr>';
        $html .= '</table>';

        // Objectives
        if (!empty($plan['objectives'])) {
            $html .= '<h2>Learning Objectives</h2>';
            $html .= '<p>' . nl2br(htmlspecialchars($plan['objectives'])) . '</p>';
        }

        // Materials
        if (!empty($plan['materials'])) {
            $html .= '<h2>Materials Needed</h2>';
            $html .= '<p>' . nl2br(htmlspecialchars($plan['materials'])) . '</p>';
        }

        // Procedures
        if (!empty($plan['procedures'])) {
            $html .= '<h2>Procedures</h2>';
            $html .= '<p>' . nl2br(htmlspecialchars($plan['procedures'])) . '</p>';
        }

        // Lesson Sections
        if (!empty($sections)) {
            $html .= '<h2>Lesson Sections</h2>';
            foreach ($sections as $section) {
                $html .= '<div class="section">';
                $html .= '<h3>' . htmlspecialchars($section['title']) . ' (' . ucfirst(str_replace('_', ' ', $section['section_type'])) . ')</h3>';
                if ($section['duration']) {
                    $html .= '<p><strong>Duration:</strong> ' . $section['duration'] . ' minutes</p>';
                }
                if (!empty($section['content'])) {
                    $html .= '<p>' . nl2br(htmlspecialchars($section['content'])) . '</p>';
                }
                $html .= '</div>';
            }
        }

        // Assessment
        if (!empty($plan['assessment'])) {
            $html .= '<h2>Assessment</h2>';
            $html .= '<p>' . nl2br(htmlspecialchars($plan['assessment'])) . '</p>';
        }

        // Notes
        if (!empty($plan['notes'])) {
            $html .= '<h2>Additional Notes</h2>';
            $html .= '<p>' . nl2br(htmlspecialchars($plan['notes'])) . '</p>';
        }

        // Attached Files
        if (!empty($files)) {
            $html .= '<h2>Attached Files</h2>';
            $html .= '<ul>';
            foreach ($files as $file) {
                $html .= '<li>' . htmlspecialchars($file['original_name']) . ' (' . $this->formatFileSize($file['file_size']) . ')</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Format file size in human-readable format
     *
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
