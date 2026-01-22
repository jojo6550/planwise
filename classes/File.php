<?php
/**
 * File Class
 * Handles secure file uploads and management
 * CS334 Module 2 - Use of Files (10 marks) + Upload images (10 marks)
 */

require_once __DIR__ . '/Database.php';

class File
{
    private $db;
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;

    /**
     * Constructor - Initialize file handler
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../uploads/lesson-plans/';
        $this->allowedTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB

        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a file
     *
     * @param array $file File from $_FILES
     * @param int $userId User ID uploading the file
     * @param int|null $lessonPlanId Associated lesson plan ID (optional)
     * @return array Result with success status and file data
     */
    public function upload(array $file, int $userId, ?int $lessonPlanId = null): array
    {
        try {
            // Validate file upload
            $validation = $this->validateUpload($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $this->uploadDir . $fileName;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return [
                    'success' => false,
                    'message' => 'Failed to move uploaded file'
                ];
            }

            // Store file metadata in database
            $sql = "INSERT INTO files (user_id, lesson_plan_id, original_name, file_name, file_path, file_type, file_size, uploaded_at)
                    VALUES (:user_id, :lesson_plan_id, :original_name, :file_name, :file_path, :file_type, :file_size, NOW())";

            $params = [
                ':user_id' => $userId,
                ':lesson_plan_id' => $lessonPlanId,
                ':original_name' => $file['name'],
                ':file_name' => $fileName,
                ':file_path' => $filePath,
                ':file_type' => $file['type'],
                ':file_size' => $file['size']
            ];

            $fileId = $this->db->insert($sql, $params);

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_id' => $fileId,
                'file_name' => $fileName,
                'original_name' => $file['name']
            ];

        } catch (Exception $e) {
            error_log("File upload failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'File upload failed'
            ];
        }
    }

    /**
     * Validate file upload
     *
     * @param array $file File from $_FILES
     * @return array Validation result
     */
    private function validateUpload(array $file): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'File upload error: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => 'File size exceeds maximum allowed size (5MB)'
            ];
        }

        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'File type not allowed'
            ];
        }

        return ['success' => true];
    }

    /**
     * Get upload error message
     *
     * @param int $errorCode Upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Get files by lesson plan ID
     *
     * @param int $lessonPlanId Lesson plan ID
     * @return array Files
     */
    public function getByLessonPlan(int $lessonPlanId): array
    {
        try {
            $sql = "SELECT * FROM files
                    WHERE lesson_plan_id = :lesson_plan_id
                    ORDER BY uploaded_at DESC";

            return $this->db->fetchAll($sql, [':lesson_plan_id' => $lessonPlanId]);

        } catch (Exception $e) {
            error_log("Get files by lesson plan failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a file
     *
     * @param int $fileId File ID
     * @param int $userId User ID (for authorization)
     * @return array Result
     */
    public function delete(int $fileId, int $userId): array
    {
        try {
            // Get file details
            $sql = "SELECT * FROM files WHERE file_id = :file_id AND user_id = :user_id";
            $file = $this->db->fetch($sql, [':file_id' => $fileId, ':user_id' => $userId]);

            if (!$file) {
                return [
                    'success' => false,
                    'message' => 'File not found or unauthorized'
                ];
            }

            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Delete database record
            $sql = "DELETE FROM files WHERE file_id = :file_id";
            $this->db->delete($sql, [':file_id' => $fileId]);

            return [
                'success' => true,
                'message' => 'File deleted successfully'
            ];

        } catch (Exception $e) {
            error_log("File delete failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'File deletion failed'
            ];
        }
    }

    /**
     * Get file by ID
     *
     * @param int $fileId File ID
     * @return array|null File data
     */
    public function getById(int $fileId): ?array
    {
        try {
            $sql = "SELECT * FROM files WHERE file_id = :file_id";
            $result = $this->db->fetch($sql, [':file_id' => $fileId]);
            return $result ?: null;

        } catch (Exception $e) {
            error_log("Get file by ID failed: " . $e->getMessage());
            return null;
        }
    }
}
