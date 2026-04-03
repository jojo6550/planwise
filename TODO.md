# PlanWise 403 Fix - TODO Steps

## Approved Plan Steps (Step-by-step execution):

### Step 1: Update ALL .htaccess files to Apache 2.4 syntax
- Replace `Order Deny,Allow` + `Deny from all` → `Require all denied`
- Replace `Order Allow,Deny` + `Allow from all` → `Require all granted`
- Target files: classes/, config/, controllers/, database/, exports/, public/qr/, uploads/, views/, public/

**Status: [x] Complete**

### Step 2: Fix Dockerfile for writable directories
- Add post-copy: `mkdir -p uploads exports public/qr logs`
- `chmod 777 uploads exports public/qr logs`
- `chown -R www-data:www-data uploads exports public/qr logs`

**Status: [x] Complete**

### Step 3: Test locally with Docker
- `docker-compose up --build`
- Visit http://localhost → verify landing page loads, no 403

**Status: [ ] Pending**

### Step 4: User actions post-edits
- Push changes to GitHub → auto-redeploy on Render
- Check Render dashboard → Logs tab for errors
- Verify https://planwise-1.onrender.com shows landing page

**Status: [ ] Pending**

### Step 5: If needed - Set Render env vars (per email.md)
- DB_HOST, DB_PORT=3306, DB_NAME, DB_USER, DB_PASS (FreeSQLDatabase)
- MAIL_* vars

**Status: [ ] Pending**

*Progress tracked here. Steps marked [x] when complete.*
