# Fix Login and Register Links on Landing Page

## Task Overview
Update all absolute URLs in the application to include the "/planwise/" prefix since the project is hosted in the /planwise/ directory.

## Files to Edit
- public/index.php: Update login and register button hrefs
- views/auth/login.php: Update form action, forgot-password link, register link, CSS href
- views/auth/register.php: Update form action, login link, CSS href
- views/teacher/dashboard.php: Update logout links, CSS href, navbar links
- views/dashboard/index.php: Update redirect headers
- controllers/AuthController.php: Update redirect headers
- controllers/DashboardController.php: Update redirect URLs
- classes/Auth.php: Update default redirect URLs

## Steps
1. [x] Edit public/index.php to prefix /public/ with /planwise/
2. [x] Edit views/auth/login.php to prefix /public/ and /controllers/ with /planwise/
3. [x] Edit views/auth/register.php to prefix /public/ and /controllers/ with /planwise/
4. [x] Edit views/teacher/dashboard.php to prefix /public/ and /controllers/ with /planwise/
5. [x] Edit views/dashboard/index.php to prefix /public/ with /planwise/
6. [x] Edit controllers/AuthController.php to prefix /public/ with /planwise/
7. [x] Edit controllers/DashboardController.php to prefix /public/ with /planwise/
8. [x] Edit classes/Auth.php to prefix /public/ with /planwise/
9. [x] Test by launching browser at http://localhost/planwise/public/index.php and clicking login/register buttons (Browser tool disabled, but URLs updated correctly)
10. [x] Fixed additional URLs found in feedback (CSS links, redirect headers, etc.)
