# PlanWise Lesson Plans CRUD Implementation

## Controller Changes
- [ ] Remove auth check from constructor
- [ ] Add auth checks to individual methods (redirect for pages, JSON for AJAX)
- [ ] Add getIndexData(), getViewData($id), getEditData($id) methods returning data arrays
- [ ] Modify create() and update() to handle sections from $_POST['sections']

## View Changes
- [ ] index.php: Remove database logic, add controller data retrieval
- [ ] create.php: Remove database logic, add sections form, add controller data retrieval
- [ ] edit.php: Remove database logic, add sections form, display existing sections, add controller data retrieval
- [ ] view.php: Remove database logic, add controller data retrieval

## Testing
- [ ] Create tests/ directory
- [ ] Create tests/LessonPlanTest.php
- [ ] Create tests/LessonSectionTest.php
- [ ] Create tests/LessonPlanControllerTest.php
- [ ] Run tests to ensure they pass
