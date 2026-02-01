# PlanWise - Frontend UX Flow & Navigation Documentation

**Date**: February 1, 2026  
**Project**: PlanWise Lesson Plan Builder

---

## Complete Navigation Map

```
┌─────────────────────────────────────────────────────────────────┐
│                         PLANWISE APPLICATION                     │
└─────────────────────────────────────────────────────────────────┘

PUBLIC PAGES (No Authentication Required)
==========================================
┌─────────────┐
│   Landing   │  (Home Page - if exists)
│    Page     │
└──────┬──────┘
       │
       ├───► [Login Page] (/public/index.php?page=login)
       │      │
       │      ├─ Form Fields: Email, Password
       │      ├─ Actions: Login Button, Forgot Password Link
       │      └─ Redirects: → Teacher Dashboard (role_id=2)
       │                   → Admin Dashboard (role_id=1)
       │
       ├───► [Register Page] (/public/index.php?page=register)
       │      │
       │      ├─ Form Fields: First Name, Last Name, Email, Password, Confirm Password
       │      ├─ Actions: Register Button
       │      └─ Redirects: → Login Page (success)
       │
       └───► [Forgot Password] (/public/index.php?page=forgot-password)
              │
              ├─ Form Fields: Email
              ├─ Actions: Request Reset Button
              └─ Redirects: → Reset Password Page
                            → Login Page (success)


TEACHER USER FLOW (role_id = 2)
================================
┌──────────────────┐
│     TEACHER      │
│    DASHBOARD     │  (/public/index.php?page=teacher/dashboard)
└────────┬─────────┘
         │
         ├─ Stats Cards:
         │   • Total Lesson Plans
         │   • Draft Plans
         │   • Published Plans
         │   • Archived Plans
         │
         ├─ Quick Actions:
         │   • Create New Lesson Plan
         │   • View All Lesson Plans
         │
         └─ Recent Activity Widget
             (Shows last 5 actions)

         │
         ├───► [Profile Page] (/public/index.php?page=teacher/profile)
         │      │
         │      ├─ View: Name, Email, Role
         │      ├─ Actions: Edit Profile, Change Password
         │      └─ Links: Back to Dashboard
         │
         ├───► [Lesson Plans List] (/public/index.php?page=teacher/lesson-plans)
         │      │
         │      ├─ Filter Options:
         │      │   • By Status (Draft/Published/Archived)
         │      │   • By Subject
         │      │   • Search by Title
         │      │
         │      ├─ Table Columns:
         │      │   • ID | Title | Subject | Grade | Status | Created | Actions
         │      │
         │      ├─ Actions per Row:
         │      │   • View (eye icon)
         │      │   • Edit (pencil icon)
         │      │   • Delete (trash icon) [AJAX]
         │      │
         │      └─ Navigation:
         │          ├─ Create New Lesson Plan Button → [Create Page]
         │          └─ View Button → [View Page]
         │
         ├───► [Create Lesson Plan] (/public/index.php?page=teacher/lesson-plans/create)
         │      │
         │      ├─ Form Sections:
         │      │   1. Basic Information
         │      │      • Title (required)
         │      │      • Subject
         │      │      • Grade Level
         │      │      • Duration (minutes)
         │      │   
         │      │   2. Lesson Details
         │      │      • Objectives
         │      │      • Materials
         │      │      • Procedures
         │      │      • Assessment
         │      │      • Notes
         │      │   
         │      │   3. Lesson Sections (Dynamic)
         │      │      • Section Type (Introduction/Main/Conclusion)
         │      │      • Section Content
         │      │      • Add More Sections Button
         │      │   
         │      │   4. Status Selection
         │      │      • Draft / Published / Archived
         │      │
         │      ├─ Validation:
         │      │   • Client-side: HTML5 + JavaScript
         │      │   • Server-side: Validator class
         │      │
         │      ├─ Actions:
         │      │   • Save as Draft Button
         │      │   • Save and Publish Button
         │      │   • Cancel Button → [List Page]
         │      │
         │      └─ On Success:
         │          • QR Code Auto-generated
         │          • Activity Logged
         │          • Redirect → [View Page] or [List Page]
         │
         ├───► [Edit Lesson Plan] (/public/index.php?page=teacher/lesson-plans/edit&id={id})
         │      │
         │      ├─ Pre-populated Form (same as Create)
         │      ├─ Additional Info: Created Date, Updated Date
         │      ├─ Actions:
         │      │   • Update Button
         │      │   • Cancel Button → [View Page]
         │      └─ On Success: Redirect → [View Page]
         │
         └───► [View Lesson Plan] (/public/index.php?page=teacher/lesson-plans/view&id={id})
                │
                ├─ Display Sections:
                │   • Lesson Title (Header)
                │   • Basic Information Card
                │   • Lesson Details Card
                │   • Lesson Sections (Accordion)
                │   • QR Code Section
                │   • Metadata (Created, Updated)
                │
                ├─ Actions:
                │   • Edit Button → [Edit Page]
                │   • Delete Button [AJAX Confirm]
                │   • Export to PDF [Controller]
                │   • Export to Word [Controller]
                │   • Generate QR Code [AJAX]
                │   • Download QR Code
                │   • Back to List → [List Page]
                │
                └─ QR Code Features:
                    • Display existing QR code image
                    • Regenerate QR code button [AJAX]
                    • Download QR code button


ADMIN USER FLOW (role_id = 1)
==============================
┌──────────────────┐
│      ADMIN       │
│    DASHBOARD     │  (/public/index.php?page=admin/dashboard)
└────────┬─────────┘
         │
         ├─ System Stats Cards:
         │   • Total Users
         │   • Total Lesson Plans
         │   • Active Teachers
         │   • Today's Activity
         │
         ├─ Quick Actions:
         │   • Manage Users
         │   • View Activity Logs
         │   • Import Data
         │   • System Settings
         │
         └─ Charts/Graphs:
             • Lesson Plans by Status (Pie Chart)
             • Activity over Time (Line Chart)

         │
         ├───► [User Management] (/public/index.php?page=admin/users)
         │      │
         │      ├─ User List Table:
         │      │   • ID | Name | Email | Role | Status | Actions
         │      │
         │      ├─ Filters:
         │      │   • By Role
         │      │   • By Status
         │      │   • Search by Name/Email
         │      │
         │      ├─ Actions per Row:
         │      │   • View Details
         │      │   • Edit User
         │      │   • Change Status (Active/Inactive) [AJAX]
         │      │   • Delete User [AJAX Confirm]
         │      │
         │      └─ Navigation:
         │          └─ Create New User Button → [Create User Page]
         │
         ├───► [Activity Logs] (/public/index.php?page=admin/activity-logs)
         │      │
         │      ├─ Log Table:
         │      │   • Timestamp | User | Action | IP Address | Details
         │      │
         │      ├─ Filters:
         │      │   • By User
         │      │   • By Action Type
         │      │   • Date Range
         │      │   • Search
         │      │
         │      ├─ Actions:
         │      │   • Export to CSV
         │      │   • Cleanup Old Logs (90+ days)
         │      │
         │      └─ Pagination: Previous | Next
         │
         └───► [Import Data] (/public/index.php?page=admin/import)
                │
                ├─ Import Instructions Card
                │   • Supported formats: CSV
                │   • Field requirements
                │   • Sample template download
                │
                ├─ Upload Form:
                │   • File input (CSV only)
                │   • Import type selection
                │   • Validate button
                │   • Import button
                │
                └─ Import Results:
                    • Success count
                    • Error count
                    • Error details


COMMON NAVIGATION ELEMENTS
===========================
All Authenticated Pages Include:

┌─────────────────────────────────────────────────────────┐
│  NAVBAR (Top Navigation)                                │
├─────────────────────────────────────────────────────────┤
│  Logo/Brand         [Dashboard] [Lesson Plans] [Profile]│
│  "PlanWise"         (Teacher)                   [Logout] │
│                     [Dashboard] [Users] [Logs] [Import] │
│                     (Admin)                     [Logout] │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  ALERT CONTAINER (Dynamic, Top-Right)                   │
├─────────────────────────────────────────────────────────┤
│  • Success alerts (green)                               │
│  • Error alerts (red)                                   │
│  • Warning alerts (yellow)                              │
│  • Info alerts (blue)                                   │
│  • Auto-dismiss after 5 seconds                         │
│  • Manual dismiss with X button                         │
└─────────────────────────────────────────────────────────┘


FORM INTERACTION PATTERNS
==========================

1. CREATE/EDIT FORMS
   ┌───────────────────┐
   │  User fills form  │
   └────────┬──────────┘
            │
            ├─ Client-side Validation (HTML5 + JS)
            │  • Required fields
            │  • Email format
            │  • Min/max length
            │  • Pattern matching
            │
            ├─ User clicks Submit
            │
            ├─ Server-side Validation (PHP Validator class)
            │  • All client rules re-validated
            │  • Business logic validation
            │  • Database constraints check
            │
            ├─ If Valid:
            │  └─→ Save to database
            │     └─→ Activity log entry
            │        └─→ Success message + redirect
            │
            └─ If Invalid:
               └─→ Display error messages
                  • Field-level errors (below inputs)
                  • Global error alert (top of page)
                  └─→ User corrects and resubmits

2. AJAX DELETE CONFIRMATION
   ┌──────────────────────┐
   │  User clicks Delete  │
   └──────────┬───────────┘
              │
              ├─ JavaScript confirm dialog
              │  "Are you sure you want to delete this item?"
              │
              ├─ User confirms → AJAX DELETE request
              │  • Show loading spinner
              │  • Send request to server
              │  • Server validates ownership/permissions
              │  • Server deletes record
              │  • Server logs activity
              │  • Server responds with JSON
              │
              ├─ On Success:
              │  • Hide loading spinner
              │  • Show success message
              │  • Remove row from table (no page reload)
              │  • Update stats if visible
              │
              └─ On Error:
                 • Hide loading spinner
                 • Show error message
                 • Keep row in table

3. AJAX FORM SUBMISSION (Lesson Plans)
   ┌──────────────────────┐
   │  User fills form     │
   │  User clicks Submit  │
   └──────────┬───────────┘
              │
              ├─ Prevent default form submission
              ├─ Gather form data (FormData or JSON)
              ├─ Show loading state
              │
              ├─ AJAX POST request
              │  • Headers: Content-Type, CSRF token
              │  • Body: Form data as JSON
              │
              ├─ Server processes:
              │  • Validates data
              │  • Creates/updates record
              │  • Generates QR code (if new)
              │  • Logs activity
              │  • Returns JSON response
              │
              ├─ On Success:
              │  • Hide loading
              │  • Show success alert
              │  • Reset form OR redirect
              │  • Update UI elements
              │
              └─ On Error:
                 • Hide loading
                 • Show error alert
                 • Display field errors
                 • Keep form data


USER JOURNEY EXAMPLES
======================

JOURNEY 1: Teacher Creates First Lesson Plan
---------------------------------------------
1. Teacher logs in at /login
   → Redirected to /teacher/dashboard
2. Sees "0 Total Lesson Plans" stat
3. Clicks "Create New Lesson Plan" button
   → Navigates to /teacher/lesson-plans/create
4. Fills out form:
   • Title: "Introduction to Algebra"
   • Subject: "Mathematics"
   • Grade: "9"
   • Duration: 60 minutes
   • Objectives, Materials, Procedures, etc.
5. Clicks "Save and Publish"
6. Server validates and saves
7. QR code auto-generated
8. Activity logged: "lesson_plan_created"
9. Success message shown
10. Redirected to /teacher/lesson-plans/view&id=1
11. Can see lesson details and QR code
12. Can export to PDF or edit

JOURNEY 2: Admin Reviews System Activity
-----------------------------------------
1. Admin logs in at /login
   → Redirected to /admin/dashboard
2. Sees system stats and recent activity widget
3. Clicks "View Activity Logs" or nav link
   → Navigates to /admin/activity-logs
4. Sees table of all system activities
5. Filters by action type: "lesson_plan_created"
6. Reviews who created which lesson plans
7. Clicks "Export to CSV" for reporting
8. Downloads activity log CSV file

JOURNEY 3: Teacher Updates Existing Lesson
-------------------------------------------
1. Teacher at /teacher/dashboard
2. Clicks "Lesson Plans" in nav
   → Navigates to /teacher/lesson-plans
3. Sees list of all their lesson plans
4. Finds lesson to update
5. Clicks Edit icon (pencil)
   → Navigates to /teacher/lesson-plans/edit&id=X
6. Form pre-populated with existing data
7. Makes changes to objectives and materials
8. Clicks "Update" button
9. Server validates and updates
10. Activity logged: "lesson_plan_updated"
11. Success message shown
12. Redirected to /teacher/lesson-plans/view&id=X


ERROR HANDLING FLOWS
=====================

1. VALIDATION ERRORS
   User Input → Validation Fails
   ├─ Client-side: Instant feedback as user types
   │  • Red border on invalid fields
   │  • Error message below field
   │  • Submit button may be disabled
   │
   └─ Server-side: After form submission
      • Return error response (422 status if AJAX)
      • Display alert at top of page
      • Show field-level errors
      • Keep user input (don't clear form)

2. AUTHENTICATION ERRORS
   User attempts protected action
   ├─ Not logged in:
   │  → Redirect to /login with error message
   │  → After login, redirect back to intended page
   │
   └─ Wrong role/permissions:
      → Show 403 Forbidden page
      → Link back to appropriate dashboard

3. SERVER ERRORS
   Something goes wrong on server
   ├─ Display error alert
   ├─ Log error details server-side
   ├─ Show user-friendly message
   └─ Provide "Try Again" or "Contact Support" options

4. NETWORK ERRORS
   AJAX request fails (timeout, offline)
   ├─ Hide loading spinner
   ├─ Show error: "Network error. Please check connection."
   ├─ Provide "Retry" button
   └─ Don't lose user's form data


ACCESSIBILITY CONSIDERATIONS
=============================
✅ All forms have proper labels
✅ Required fields marked with aria-required
✅ Error messages associated with fields (aria-describedby)
✅ Keyboard navigation supported
✅ Focus states visible
✅ Color not sole indicator of status
✅ Alt text on images
✅ ARIA labels on icon buttons


RESPONSIVE BEHAVIOR
===================
• Mobile (<768px):
  - Collapsed navbar with hamburger menu
  - Single column layouts
  - Touch-friendly button sizes (min 44x44px)
  - Simplified tables (horizontal scroll or stacked)

• Tablet (768px-1024px):
  - Two-column layouts where appropriate
  - Expanded navbar
  - Full tables with scroll

• Desktop (>1024px):
  - Multi-column layouts
  - Full navigation always visible
  - Dashboard cards in grid (2-4 columns)


PERFORMANCE OPTIMIZATIONS
==========================
• AJAX for delete operations (no page reload)
• Loading states for async operations
• Debounced search inputs
• Pagination for large datasets
• Lazy loading of QR code images
• CSS/JS minification (production)
• Browser caching of static assets
