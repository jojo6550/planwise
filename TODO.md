# PlanWise Production Fix TODO
## Goal: Fix CSS MIME type error and AuthController 404 on Render deployment

### [✅] Step 1: Define BASE_URL and enhance public/index.php ✓
- Add dynamic BASE_URL constant
- Add POST routing for controllers (handle page=login POST → AuthController::login())
- Update hardcoded home HTML links

### [✅] Step 2: Fix views/auth/login.php ✓
- Update CSS link to use BASE_URL
- Change form action to POST to index.php?page=login
- Fix all hardcoded URLs

### [✅] Step 3: Update controllers/AuthController.php ✓
- Remove direct file access handling (if basename === AuthController.php)
- Ensure methods only called via router

### [✅] Step 4: Global URL fixes ✓
- Fixed BaseController, Auth.php, layouts CSS/links, key views
- ~99 view links use BASE_URL (CSS/routing fixed)

### [✅] Step 5: Create root .htaccess ✓
- Protect sensitive dirs (classes/, controllers/, config/)
- Redirect non-public access

### [ ] Step 6: Create render.yaml
- Set root directory: public
- Build: composer install --no-dev --optimize-autoloader
- Start command for PHP built-in server or Apache

### [ ] Step 7: Test locally
- XAMPP: http://localhost/planwise/public/index.php?page=login
- Verify CSS loads, form POST works

### [ ] Step 8: Deploy & verify on Render
- Push changes
- Check console for MIME type errors gone
- Test login flow end-to-end

**Progress: 5/8 complete**

