<?php
/**
 * DataExporter Class
 * Handles exporting data to CSV and XLS formats
 * CS334 Module 2 - Generate reports (22 marks) + Use of Files (10 marks)
 */

class DataExporter
{
    private $exportDir;

    /**
     * Constructor - Initialize exporter
     */
    public function __construct()
    {
        $this->exportDir = __DIR__ . '/../exports/data/';
        
        // Create export directory if it doesn't exist
        if (!file_exists($this->exportDir)) {
            mkdir($this->exportDir, 0755, true);
        }
    }

    /**
     * Export data to CSV format
     *
     * @param string $filename Filename (without extension)
     * @param array $headers Column headers
     * @param array $data Data rows
     * @param bool $download Whether to download the file
     * @return array Result array
     */
    public function exportToCSV(string $filename, array $headers, array $data, bool $download = true): array
    {
        try {
            // Sanitize filename
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
            $filepath = $this->exportDir . $filename . '_' . date('Y-m-d_H-i-s') . '.csv';

            // Open file for writing
            $file = fopen($filepath, 'w');
            if (!$file) {
                throw new Exception("Failed to create CSV file");
            }

            // Set UTF-8 BOM for proper Excel encoding
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write headers
            fputcsv($file, $headers);

            // Write data rows
            foreach ($data as $row) {
                // Ensure row values are strings
                $csvRow = [];
                foreach ($row as $value) {
                    $csvRow[] = is_array($value) ? json_encode($value) : (string)$value;
                }
                fputcsv($file, $csvRow);
            }

            fclose($file);

            if ($download) {
                return $this->downloadFile($filepath, $filename . '.csv');
            }

            return [
                'success' => true,
                'message' => 'CSV exported successfully',
                'filepath' => $filepath
            ];

        } catch (Exception $e) {
            error_log("CSV export failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to export CSV: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export data to XLS (Excel) format using a simple table format
     * Creates a simple HTML table that Excel can read
     *
     * @param string $filename Filename (without extension)
     * @param array $headers Column headers
     * @param array $data Data rows
     * @param bool $download Whether to download the file
     * @return array Result array
     */
    public function exportToXLS(string $filename, array $headers, array $data, bool $download = true): array
    {
        try {
            // Sanitize filename
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
            $filepath = $this->exportDir . $filename . '_' . date('Y-m-d_H-i-s') . '.xls';

            // Create Excel HTML format
            $html = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $html .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ' .
                     'xmlns:o="urn:schemas-microsoft-com:office:office" ' .
                     'xmlns:x="urn:schemas-microsoft-com:office:excel" ' .
                     'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . PHP_EOL;
            
            $html .= '<Styles>' . PHP_EOL;
            $html .= '<Style ss:ID="Header"><Font ss:Bold="1" ss:Size="12"/><Interior ss:Color="#D3D3D3" ss:Pattern="Solid"/></Style>' . PHP_EOL;
            $html .= '</Styles>' . PHP_EOL;

            $html .= '<Worksheet ss:Name="Sheet1">' . PHP_EOL;
            $html .= '<Table>' . PHP_EOL;

            // Write headers
            $html .= '<Row>' . PHP_EOL;
            foreach ($headers as $header) {
                $html .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . 
                         htmlspecialchars($header, ENT_XML1, 'UTF-8') . 
                         '</Data></Cell>' . PHP_EOL;
            }
            $html .= '</Row>' . PHP_EOL;

            // Write data rows
            foreach ($data as $row) {
                $html .= '<Row>' . PHP_EOL;
                foreach ($row as $value) {
                    $cellValue = is_array($value) ? json_encode($value) : (string)$value;
                    $cellValue = htmlspecialchars($cellValue, ENT_XML1, 'UTF-8');
                    
                    // Determine data type
                    $dataType = 'String';
                    if (is_numeric($value) && !is_array($value)) {
                        $dataType = 'Number';
                    }
                    
                    $html .= '<Cell><Data ss:Type="' . $dataType . '">' . 
                             $cellValue . 
                             '</Data></Cell>' . PHP_EOL;
                }
                $html .= '</Row>' . PHP_EOL;
            }

            $html .= '</Table>' . PHP_EOL;
            $html .= '</Worksheet>' . PHP_EOL;
            $html .= '</Workbook>' . PHP_EOL;

            // Write file
            if (file_put_contents($filepath, $html) === false) {
                throw new Exception("Failed to create XLS file");
            }

            if ($download) {
                return $this->downloadFile($filepath, $filename . '.xls');
            }

            return [
                'success' => true,
                'message' => 'XLS exported successfully',
                'filepath' => $filepath
            ];

        } catch (Exception $e) {
            error_log("XLS export failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to export XLS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download file and clean up
     *
     * @param string $filepath Full file path
     * @param string $filename Filename to display
     * @return array Result array
     */
    private function downloadFile(string $filepath, string $filename): array
    {
        try {
            if (!file_exists($filepath)) {
                throw new Exception("File not found");
            }

            // Send file headers
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Pragma: no-cache');
            header('Expires: 0');

            // Read and output file
            readfile($filepath);

            // Delete file after download
            unlink($filepath);

            exit();

        } catch (Exception $e) {
            error_log("File download failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to download file: ' . $e->getMessage()
            ];
        }
    }
}
