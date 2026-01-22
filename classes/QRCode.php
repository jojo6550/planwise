$sql = "INSERT INTO qr_codes (lesson_plan_id, qr_code_data, qr_image_path, created_at)
                    VALUES (:lesson_plan_id, :qr_code_data, :qr_image_path, NOW())";

            $params = [
                ':lesson_plan_id' => $lessonPlanId,
                ':qr_code_data' => $qrData,
                ':qr_image_path' => $filePath
            ];

            $this->db->insert($sql, $params);

            return [
                'success' => true,
                'message' => 'QR code generated successfully',
                'qr_image_path' => $filePath,
                'qr_data' => $qrData
            ];

        } catch (Exception $e) {
            error_log("QR code generation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate QR code'
            ];
        }
    }
=======
            // Store QR code data in database
            $sql = "INSERT INTO qr_codes (lesson_plan_id, qr_code_data, qr_image_path, created_at)
                    VALUES (:lesson_plan_id, :qr_code_data, :qr_image_path, NOW())";

            $params = [
                ':lesson_plan_id' => $lessonPlanId,
                ':qr_code_data' => $qrData,
                ':qr_image_path' => $filePath
            ];

            $this->db->insert($sql, $params);

            return [
                'success' => true,
                'message' => 'QR code generated successfully',
                'qr_image_path' => $filePath,
                'qr_data' => $qrData
            ];

        } catch (Exception $e) {
            error_log("QR code generation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate QR code'
            ];
        }
    }

    /**
     * Get QR code by lesson plan ID
     *
     * @param int $lessonPlanId Lesson plan ID
     * @return array|null QR code data
     */
    public function getByLessonPlanId(int $lessonPlanId): ?array
    {
        try {
            $sql = "SELECT * FROM qr_codes WHERE lesson_plan_id = :lesson_plan_id ORDER BY created_at DESC LIMIT 1";
            $result = $this->db->fetch($sql, [':lesson_plan_id' => $lessonPlanId]);
            return $result ?: null;

        } catch (Exception $e) {
            error_log("Get QR code failed: " . $e->getMessage());
            return null;
        }
    }
