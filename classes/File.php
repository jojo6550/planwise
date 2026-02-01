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
    private $thumbnailDir;
    private $allowedTypes;
    private $maxFileSize;

    /**
     * Constructor - Initialize file handler
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../uploads/lesson-plans/';
        $this->thumbnailDir = __DIR__ . '/../uploads/thumbnails/';
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

        // Create directories if they don't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        if (!file_exists($this->thumbnailDir)) {
            mkdir($this->thumbnailDir, 0755, true);
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
            $sql = "INSERT INTO files (user_id, lesson_id, original_name, file_name, file_path, file_type, file_size, uploaded_at)
                    VALUES (:user_id, :lesson_id, :original_name, :file_name, :file_path, :file_type, :file_size, NOW())";

            $params = [
                ':user_id' => $userId,
                ':lesson_id' => $lessonPlanId,
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

        // Check file size is not zero
        if ($file['size'] === 0) {
            return [
                'success' => false,
                'message' => 'File is empty'
            ];
        }

        // Check file type using multiple methods
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'File type not allowed'
            ];
        }

        // Additional security: Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'cmd', 'com', 'scr', 'pif', 'jar', 'vb', 'vbs', 'js', 'hta'];
        if (in_array($extension, $dangerousExtensions)) {
            return [
                'success' => false,
                'message' => 'File type not allowed for security reasons'
            ];
        }

        // Sanitize filename
        $originalName = $file['name'];
        $sanitizedName = $this->sanitizeFileName($originalName);
        if ($sanitizedName !== $originalName) {
            // Update the file array with sanitized name
            $file['name'] = $sanitizedName;
        }

        return ['success' => true];
    }

    /**
     * Sanitize filename to prevent security issues
     *
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    private function sanitizeFileName(string $filename): string
    {
        // Remove any path information
        $filename = basename($filename);

        // Replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Remove multiple dots
        $filename = preg_replace('/\.+/', '.', $filename);

        // Ensure it doesn't start or end with dot
        $filename = trim($filename, '.');

        // Limit length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = substr($name, 0, 250 - strlen($extension));
            $filename = $name . '.' . $extension;
        }

        return $filename;
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
                    WHERE lesson_id = :lesson_id
                    ORDER BY uploaded_at DESC";

            return $this->db->fetchAll($sql, [':lesson_id' => $lessonPlanId]);

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
     * Generate thumbnail for image
     *
     * @param string $imagePath Original image path
     * @param string $fileName Original filename
     * @return string|null Thumbnail path or null if failed
     */
    private function generateThumbnail(string $imagePath, string $fileName): ?string
    {
        try {
            // Get image info
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return null;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mime = $imageInfo['mime'];

            // Calculate thumbnail size (max 200px)
            $maxSize = 200;
            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = ($height / $width) * $maxSize;
            } else {
                $newHeight = $maxSize;
                $newWidth = ($width / $height) * $maxSize;
            }

            // Create image resource
            $sourceImage = null;
            switch ($mime) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($imagePath);
                    break;
                default:
                    return null;
            }

            if (!$sourceImage) {
                return null;
            }

            // Create thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Generate thumbnail filename
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $thumbnailName = 'thumb_' . pathinfo($fileName, PATHINFO_FILENAME) . '.' . $extension;
            $thumbnailPath = $this->thumbnailDir . $thumbnailName;

            // Save thumbnail
            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($thumbnail, $thumbnailPath, 90);
                    break;
                case 'image/png':
                    imagepng($thumbnail, $thumbnailPath, 9);
                    break;
                case 'image/gif':
                    imagegif($thumbnail, $thumbnailPath);
                    break;
            }

            // Clean up memory
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);

            return $thumbnailPath;

        } catch (Exception $e) {
            error_log("Thumbnail generation failed: " . $e->getMessage());
            return null;
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

    /**
     * Check if file type is allowed
     *
     * @param string $extension File extension
     * @return bool True if allowed
     */
    private function isAllowedFileType(string $extension): bool
    {
        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        return in_array(strtolower($extension), $allowedExtensions);
    }

    /**
     * Check if file size is valid
     *
     * @param int $size File size in bytes
     * @return bool True if valid
     */
    private function isValidFileSize(int $size): bool
    {
        return $size <= $this->maxFileSize;
    }

    /**
     * Get file extension from filename
     *
     * @param string $filename Filename
     * @return string File extension (lowercase)
     */
    private function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}
