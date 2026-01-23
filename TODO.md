# TODO: Fix TCPDF Warnings and Errors

- [x] Remove `use TCPDF;` statement from classes/PDFExporter.php (not present in code)
- [x] Remove constant definitions from classes/PDFExporter.php (not present in code)
- [x] Add output buffering in controllers/ExportController.php for PDF generation (already implemented)

# TODO: Fix Lesson Plan Creation Silent Failure

- [x] Add missing action attribute to create form in views/teacher/lesson-plans/create.php
- [x] Add missing require_once statements for Validator and AuthController classes in LessonPlanController.php
