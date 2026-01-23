<?php
/**
 * WordExporter Class
 * Generates Word documents for lesson plans using PHPWord
 * CS334 Module 2 - Generate Word reports (22 marks) + Use of Files (10 marks)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/LessonPlan.php';
require_once __DIR__ . '/LessonSection.php';
require_once __DIR__ . '/File.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;

class WordExporter
{
    private $lessonPlan;
    private $lessonSection;
    private $fileHandler;
    private $exportDir;

    /**
     * Constructor - Initialize Word exporter
     */
    public function __construct()
    {
        $this->lessonPlan = new LessonPlan();
        $this->lessonSection = new LessonSection();
        $this->fileHandler = new File();
        $this->exportDir = __DIR__ . '/../exports/word/';

        // Create export directory if it doesn't exist
        if (!file_exists($this->exportDir)) {
            mkdir($this->exportDir, 0755, true);
        }
    }

    /**
     * Generate Word document for a lesson plan
     *
     * @param int $lessonPlanId Lesson plan ID
     * @param int $userId User ID for authorization
     * @param bool $download Whether to download or save
     * @return array Result with file path or download status
     */
    public function generateLessonPlanWord(int $lessonPlanId, int $userId, bool $download = true): array
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

            // Create new Word document
            $phpWord = new PhpWord();

            // Set document properties
            $properties = $phpWord->getDocInfo();
            $properties->setCreator('PlanWise - VTDI');
            $properties->setCompany('Vocational Training Development Institute');
            $properties->setTitle('Lesson Plan: ' . $plan['title']);
            $properties->setDescription('Lesson Plan Export');
            $properties->setCategory('Education');
            $properties->setLastModifiedBy($plan['first_name'] . ' ' . $plan['last_name']);
            $properties->setCreated(time());
            $properties->setModified(time());

            // Add a section
            $section = $phpWord->addSection();

            // Build Word content
            $this->buildWordContent($section, $plan, $sections, $files);

            // Generate filename
            $fileName = 'lesson_plan_' . $lessonPlanId . '_' . time() . '.docx';
            $filePath = $this->exportDir . $fileName;

            if ($download) {
                // Output Word document for download
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Cache-Control: max-age=0');

                $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save('php://output');
                exit();
            } else {
                // Save Word document to file
                $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save($filePath);
                return [
                    'success' => true,
                    'message' => 'Word document saved successfully',
                    'file_path' => $filePath,
                    'file_name' => $fileName
                ];
            }

        } catch (Exception $e) {
            error_log("Word generation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate Word document: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build Word document content
     *
     * @param \PhpOffice\PhpWord\Element\Section $section Word section
     * @param array $plan Lesson plan data
     * @param array $sections Lesson sections
     * @param array $files Attached files
     */
    private function buildWordContent($section, array $plan, array $sections, array $files)
    {
        // Title
        $section->addTitle(htmlspecialchars($plan['title']), 1);

        // Basic Information
        $section->addTitle('Basic Information', 2);

        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2000)->addText('Subject:', ['bold' => true]);
        $table->addCell(5000)->addText(htmlspecialchars($plan['subject'] ?: 'N/A'));

        $table->addRow();
        $table->addCell(2000)->addText('Grade Level:', ['bold' => true]);
        $table->addCell(5000)->addText(htmlspecialchars($plan['grade_level'] ?: 'N/A'));

        $table->addRow();
        $table->addCell(2000)->addText('Duration:', ['bold' => true]);
        $table->addCell(5000)->addText($plan['duration'] ? $plan['duration'] . ' minutes' : 'N/A');

        $table->addRow();
        $table->addCell(2000)->addText('Status:', ['bold' => true]);
        $table->addCell(5000)->addText(ucfirst(htmlspecialchars($plan['status'])));

        $table->addRow();
        $table->addCell(2000)->addText('Created By:', ['bold' => true]);
        $table->addCell(5000)->addText(htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']));

        $table->addRow();
        $table->addCell(2000)->addText('Created At:', ['bold' => true]);
        $table->addCell(5000)->addText(date('F j, Y g:i A', strtotime($plan['created_at'])));

        // Objectives
        if (!empty($plan['objectives'])) {
            $section->addTitle('Learning Objectives', 2);
            $section->addText(htmlspecialchars($plan['objectives']));
        }

        // Materials
        if (!empty($plan['materials'])) {
            $section->addTitle('Materials Needed', 2);
            $section->addText(htmlspecialchars($plan['materials']));
        }

        // Procedures
        if (!empty($plan['procedures'])) {
            $section->addTitle('Procedures', 2);
            $section->addText(htmlspecialchars($plan['procedures']));
        }

        // Lesson Sections
        if (!empty($sections)) {
            $section->addTitle('Lesson Sections', 2);
            foreach ($sections as $sectionData) {
                $section->addTitle(htmlspecialchars($sectionData['title']) . ' (' . ucfirst(str_replace('_', ' ', $sectionData['section_type'])) . ')', 3);
                if ($sectionData['duration']) {
                    $section->addText('Duration: ' . $sectionData['duration'] . ' minutes', ['italic' => true]);
                }
                if (!empty($sectionData['content'])) {
                    $section->addText(htmlspecialchars($sectionData['content']));
                }
                $section->addTextBreak(1);
            }
        }

        // Assessment
        if (!empty($plan['assessment'])) {
            $section->addTitle('Assessment', 2);
            $section->addText(htmlspecialchars($plan['assessment']));
        }

        // Notes
        if (!empty($plan['notes'])) {
            $section->addTitle('Additional Notes', 2);
            $section->addText(htmlspecialchars($plan['notes']));
        }

        // Attached Files
        if (!empty($files)) {
            $section->addTitle('Attached Files', 2);
            foreach ($files as $file) {
                $section->addListItem(htmlspecialchars($file['original_name']) . ' (' . $this->formatFileSize($file['file_size']) . ')');
            }
        }
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
