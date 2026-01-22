* Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
=======
    /**
     * Import data from CSV
     */
    public function import()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $type = $_GET['type'] ?? '';
        if (!in_array($type, ['lesson_plans', 'users'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid import type'], 400);
            return;
        }

        // Check if admin
        $user = $this->auth->user();
        if ($user['role_id'] != 1) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->jsonResponse(['success' => false, 'message' => 'No CSV file uploaded'], 400);
            return;
        }

        $file = $_FILES['csv_file'];

        // Validate file type
        if ($file['type'] !== 'text/csv' && !preg_match('/\.csv$/i', $file['name'])) {
            $this->jsonResponse(['success' => false, 'message' => 'File must be a CSV'], 400);
            return;
        }

        // Process CSV
        $result = $this->processCSVImport($file['tmp_name'], $type);

        $this->jsonResponse($result);
    }

    /**
     * Process CSV import
     */
    private function processCSVImport(string $filePath, string $type): array
    {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            $rowNumber = 1;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNumber++;

                try {
                    if ($type === 'lesson_plans') {
                        $result = $this->importLessonPlan($data);
                    } elseif ($type === 'users') {
                        $result = $this->importUser($data);
                    }

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Row {$rowNumber}: " . $result['message'];
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }
            fclose($handle);
        }

        return [
            'success' => true,
            'message' => "Import completed. Success: {$successCount}, Errors: {$errorCount}",
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => array_slice($errors, 0, 10) // Limit errors shown
        ];
    }

    /**
     * Import a single lesson plan
     */
    private function importLessonPlan(array $data): array
    {
        require_once __DIR__ . '/../classes/LessonPlan.php';
        $lessonPlan = new LessonPlan();

        $planData = [
            'user_id' => $this->auth->id(),
            'title' => trim($data[0] ?? ''),
            'subject' => trim($data[1] ?? ''),
            'grade_level' => trim($data[2] ?? ''),
            'duration' => (int)($data[3] ?? 0),
            'objectives' => trim($data[4] ?? ''),
            'materials' => trim($data[5] ?? ''),
            'procedures' => trim($data[6] ?? ''),
            'assessment' => trim($data[7] ?? ''),
            'notes' => trim($data[8] ?? ''),
            'status' => 'draft'
        ];

        if (empty($planData['title'])) {
            return ['success' => false, 'message' => 'Title is required'];
        }

        return $lessonPlan->create($planData);
    }

    /**
     * Import a single user
     */
    private function importUser(array $data): array
    {
        require_once __DIR__ . '/../classes/User.php';
        $user = new User();

        $userData = [
            'first_name' => trim($data[0] ?? ''),
            'last_name' => trim($data[1] ?? ''),
            'email' => trim($data[2] ?? ''),
            'password' => trim($data[3] ?? ''),
            'role_id' => 2, // Default to teacher
            'status' => 'active'
        ];

        if (empty($userData['first_name']) || empty($userData['last_name']) || empty($userData['email']) || empty($userData['password'])) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        return $user->create($userData);
    }

    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
